<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Quiz;
use App\Models\QuizSetting;
use App\Services\QuizService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DailyQuizTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Update initial quiz settings created by migration
        QuizSetting::first()->update([
            'reset_time' => '08:00',
            'questions_per_day' => 1,
            'xp_per_correct' => 10,
        ]);
    }

    public function test_participant_can_view_quiz_if_not_taken_today()
    {
        $user = User::factory()->create(['role' => 'participant', 'jenjang' => 'SMA']);
        $quiz = Quiz::create([
            'jenjang' => 'SMA',
            'type' => 'pilihan_ganda',
            'question_text' => 'What is 1+1?',
            'correct_answer' => ['A'],
            'options' => ['2', '3', '4'],
            'score_correct' => 1,
            'score_incorrect' => 0,
        ]);

        $this->actingAs($user);
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Quiz Hari Ini');
    }

    public function test_participant_receives_random_quiz_from_their_jenjang()
    {
        $user = User::factory()->create(['role' => 'participant', 'jenjang' => 'SMP']);
        $quiz = Quiz::create([
            'jenjang' => 'SMP',
            'type' => 'pilihan_ganda',
            'question_text' => 'What is 2+2?',
            'correct_answer' => ['A'],
            'options' => ['4', '5', '6'],
            'score_correct' => 1,
            'score_incorrect' => 0,
        ]);
        Quiz::create([
            'jenjang' => 'SMA',
            'type' => 'pilihan_ganda',
            'question_text' => 'Not this one',
            'correct_answer' => ['A'],
            'options' => ['1'],
            'score_correct' => 1,
            'score_incorrect' => 0,
        ]);

        $this->actingAs($user);
        
        $response = $this->get('/quiz');
        $response->assertStatus(200);
        $response->assertSee('What is 2+2?');
        $response->assertDontSee('Not this one');
    }

    public function test_submit_quiz_correct_answer_gives_xp()
    {
        $user = User::factory()->create(['role' => 'participant', 'jenjang' => 'SMA']);
        $quiz = Quiz::create([
            'jenjang' => 'SMA',
            'type' => 'pilihan_ganda',
            'question_text' => 'What is 1+1?',
            'correct_answer' => ['A'],
            'options' => ['A', 'B', 'C'],
            'score_correct' => 1,
            'score_incorrect' => 0,
        ]);

        $service = new QuizService();
        $assigned = $service->attemptsForToday($user)->first();

        $result = $service->submitAnswer($user, $assigned, '0');

        $this->assertEquals(10, \App\Models\UserXp::where('user_id', $user->id)->value('total_xp'));
        $this->assertTrue($result['is_correct']);
        $this->assertEquals(10, $result['xp_earned']);
    }

    public function test_submit_quiz_incorrect_answer_gives_no_xp()
    {
        $user = User::factory()->create(['role' => 'participant', 'jenjang' => 'SMA']);
        $quiz = Quiz::create([
            'jenjang' => 'SMA',
            'type' => 'pilihan_ganda',
            'question_text' => 'What is 1+1?',
            'correct_answer' => ['A'],
            'options' => ['A', 'B', 'C'],
            'score_correct' => 1,
            'score_incorrect' => 0,
        ]);

        $service = new QuizService();
        $assigned = $service->attemptsForToday($user)->first();

        $result = $service->submitAnswer($user, $assigned, '1');

        $this->assertEquals(0, (int) \App\Models\UserXp::where('user_id', $user->id)->value('total_xp'));
        $this->assertFalse($result['is_correct']);
        $this->assertEquals(0, $result['xp_earned']);
    }

    public function test_daily_reset_logic()
    {
        $user = User::factory()->create(['role' => 'participant', 'jenjang' => 'SMA']);
        $quiz1 = Quiz::create([
            'jenjang' => 'SMA',
            'type' => 'pilihan_ganda',
            'question_text' => 'Q1',
            'correct_answer' => ['A'],
            'options' => ['A'],
            'score_correct' => 1,
            'score_incorrect' => 0,
        ]);
        $quiz2 = Quiz::create([
            'jenjang' => 'SMA',
            'type' => 'pilihan_ganda',
            'question_text' => 'Q2',
            'correct_answer' => ['A'],
            'options' => ['A'],
            'score_correct' => 1,
            'score_incorrect' => 0,
        ]);

        $service = new QuizService();

        // Simulate taking quiz at 07:00 AM on Day 1
        Carbon::setTestNow(Carbon::create(2024, 1, 1, 7, 0, 0));
        $period1 = $service->currentPeriodKey();
        $assigned1 = $service->attemptsForToday($user)->first();
        $service->submitAnswer($user, $assigned1, '0');
        
        // Cannot take again immediately
        $pending = $service->attemptsForToday($user)->filter(fn($a) => $a->answered_at === null);
        $this->assertTrue($pending->isEmpty());

        // Simulate time advancing to 08:01 AM on Day 1 (after reset)
        Carbon::setTestNow(Carbon::create(2024, 1, 1, 8, 1, 0));
        $period2 = $service->currentPeriodKey();
        
        // Should be able to take a new quiz
        $pendingNew = $service->attemptsForToday($user)->filter(fn($a) => $a->answered_at === null);
        $this->assertFalse($pendingNew->isEmpty(), "New pending array is empty. DB Attempts: " . \App\Models\QuizAttempt::count() . ". P1: $period1, P2: $period2");
        $assigned2 = $pendingNew->first();
        
        // Verify it's a new assignment block
        $this->assertNotEquals($assigned1->id, $assigned2->id);
    }

    public function test_quiz_card_still_displayed_on_dashboard_after_quiz_completed(): void
    {
        $user = User::factory()->create(['role' => 'participant', 'jenjang' => 'SMA']);
        $quiz = Quiz::create([
            'jenjang' => 'SMA',
            'type' => 'pilihan_ganda',
            'question_text' => 'Berapa 5 + 5?',
            'correct_answer' => ['A'],
            'options' => ['10', '15', '20'],
            'score_correct' => 1,
            'score_incorrect' => 0,
        ]);

        $service = new QuizService();
        $assigned = $service->attemptsForToday($user)->first();
        $service->submitAnswer($user, $assigned, '0');

        $this->actingAs($user);
        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Quiz Hari Ini');
        $response->assertSee('Sudah Selesai');
        $response->assertSee('Lihat Hasil');
    }

    public function test_user_can_view_quiz_in_preview_mode_after_completed(): void
    {
        $user = User::factory()->create(['role' => 'participant', 'jenjang' => 'SMA']);
        $quiz = Quiz::create([
            'jenjang' => 'SMA',
            'type' => 'pilihan_ganda',
            'question_text' => 'Soal untuk preview mode',
            'explanation' => 'Penjelasan jawaban kuis ini.',
            'correct_answer' => ['A'],
            'options' => ['A', 'B'],
            'score_correct' => 1,
            'score_incorrect' => 0,
        ]);

        $service = new QuizService();
        $assigned = $service->attemptsForToday($user)->first();
        $service->submitAnswer($user, $assigned, '0');

        $this->actingAs($user);
        $response = $this->get('/quiz');

        $response->assertStatus(200);
        $response->assertViewIs('participant.quiz');
        $response->assertSee('Soal untuk preview mode');
        $response->assertSee('Penjelasan jawaban kuis ini.');
        $response->assertViewHas('isPreview', true);
    }
}
