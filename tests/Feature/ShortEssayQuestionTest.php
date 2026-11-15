<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ExamSession;
use App\Models\ExamSessionCategory;
use App\Models\ExamSessionParticipant;
use App\Models\QuestionBank;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizSetting;
use App\Models\SubCategory;
use App\Models\User;
use App\Models\UserAnswer;
use App\Services\AssessmentService;
use App\Services\QuizAnswerChecker;
use App\Services\QuizService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShortEssayQuestionTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected Category $category;
    protected SubCategory $subCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['role' => 'superadmin']);
        $this->category = Category::create(['name' => 'Bahasa Indonesia', 'slug' => 'bahasa-indonesia']);
        $this->subCategory = SubCategory::create([
            'category_id' => $this->category->id,
            'name' => 'Tata Bahasa',
            'slug' => 'tata-bahasa',
        ]);
    }

    /** 1. Test Superadmin dapat membuat soal essai singkat via API / Controller */
    public function test_superadmin_can_create_short_essay_question(): void
    {
        $response = $this->actingAs($this->superAdmin)->postJson(route('admin.questions.store'), [
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'kode_soal' => 'ESSAY-01',
            'type' => 'essai_singkat',
            'question_text' => 'Ibu kota negara Indonesia adalah...',
            'options' => [],
            'correct_answer' => ['Jakarta'],
            'score_correct' => 1,
            'score_incorrect' => 0,
            'explanation' => 'Ibu kota Indonesia adalah Jakarta.',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('question_banks', [
            'kode_soal' => 'ESSAY-01',
            'type' => 'essai_singkat',
        ]);
    }

    /** 2. Test QuizAnswerChecker: Jawaban persis, beda case, beda spasi, dan salah */
    public function test_quiz_answer_checker_evaluates_short_essay_accurately(): void
    {
        $checker = app(QuizAnswerChecker::class);

        $quiz = new Quiz([
            'type' => 'essai_singkat',
            'correct_answer' => ['Bhinneka Tunggal Ika'],
        ]);

        // A. Persis sama -> Benar
        $this->assertTrue($checker->isCorrect($quiz, 'Bhinneka Tunggal Ika'));

        // B. Huruf kecil semua -> Benar
        $this->assertTrue($checker->isCorrect($quiz, 'bhinneka tunggal ika'));

        // C. Huruf kapital semua -> Benar
        $this->assertTrue($checker->isCorrect($quiz, 'BHINNEKA TUNGGAL IKA'));

        // D. Spasi di awal & akhir -> Benar
        $this->assertTrue($checker->isCorrect($quiz, '   Bhinneka Tunggal Ika   '));

        // E. Spasi ganda di tengah kalimat -> Benar
        $this->assertTrue($checker->isCorrect($quiz, 'bhinneka    tunggal   ika'));

        // F. Jawaban typo / salah -> Salah
        $this->assertFalse($checker->isCorrect($quiz, 'Bhinneka Ika'));

        // G. Jawaban kosong / spasi saja -> Salah
        $this->assertFalse($checker->isCorrect($quiz, ''));
        $this->assertFalse($checker->isCorrect($quiz, '   '));
    }

    /** 3. Test AssessmentService penilaian sesi ujian untuk soal essai singkat */
    public function test_assessment_service_scores_short_essay_correctly(): void
    {
        $question = QuestionBank::create([
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'kode_soal' => 'ESSAY-02',
            'type' => 'essai_singkat',
            'question_text' => 'Lambang negara Indonesia adalah burung...',
            'options' => [],
            'correct_answer' => ['Garuda'],
            'score_correct' => 5,
            'score_incorrect' => 0,
        ]);

        $session = ExamSession::create([
            'name' => 'Sesi Ujian Essay',
            'code' => 'ESSAY001',
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-10',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'is_active' => true,
        ]);

        ExamSessionCategory::create([
            'exam_session_id' => $session->id,
            'category_id' => $this->category->id,
            'duration' => 60,
        ]);
        $session->questions()->attach($question->id);

        $participantBenar = ExamSessionParticipant::create([
            'exam_session_id' => $session->id,
            'user_id' => User::factory()->create()->id,
            'name' => 'Siswa Benar',
            'whatsapp' => '08123456789',
            'access_code' => 'BENAR01',
            'finished_at' => now(),
        ]);

        $participantSalah = ExamSessionParticipant::create([
            'exam_session_id' => $session->id,
            'user_id' => User::factory()->create()->id,
            'name' => 'Siswa Salah',
            'whatsapp' => '08123456788',
            'access_code' => 'SALAH01',
            'finished_at' => now(),
        ]);

        // Siswa Benar menjawab dengan spasi berlebih dan lowercase: "  garuda  "
        UserAnswer::create([
            'exam_session_id' => $session->id,
            'participant_id' => $participantBenar->id,
            'question_bank_id' => $question->id,
            'answer' => '  garuda  ',
        ]);

        // Siswa Salah menjawab "Elang"
        UserAnswer::create([
            'exam_session_id' => $session->id,
            'participant_id' => $participantSalah->id,
            'question_bank_id' => $question->id,
            'answer' => 'Elang',
        ]);

        $assessmentService = app(AssessmentService::class);
        $assessmentService->calculateIRT($session->id);

        $answerBenar = UserAnswer::where('participant_id', $participantBenar->id)->first();
        $this->assertTrue((bool) $answerBenar->is_correct);
        $this->assertEquals(5, $answerBenar->score);

        $answerSalah = UserAnswer::where('participant_id', $participantSalah->id)->first();
        $this->assertFalse((bool) $answerSalah->is_correct);
        $this->assertEquals(0, $answerSalah->score);
    }

    /** 4. Test QuizService: submit answer untuk quiz bertipe essai_singkat */
    public function test_quiz_service_submits_short_essay_answer(): void
    {
        QuizSetting::first()->update([
            'xp_per_correct' => 10,
        ]);

        $student = User::factory()->create(['role' => 'basic']);

        $quiz = Quiz::create([
            'jenjang' => 'SMA',
            'type' => 'essai_singkat',
            'question_text' => 'Presiden pertama RI adalah...',
            'options' => [],
            'correct_answer' => ['Soekarno'],
            'score_correct' => 1,
            'score_incorrect' => 0,
        ]);

        $service = app(QuizService::class);
        $attempt = QuizAttempt::create([
            'user_id' => $student->id,
            'quiz_id' => $quiz->id,
            'period_key' => $service->currentPeriodKey(),
            'assigned_date' => now()->toDateString(),
        ]);

        // Jawab dengan beda huruf kapital dan spasi
        $result = $service->submitAnswer($student, $attempt, '   soekarno   ');

        $this->assertTrue($result['is_correct']);
        $this->assertEquals(10, $result['xp_earned']);

        $attempt->refresh();
        $this->assertTrue((bool) $attempt->is_correct);
        $this->assertEquals(10, $attempt->xp_earned);
        $this->assertEquals('   soekarno   ', $attempt->answer);
    }
}
