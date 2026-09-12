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

    public function test_participant_pages_show_only_raw_score_and_raw_predicate_in_raw_mode(): void
    {
        [$user, $session, $participant] = $this->makeFinishedAttempt('raw');

        foreach ($this->participantRoutes($session, $participant) as $url) {
            $response = $this->actingAs($user)->get($url);

            $response->assertOk();
            $response->assertDontSee('Skor IRT', false);
            $response->assertDontSee('Predikat IRT', false);
        }

        $this->actingAs($user)
            ->get(route('participant.result', $participant->id))
            ->assertSee('Skor Raw')
            ->assertSee('Predikat Raw')
            ->assertSee('Baik')
            ->assertDontSee('900.00');
    }

    public function test_participant_pages_show_only_irt_score_and_irt_predicate_in_irt_mode(): void
    {
        [$user, $session, $participant] = $this->makeFinishedAttempt('irt');

        foreach ($this->participantRoutes($session, $participant) as $url) {
            $response = $this->actingAs($user)->get($url);

            $response->assertOk();
            $response->assertDontSee('Skor Raw', false);
            $response->assertDontSee('Predikat Raw', false);
        }

        $this->actingAs($user)
            ->get(route('participant.result', $participant->id))
            ->assertSee('Skor IRT')
            ->assertSee('Predikat IRT')
            ->assertSee('Istimewa')
            ->assertDontSee('42.50');
    }

    public function test_participant_pages_show_both_scores_and_predicates_in_both_mode(): void
    {
        [$user, $session, $participant] = $this->makeFinishedAttempt('both');

        foreach ($this->participantRoutes($session, $participant) as $url) {
            $response = $this->actingAs($user)->get($url);

            $response->assertOk();
        }

        $this->actingAs($user)
            ->get(route('participant.result', $participant->id))
            ->assertSee('Skor Raw')
            ->assertSee('Predikat Raw')
            ->assertSee('Skor IRT')
            ->assertSee('Predikat IRT');
    }

    public function test_statistics_rank_by_the_score_selected_for_the_session(): void
    {
        [$user, $session, $participant] = $this->makeFinishedAttempt('raw');
        $otherUser = User::factory()->create(['role' => 'premium', 'name' => 'Peringkat Raw']);
        $otherParticipant = ExamSessionParticipant::create([
            'exam_session_id' => $session->id,
            'user_id' => $otherUser->id,
            'name' => $otherUser->name,
            'whatsapp' => '081234567891',
            'access_code' => 'VIS002',
            'privilege' => 'premium',
            'started_at' => now()->subHour(),
            'finished_at' => now(),
        ]);
        ExamResult::create([
            'participant_id' => $otherParticipant->id,
            'exam_session_id' => $session->id,
            'total_correct' => 1,
            'total_incorrect' => 0,
            'total_blank' => 0,
            'score' => 80,
            'irt_score' => 200,
        ]);

        $this->actingAs($user)
            ->get(route('participant.statistics', $session->id))
            ->assertSeeInOrder([$otherParticipant->name, $participant->name]);

        $session->update(['participant_score_display' => 'irt']);

        $this->actingAs($user)
            ->get(route('participant.statistics', $session->id))
            ->assertSeeInOrder([$participant->name, $otherParticipant->name]);
    }

    private function participantRoutes(ExamSession $session, ExamSessionParticipant $participant): array
    {
        return [
            route('participant.dashboard'),
            route('participant.session.show', $session->id),
            route('participant.result', $participant->id),
            route('participant.review', $participant->id),
            route('participant.statistics', $session->id),
            route('exam.success', $session->code),
        ];
    }

    private function makeFinishedAttempt(string $display): array
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
            'participant_score_display' => $display,
            'predicate_raw_kurang_min' => 0,
            'predicate_raw_memadai_min' => 30,
            'predicate_raw_baik_min' => 40,
            'predicate_raw_istimewa_min' => 50,
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
