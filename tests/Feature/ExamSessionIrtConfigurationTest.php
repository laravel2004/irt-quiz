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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExamSessionIrtConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_store_irt_bounds_and_predicate_thresholds(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin']);
        $category = $this->makeCategoryWithQuestions();

        $response = $this->actingAs($admin)->postJson(route('admin.sessions.store'), $this->sessionData($category->id));

        $response->assertCreated();
        $this->assertDatabaseHas('exam_sessions', [
            'predicate_kurang_min' => 100,
            'predicate_memadai_min' => 500,
            'predicate_baik_min' => 700,
            'predicate_istimewa_min' => 850,
        ]);
        $this->assertDatabaseHas('exam_session_categories', [
            'category_id' => $category->id,
            'min_score_irt' => 100,
            'max_score_irt' => 1000,
        ]);
    }

    public function test_admin_form_exposes_irt_bound_and_predicate_inputs(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin']);

        $this->actingAs($admin)
            ->get(route('admin.sessions.index'))
            ->assertOk()
            ->assertSee('Batas Bawah IRT')
            ->assertSee('Batas Atas IRT')
            ->assertSee('predicateKurangMin')
            ->assertSee('predicateIstimewaMin');
    }

    public function test_backend_rejects_invalid_irt_bounds_and_predicate_order(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin']);
        $category = $this->makeCategoryWithQuestions();
        $data = $this->sessionData($category->id);
        $data['categories'][0]['min_score_irt'] = 1000;
        $data['predicate_memadai_min'] = 900;
        $data['predicate_baik_min'] = 700;

        $response = $this->actingAs($admin)->postJson(route('admin.sessions.store'), $data);

        $response->assertUnprocessable();
        $this->assertDatabaseCount('exam_sessions', 0);
    }

    public function test_started_session_rejects_scoring_configuration_changes(): void
    {
        $category = $this->makeCategoryWithQuestions();
        $service = app(ExamSessionService::class);
        $data = $this->sessionData($category->id);
        $data['code'] = 'SCORE001';
        $session = $service->createWithCategories($data);

        ExamSessionParticipant::create([
            'exam_session_id' => $session->id,
            'name' => 'Peserta Aktif',
            'whatsapp' => '081234567890',
            'access_code' => 'SC0001',
            'started_at' => now(),
        ]);

        $data['categories'][0]['min_score_irt'] = 200;
        $data['predicate_kurang_min'] = 200;

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Konfigurasi nilai tidak dapat diubah karena sesi sudah mulai dikerjakan.');

        $service->updateWithCategories($session->id, $data);
    }

    public function test_predicate_uses_inclusive_minimum_thresholds(): void
    {
        $session = new ExamSession([
            'predicate_kurang_min' => 100,
            'predicate_memadai_min' => 500,
            'predicate_baik_min' => 700,
            'predicate_istimewa_min' => 850,
        ]);

        $this->assertSame('Kurang', $session->predicateForIrtScore(100));
        $this->assertSame('Kurang', $session->predicateForIrtScore(499.99));
        $this->assertSame('Memadai', $session->predicateForIrtScore(500));
        $this->assertSame('Memadai', $session->predicateForIrtScore(699.99));
        $this->assertSame('Baik', $session->predicateForIrtScore(700));
        $this->assertSame('Baik', $session->predicateForIrtScore(849.99));
        $this->assertSame('Istimewa', $session->predicateForIrtScore(850));
    }

    public function test_migration_backfills_legacy_sessions_and_rollback_keeps_existing_scores(): void
    {
        $category = Category::create(['name' => 'Bahasa', 'slug' => 'bahasa']);
        $migration = require base_path('database/migrations/2026_09_06_010000_add_irt_bounds_and_predicate_thresholds.php');
        $migration->down();

        $sessionId = DB::table('exam_sessions')->insertGetId([
            'name' => 'Sesi Lama',
            'code' => 'LEGACY01',
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-10',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('exam_session_categories')->insert([
            'exam_session_id' => $sessionId,
            'category_id' => $category->id,
            'duration' => 60,
            'total_questions' => 10,
            'max_score_raw' => 100,
            'max_score_irt' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration->up();

        $this->assertDatabaseHas('exam_session_categories', [
            'exam_session_id' => $sessionId,
            'min_score_irt' => 0,
        ]);
        $this->assertDatabaseHas('exam_sessions', [
            'id' => $sessionId,
            'predicate_kurang_min' => 0,
            'predicate_memadai_min' => 500,
            'predicate_baik_min' => 700,
            'predicate_istimewa_min' => 850,
        ]);

        $migration->down();
        $this->assertTrue(Schema::hasColumns('exam_session_categories', ['max_score_raw', 'max_score_irt']));
        $this->assertFalse(Schema::hasColumn('exam_session_categories', 'min_score_irt'));
        $migration->up();
    }

    private function makeCategoryWithQuestions(): Category
    {
        $category = Category::create(['name' => 'Matematika', 'slug' => 'matematika']);

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
            'name' => 'Sesi Batas IRT',
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-10',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'is_lock_quiz' => false,
            'participant_score_display' => 'irt',
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
