<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ExamCategoryResult;
use App\Models\ExamResult;
use App\Models\ExamSession;
use App\Models\ExamSessionCategory;
use App\Models\ExamSessionParticipant;
use App\Models\QuestionBank;
use App\Models\User;
use App\Models\UserAnswer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantScoreVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_pages_show_irt_and_predicate_without_raw_score(): void
    {
        [$user, $session, $participant] = $this->makeFinishedAttempt();

        $routes = [
            route('participant.dashboard'),
            route('participant.session.show', $session->id),
            route('participant.result', $participant->id),
            route('participant.review', $participant->id),
            route('participant.statistics', $session->id),
            route('exam.success', $session->code),
        ];

        foreach ($routes as $url) {
            $response = $this->actingAs($user)->get($url);

            $response->assertOk();
            $response->assertDontSee('Skor Raw', false);
            $response->assertDontSee('Skor Mentah', false);
            $response->assertDontSee('Raw Score', false);
        }

        $this->actingAs($user)
            ->get(route('participant.result', $participant->id))
            ->assertSee('Skor IRT')
            ->assertSee('Istimewa');
    }

    private function makeFinishedAttempt(): array
    {
        $user = User::factory()->create(['role' => 'premium']);
        $category = Category::create(['name' => 'Matematika', 'slug' => 'matematika']);
        $session = ExamSession::create([
            'name' => 'Sesi Visibility',
            'code' => 'VISIBLE1',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'start_time' => '00:00',
            'end_time' => '23:59',
            'is_active' => true,
            'predicate_kurang_min' => 100,
            'predicate_memadai_min' => 500,
            'predicate_baik_min' => 700,
            'predicate_istimewa_min' => 850,
        ]);
        ExamSessionCategory::create([
            'exam_session_id' => $session->id,
            'category_id' => $category->id,
            'duration' => 60,
            'total_questions' => 1,
            'max_score_raw' => 100,
            'min_score_irt' => 100,
            'max_score_irt' => 1000,
        ]);
        $question = QuestionBank::create([
            'category_id' => $category->id,
            'type' => 'pilihan_ganda',
            'question_text' => 'Soal visibility',
            'options' => ['Benar', 'Salah'],
            'correct_answer' => ['A'],
            'score_correct' => 1,
            'score_incorrect' => 0,
        ]);
        $session->questions()->attach($question->id);

        $participant = ExamSessionParticipant::create([
            'exam_session_id' => $session->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'whatsapp' => '081234567890',
            'access_code' => 'VIS001',
            'privilege' => 'premium',
            'started_at' => now()->subHour(),
            'finished_at' => now(),
        ]);
        $participant->questions()->attach($question->id);
        UserAnswer::create([
            'participant_id' => $participant->id,
            'exam_session_id' => $session->id,
            'question_bank_id' => $question->id,
            'answer' => 0,
            'is_correct' => true,
            'score' => 1,
        ]);
        $result = ExamResult::create([
            'participant_id' => $participant->id,
            'exam_session_id' => $session->id,
            'total_correct' => 1,
            'total_incorrect' => 0,
            'total_blank' => 0,
            'score' => 42.5,
            'irt_score' => 900,
        ]);
        ExamCategoryResult::create([
            'exam_result_id' => $result->id,
            'category_id' => $category->id,
            'total_correct' => 1,
            'total_incorrect' => 0,
            'total_blank' => 0,
            'score' => 42.5,
            'irt_score' => 900,
        ]);

        return [$user, $session, $participant];
    }
}
