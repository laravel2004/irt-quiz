<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ExamSessionParticipant;
use App\Models\QuestionBank;
use App\Models\UserAnswer;
use App\Services\AssessmentService;
use App\Services\ExamSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentIrtBoundsTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_wrong_and_blank_participants_receive_the_lower_irt_bound(): void
    {
        [$session, $questions] = $this->makeSession();
        $wrong = $this->makeParticipant($session->id, $questions, 'WRONG1');
        $blank = $this->makeParticipant($session->id, $questions, 'BLANK1');

        foreach ($questions as $question) {
            UserAnswer::create([
                'participant_id' => $wrong->id,
                'exam_session_id' => $session->id,
                'question_bank_id' => $question->id,
                'answer' => 1,
            ]);
        }

        app(AssessmentService::class)->calculateIRT($session->id);

        $this->assertSame(200.0, (float) $wrong->result()->value('irt_score'));
        $this->assertSame(200.0, (float) $blank->result()->value('irt_score'));
        $this->assertSame(0.0, (float) $wrong->result()->value('score'));
    }

    public function test_all_correct_participant_receives_the_upper_irt_bound(): void
    {
        [$session, $questions] = $this->makeSession();
        $participant = $this->makeParticipant($session->id, $questions, 'RIGHT1');

        foreach ($questions as $question) {
            UserAnswer::create([
                'participant_id' => $participant->id,
                'exam_session_id' => $session->id,
                'question_bank_id' => $question->id,
                'answer' => 0,
            ]);
        }

        app(AssessmentService::class)->calculateIRT($session->id);

        $this->assertSame(1000.0, (float) $participant->result()->value('irt_score'));
    }

    public function test_partial_performance_stays_between_the_irt_bounds(): void
    {
        [$session, $questions] = $this->makeSession();
        $participant = $this->makeParticipant($session->id, $questions, 'PART01');

        UserAnswer::create([
            'participant_id' => $participant->id,
            'exam_session_id' => $session->id,
            'question_bank_id' => $questions[0]->id,
            'answer' => 0,
        ]);
        UserAnswer::create([
            'participant_id' => $participant->id,
            'exam_session_id' => $session->id,
            'question_bank_id' => $questions[1]->id,
            'answer' => 1,
        ]);

        app(AssessmentService::class)->calculateIRT($session->id);

        $score = (float) $participant->result()->value('irt_score');
        $this->assertGreaterThan(200, $score);
        $this->assertLessThan(1000, $score);
        $this->assertSame($score, (float) $participant->result()->first()->categoryResults()->value('irt_score'));

        app(AssessmentService::class)->calculateIRT($session->id);
        $this->assertSame($score, (float) $participant->result()->value('irt_score'));
    }

    public function test_negative_incorrect_score_cannot_reduce_irt_below_the_lower_bound(): void
    {
        [$session, $questions] = $this->makeSession();
        $questions->each->update(['score_incorrect' => -1]);
        $participant = $this->makeParticipant($session->id, $questions, 'NEG001');

        foreach ($questions as $question) {
            UserAnswer::create([
                'participant_id' => $participant->id,
                'exam_session_id' => $session->id,
                'question_bank_id' => $question->id,
                'answer' => 1,
            ]);
        }

        app(AssessmentService::class)->calculateIRT($session->id);

        $this->assertSame(200.0, (float) $participant->result()->value('irt_score'));
    }

    private function makeSession(): array
    {
        $category = Category::create(['name' => 'Matematika', 'slug' => 'matematika']);
        $questions = collect(range(1, 2))->map(fn ($number) => QuestionBank::create([
            'category_id' => $category->id,
            'type' => 'pilihan_ganda',
            'question_text' => "Soal {$number}",
            'options' => ['Benar', 'Salah'],
            'correct_answer' => ['A'],
            'score_correct' => 1,
            'score_incorrect' => 0,
        ]));

        $session = app(ExamSessionService::class)->createWithCategories([
            'name' => 'Sesi Penilaian',
            'code' => 'NILAI01',
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-10',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'is_lock_quiz' => false,
            'predicate_kurang_min' => 200,
            'predicate_memadai_min' => 500,
            'predicate_baik_min' => 700,
            'predicate_istimewa_min' => 850,
            'categories' => [[
                'id' => $category->id,
                'duration' => 60,
                'total_questions' => 2,
                'max_score_raw' => 100,
                'min_score_irt' => 200,
                'max_score_irt' => 1000,
                'sub_categories' => [],
            ]],
        ]);

        return [$session, $questions->values()];
    }

    private function makeParticipant(int $sessionId, $questions, string $accessCode): ExamSessionParticipant
    {
        $participant = ExamSessionParticipant::create([
            'exam_session_id' => $sessionId,
            'name' => $accessCode,
            'whatsapp' => '081234567890',
            'access_code' => $accessCode,
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
        ]);
        $participant->questions()->attach($questions->pluck('id')->all());

        return $participant;
    }
}
