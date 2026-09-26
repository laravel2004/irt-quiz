<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ExamSession;
use App\Models\ExamSessionCategory;
use App\Models\ExamSessionParticipant;
use App\Models\ParticipantCategoryStatus;
use App\Models\QuestionBank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExamSubmissionRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_submit_saves_answer_and_finishes_category(): void
    {
        $exam = $this->makeExam();

        $response = $this->actingAs($exam['user'])->postJson($this->submitUrl($exam), [
            'answers' => $this->answerPayload($exam['question'], '0'),
            'finish_category' => true,
        ]);

        $response->assertOk()->assertJson([
            'status' => 'success',
            'saved_count' => 1,
            'category_finished' => true,
        ]);
        $this->assertDatabaseHas('user_answers', [
            'participant_id' => $exam['participant']->id,
            'question_bank_id' => $exam['question']->id,
            'answer' => '"0"',
        ]);
        $this->assertNotNull($exam['status']->fresh()->finished_at);
    }

    public function test_retry_after_finish_is_successful_and_does_not_change_final_answer(): void
    {
        $exam = $this->makeExam();
        $url = $this->submitUrl($exam);

        $this->actingAs($exam['user'])->postJson($url, [
            'answers' => $this->answerPayload($exam['question'], '0'),
            'finish_category' => true,
        ])->assertOk();

        $response = $this->postJson($url, [
            'answers' => $this->answerPayload($exam['question'], '1'),
            'finish_category' => true,
        ]);

        $response->assertOk()->assertJson([
            'status' => 'success',
            'already_finished' => true,
            'category_finished' => true,
        ]);
        $this->assertDatabaseCount('user_answers', 1);
        $this->assertDatabaseHas('user_answers', [
            'participant_id' => $exam['participant']->id,
            'question_bank_id' => $exam['question']->id,
            'answer' => '"0"',
        ]);
    }

    public function test_retry_still_succeeds_when_session_was_closed_after_first_commit(): void
    {
        $exam = $this->makeExam();
        $url = $this->submitUrl($exam);

        $this->actingAs($exam['user'])->postJson($url, [
            'answers' => $this->answerPayload($exam['question'], '0'),
            'finish_category' => true,
        ])->assertOk();
        $exam['session']->update(['is_active' => false]);

        $this->postJson($url, [
            'answers' => $this->answerPayload($exam['question'], '0'),
            'finish_category' => true,
        ])->assertOk()->assertJson([
            'status' => 'success',
            'already_finished' => true,
        ]);

        $this->assertDatabaseCount('user_answers', 1);
    }

    public function test_empty_submit_can_finish_category(): void
    {
        $exam = $this->makeExam();

        $this->actingAs($exam['user'])->postJson($this->submitUrl($exam), [
            'answers' => [],
            'finish_category' => true,
        ])->assertOk()->assertJson([
            'status' => 'success',
            'saved_count' => 0,
            'category_finished' => true,
        ]);

        $this->assertDatabaseCount('user_answers', 0);
        $this->assertNotNull($exam['status']->fresh()->finished_at);
    }

    public function test_question_not_assigned_to_participant_is_rejected(): void
    {
        $exam = $this->makeExam();
        $foreignQuestion = QuestionBank::create([
            'category_id' => $exam['category']->id,
            'type' => 'pilihan_ganda',
            'question_text' => 'Soal asing',
            'options' => ['A', 'B'],
            'correct_answer' => ['A'],
        ]);

        $this->actingAs($exam['user'])->postJson($this->submitUrl($exam), [
            'answers' => $this->answerPayload($foreignQuestion, '0'),
            'finish_category' => true,
        ])->assertUnprocessable();

        $this->assertDatabaseCount('user_answers', 0);
        $this->assertNull($exam['status']->fresh()->finished_at);
    }

    public function test_category_from_another_session_is_not_accessible(): void
    {
        $exam = $this->makeExam();
        $otherExam = $this->makeExam();

        $this->actingAs($exam['user'])->postJson(route('exam.submit_category', [
            'code' => $exam['session']->code,
            'id' => $otherExam['sessionCategory']->id,
        ]), [
            'answers' => [],
            'finish_category' => true,
        ])->assertNotFound();

        $this->assertDatabaseCount('user_answers', 0);
    }

    public function test_another_user_cannot_submit_for_participant(): void
    {
        $exam = $this->makeExam();
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)->postJson($this->submitUrl($exam), [
            'answers' => $this->answerPayload($exam['question'], '0'),
            'finish_category' => true,
        ])->assertNotFound();

        $this->assertDatabaseCount('user_answers', 0);
        $this->assertNull($exam['status']->fresh()->finished_at);
    }

    public function test_opening_expired_category_does_not_finish_it_before_browser_submit(): void
    {
        $exam = $this->makeExam(now()->subMinutes(10), 1);

        $this->actingAs($exam['user'])
            ->get(route('exam.main', [
                'code' => $exam['session']->code,
                'id' => $exam['sessionCategory']->id,
            ]))
            ->assertOk()
            ->assertViewHas('remainingSeconds', 0);

        $this->assertNull($exam['status']->fresh()->finished_at);
    }

    public function test_exam_page_contains_scoped_draft_and_safe_submit_contract(): void
    {
        $exam = $this->makeExam();

        $response = $this->actingAs($exam['user'])->get(route('exam.main', [
            'code' => $exam['session']->code,
            'id' => $exam['sessionCategory']->id,
        ]));

        $response->assertOk()
            ->assertSee("exam_draft_v1:{$exam['participant']->id}:{$exam['sessionCategory']->id}", false)
            ->assertSee('function loadDraft()', false)
            ->assertSee('function saveDraft()', false)
            ->assertSee('function clearDraft()', false)
            ->assertSee('response.ok && data?.status === \'success\'', false)
            ->assertSee('Jawaban Anda masih tersimpan di perangkat ini.', false);
    }

    public function test_categories_page_contains_safe_finish_session_contract(): void
    {
        $exam = $this->makeExam();
        $exam['status']->update(['finished_at' => now()]);

        $this->actingAs($exam['user'])
            ->get(route('exam.categories', $exam['session']->code))
            ->assertOk()
            ->assertSee('id="finishSessionButton"', false)
            ->assertSee('response.ok && data?.status === \'success\'', false)
            ->assertSee("confirmButtonText: 'Coba Lagi'", false);
    }

    private function makeExam($categoryStartedAt = null, int $duration = 60): array
    {
        $user = User::factory()->create();
        $session = ExamSession::create([
            'name' => 'Tryout Recovery',
            'code' => Str::upper(Str::random(10)),
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
            'is_active' => true,
        ]);
        $category = Category::create([
            'name' => 'Matematika '.Str::random(5),
            'slug' => Str::lower(Str::random(10)),
        ]);
        $sessionCategory = ExamSessionCategory::create([
            'exam_session_id' => $session->id,
            'category_id' => $category->id,
            'duration' => $duration,
            'total_questions' => 1,
            'max_score_raw' => 1,
            'max_score_irt' => 1000,
        ]);
        $question = QuestionBank::create([
            'category_id' => $category->id,
            'type' => 'pilihan_ganda',
            'question_text' => 'Dua tambah dua?',
            'options' => ['4', '5'],
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
            'access_code' => Str::upper(Str::random(6)),
            'started_at' => now()->subHour(),
        ]);
        $participant->questions()->attach($question->id, ['order' => 1]);
        $status = ParticipantCategoryStatus::create([
            'exam_session_participant_id' => $participant->id,
            'exam_session_category_id' => $sessionCategory->id,
            'started_at' => $categoryStartedAt ?? now()->subMinute(),
        ]);

        return compact('user', 'session', 'category', 'sessionCategory', 'question', 'participant', 'status');
    }

    private function submitUrl(array $exam): string
    {
        return route('exam.submit_category', [
            'code' => $exam['session']->code,
            'id' => $exam['sessionCategory']->id,
        ]);
    }

    private function answerPayload(QuestionBank $question, mixed $answer): array
    {
        return [
            (string) $question->id => [
                'answer' => $answer,
                'is_doubtful' => false,
            ],
        ];
    }
}
