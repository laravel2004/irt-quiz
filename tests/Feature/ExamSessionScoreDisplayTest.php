<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Models\QuestionBank;
use App\Models\User;
use App\Services\ExamSessionService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamSessionScoreDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_raw_predicate_and_score_visibility_follow_session_mode(): void
    {
        $session = new ExamSession([
            'predicate_raw_kurang_min' => 0,
            'predicate_raw_memadai_min' => 50,
            'predicate_raw_baik_min' => 70,
            'predicate_raw_istimewa_min' => 85,
        ]);

        $this->assertSame('Kurang', $session->predicateForRawScore(49.99));
        $this->assertSame('Memadai', $session->predicateForRawScore(50));
        $this->assertSame('Baik', $session->predicateForRawScore(70));
        $this->assertSame('Istimewa', $session->predicateForRawScore(85));

        $session->participant_score_display = 'raw';
        $this->assertTrue($session->showsRawScoreToParticipant());
        $this->assertFalse($session->showsIrtScoreToParticipant());

        $session->participant_score_display = 'irt';
        $this->assertFalse($session->showsRawScoreToParticipant());
        $this->assertTrue($session->showsIrtScoreToParticipant());

        $session->participant_score_display = 'both';
        $this->assertTrue($session->showsRawScoreToParticipant());
        $this->assertTrue($session->showsIrtScoreToParticipant());
    }

    public function test_admin_can_store_score_display_and_raw_predicates(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin']);
        $category = $this->makeCategoryWithQuestions();

        $this->actingAs($admin)
            ->postJson(route('admin.sessions.store'), $this->sessionData($category->id))
            ->assertCreated();

        $this->assertDatabaseHas('exam_sessions', [
            'participant_score_display' => 'both',
            'predicate_raw_kurang_min' => 0,
            'predicate_raw_memadai_min' => 50,
            'predicate_raw_baik_min' => 70,
            'predicate_raw_istimewa_min' => 85,
        ]);
    }

    public function test_admin_form_exposes_score_display_and_raw_predicate_inputs(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin']);

        $this->actingAs($admin)
            ->get(route('admin.sessions.index'))
            ->assertOk()
            ->assertSee('Skor yang Ditampilkan ke Peserta')
            ->assertSee('participantScoreDisplay')
            ->assertSee('Ambang Predikat Raw')
            ->assertSee('predicateRawIstimewaMin');
    }

    public function test_backend_rejects_invalid_mode_and_raw_predicates(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin']);
        $category = $this->makeCategoryWithQuestions();
        $data = $this->sessionData($category->id);
        $data['participant_score_display'] = 'all';
        $data['predicate_raw_baik_min'] = 40;

        $this->actingAs($admin)
            ->postJson(route('admin.sessions.store'), $data)
            ->assertUnprocessable();

        $this->assertDatabaseCount('exam_sessions', 0);
    }

    public function test_started_session_allows_score_display_and_predicate_changes(): void
    {
        $category = $this->makeCategoryWithQuestions();
        $data = $this->sessionData($category->id) + ['code' => 'DISPLAY1'];
        $service = app(ExamSessionService::class);
        $session = $service->createWithCategories($data);

        ExamSessionParticipant::create([
            'exam_session_id' => $session->id,
            'name' => 'Peserta Aktif',
            'whatsapp' => '081234567890',
            'access_code' => 'DISPLAY',
            'started_at' => now(),
        ]);
        $data['participant_score_display'] = 'raw';
        $data['predicate_raw_memadai_min'] = 45;
        $data['predicate_memadai_min'] = 450;

        $updatedSession = $service->updateWithCategories($session->id, $data);

        $this->assertSame('raw', $updatedSession->participant_score_display);
        $this->assertEquals(45, $updatedSession->predicate_raw_memadai_min);
        $this->assertEquals(450, $updatedSession->predicate_memadai_min);

        $this->assertDatabaseHas('exam_sessions', [
            'id' => $session->id,
            'participant_score_display' => 'raw',
            'predicate_raw_memadai_min' => 45,
            'predicate_memadai_min' => 450,
        ]);
    }

    private function makeCategoryWithQuestions(): Category
    {
        $category = Category::create(['name' => 'Matematika', 'slug' => 'matematika-display']);

        foreach (range(1, 2) as $number) {
            QuestionBank::create([
                'category_id' => $category->id,
                'type' => 'pilihan_ganda',
                'question_text' => "Soal {$number}",
                'options' => ['A' => 'Benar', 'B' => 'Salah'],
                'correct_answer' => ['A'],
                'score_correct' => 1,
                'score_incorrect' => 0,
            ]);
        }

        return $category;
    }

    private function sessionData(int $categoryId): array
    {
        return [
            'name' => 'Sesi Tampilan Skor',
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-10',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'is_lock_quiz' => false,
            'participant_score_display' => 'both',
            'predicate_raw_kurang_min' => 0,
            'predicate_raw_memadai_min' => 50,
            'predicate_raw_baik_min' => 70,
            'predicate_raw_istimewa_min' => 85,
            'predicate_kurang_min' => 100,
            'predicate_memadai_min' => 500,
            'predicate_baik_min' => 700,
            'predicate_istimewa_min' => 850,
            'categories' => [[
                'id' => $categoryId,
                'duration' => 60,
                'total_questions' => 2,
                'max_score_raw' => 100,
                'min_score_irt' => 100,
                'max_score_irt' => 1000,
                'sub_categories' => [],
            ]],
        ];
    }
}
