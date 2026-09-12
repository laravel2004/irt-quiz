<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ExamResult;
use App\Models\ExamSession;
use App\Models\ExamSessionCategory;
use App\Models\ExamSessionParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminScoreVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_roles_keep_raw_score_and_can_audit_irt_configuration(): void
    {
        $session = $this->makeSessionWithResult();

        foreach (['superadmin', 'admin_sesi'] as $role) {
            $admin = User::factory()->create(['role' => $role]);

            $this->actingAs($admin)
                ->get(route('admin.sessions.show', $session->id))
                ->assertOk()
                ->assertSee('SKOR RAW')
                ->assertSee('PREDIKAT RAW')
                ->assertSee('PREDIKAT IRT')
                ->assertSee('Ambang Predikat Raw')
                ->assertSee('Batas IRT')
                ->assertSee('Ambang Predikat IRT');
        }

        $participant = User::factory()->create(['role' => 'basic']);
        $this->actingAs($participant)
            ->get(route('admin.sessions.show', $session->id))
            ->assertForbidden();
    }

    public function test_admin_csv_keeps_raw_and_irt_scores(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin']);
        $session = $this->makeSessionWithResult();

        $content = $this->actingAs($admin)
            ->get(route('admin.sessions.export', $session->id))
            ->streamedContent();

        $this->assertStringContainsString('Skor Raw', $content);
        $this->assertStringContainsString('Predikat Raw', $content);
        $this->assertStringContainsString('Skor IRT', $content);
        $this->assertStringContainsString('Predikat IRT', $content);
        $this->assertStringContainsString('Baik', $content);
        $this->assertStringContainsString('Istimewa', $content);
        $this->assertStringContainsString('42.5', $content);
        $this->assertStringContainsString('900', $content);
    }

    private function makeSessionWithResult(): ExamSession
    {
        $category = Category::create(['name' => 'Matematika', 'slug' => 'matematika']);
        $session = ExamSession::create([
            'name' => 'Sesi Admin',
            'code' => 'ADMIN001',
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-10',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'is_active' => true,
            'participant_score_display' => 'raw',
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
        $participant = ExamSessionParticipant::create([
            'exam_session_id' => $session->id,
            'name' => 'Peserta',
            'whatsapp' => '081234567890',
            'access_code' => 'ADM001',
            'finished_at' => now(),
        ]);
        ExamResult::create([
            'participant_id' => $participant->id,
            'exam_session_id' => $session->id,
            'total_correct' => 1,
            'total_incorrect' => 0,
            'total_blank' => 0,
            'score' => 42.5,
            'irt_score' => 900,
        ]);

        return $session;
    }
}
