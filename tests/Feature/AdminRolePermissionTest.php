<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ExamSession;
use App\Models\ExamSessionCategory;
use App\Models\ExamSessionParticipant;
use App\Models\QuizSetting;
use App\Models\User;
use App\Models\UserXp;
use App\Services\QuizService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $adminSesiUser;
    protected User $superAdmin;
    protected ExamSession $assignedSession;
    protected ExamSession $unassignedSession;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['role' => 'superadmin']);
        $this->adminUser = User::factory()->create(['role' => 'admin']);
        $this->adminSesiUser = User::factory()->create(['role' => 'admin_sesi']);

        $category = Category::create(['name' => 'Matematika', 'slug' => 'matematika']);

        $this->assignedSession = ExamSession::create([
            'name' => 'Sesi Ditugaskan',
            'code' => 'ASSIGN01',
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-10',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'is_active' => true,
        ]);
        ExamSessionCategory::create([
            'exam_session_id' => $this->assignedSession->id,
            'category_id' => $category->id,
            'duration' => 60,
        ]);

        $this->unassignedSession = ExamSession::create([
            'name' => 'Sesi Rahasia',
            'code' => 'SECRET01',
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-10',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'is_active' => true,
        ]);
        ExamSessionCategory::create([
            'exam_session_id' => $this->unassignedSession->id,
            'category_id' => $category->id,
            'duration' => 60,
        ]);

        // Tugaskan adminUser ke assignedSession
        ExamSessionParticipant::create([
            'exam_session_id' => $this->assignedSession->id,
            'user_id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'whatsapp' => '08123456789',
            'access_code' => 'ADMIN01',
        ]);
    }

    /** 1. Test redirect login untuk role admin */
    public function test_admin_redirects_to_sessions_index_after_login(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/');
        $response->assertRedirect(route('admin.sessions.index'));
    }

    /** 2. Test role admin hanya melihat sesi yang ditugaskan */
    public function test_admin_only_sees_assigned_sessions(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.sessions.index'));
        $response->assertOk();
        $response->assertSee('Sesi Ditugaskan');
        $response->assertDontSee('Sesi Rahasia');
    }

    /** 3. Test role admin dapat melihat detail sesi yang ditugaskan */
    public function test_admin_can_view_assigned_session_details(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.sessions.show', $this->assignedSession->id));
        $response->assertOk();
        $response->assertSee('Daftar Peserta');
    }

    /** 4. Test role admin TIDAK BISA melihat tombol Tambah Peserta dan tombol Hapus di halaman detail */
    public function test_admin_does_not_see_add_participant_buttons_and_delete_action(): void
    {
        // Tambahkan satu peserta siswa
        $student = User::factory()->create(['name' => 'Budi Santoso', 'role' => 'basic']);
        ExamSessionParticipant::create([
            'exam_session_id' => $this->assignedSession->id,
            'user_id' => $student->id,
            'name' => $student->name,
            'whatsapp' => '08123456789',
            'access_code' => 'BUDI01',
            'privilege' => 'general',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.sessions.show', $this->assignedSession->id));
        $response->assertOk();
        $response->assertDontSee('onclick="openParticipantModal()"', false);
        $response->assertDontSee('onclick="openNewUserModal()"', false);
        $response->assertDontSee('<th style="width: 80px; text-align: center;">AKSI</th>', false);
    }

    /** 5. Test proteksi backend: admin DITOLAK saat menambahkan user lama (POST /admin/session-participants) */
    public function test_admin_cannot_store_existing_participant(): void
    {
        $student = User::factory()->create(['role' => 'basic']);

        $response = $this->actingAs($this->adminUser)->postJson(route('admin.session-participants.store'), [
            'exam_session_id' => $this->assignedSession->id,
            'user_ids' => [$student->id],
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('exam_session_participants', [
            'exam_session_id' => $this->assignedSession->id,
            'user_id' => $student->id,
        ]);
    }

    /** 6. Test proteksi backend: admin DITOLAK saat membuat user baru (POST /admin/session-participants/new) */
    public function test_admin_cannot_store_new_user_participant(): void
    {
        $response = $this->actingAs($this->adminUser)->postJson(route('admin.session-participants.store-new'), [
            'exam_session_id' => $this->assignedSession->id,
            'name' => 'Siswa Baru',
            'email' => 'siswabaru@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('users', ['email' => 'siswabaru@test.com']);
    }

    /** 7. Test proteksi backend: admin DITOLAK saat menghapus peserta */
    public function test_admin_cannot_delete_participant(): void
    {
        $student = User::factory()->create(['role' => 'basic']);
        $participant = ExamSessionParticipant::create([
            'exam_session_id' => $this->assignedSession->id,
            'user_id' => $student->id,
            'name' => $student->name,
            'whatsapp' => '08123456789',
            'access_code' => 'STU001',
        ]);

        $response = $this->actingAs($this->adminUser)->deleteJson(route('admin.session-participants.destroy', $participant->id));
        $response->assertStatus(403);
        $this->assertDatabaseHas('exam_session_participants', ['id' => $participant->id]);
    }

    /** 8. Test proteksi backend: admin DITOLAK saat mengubah privilege peserta */
    public function test_admin_cannot_update_participant_privilege(): void
    {
        $student = User::factory()->create(['role' => 'basic']);
        $participant = ExamSessionParticipant::create([
            'exam_session_id' => $this->assignedSession->id,
            'user_id' => $student->id,
            'name' => $student->name,
            'whatsapp' => '08123456789',
            'access_code' => 'STU002',
            'privilege' => 'general',
        ]);

        $response = $this->actingAs($this->adminUser)->patchJson(route('admin.session-participants.update-privilege', $participant->id), [
            'privilege' => 'premium',
        ]);

        $response->assertStatus(403);
        $this->assertEquals('general', $participant->fresh()->privilege);
    }

    /** 9. Test role admin DITOLAK saat mencoba akses menu Superadmin */
    public function test_admin_cannot_access_superadmin_menus(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.categories.index'));
        $response->assertForbidden();

        $responseQuestions = $this->actingAs($this->adminUser)->get(route('admin.questions.index'));
        $responseQuestions->assertForbidden();

        $responseParticipants = $this->actingAs($this->adminUser)->get(route('admin.participants.index'));
        $responseParticipants->assertForbidden();
    }

    /** 10. Test role admin tidak masuk ke dalam leaderboard mingguan */
    public function test_admin_role_excluded_from_weekly_leaderboard(): void
    {
        QuizSetting::first()->update([
            'last_weekly_reset_at' => now(),
        ]);

        UserXp::create([
            'user_id' => $this->adminUser->id,
            'total_xp' => 9999,
        ]);

        $student = User::factory()->create([
            'name' => 'Siswa Juara',
            'role' => 'basic',
            'jenjang' => 'SMA',
        ]);
        UserXp::create([
            'user_id' => $student->id,
            'total_xp' => 100,
        ]);

        $service = app(QuizService::class);
        $leaderboard = $service->getWeeklyLeaderboardData($student);

        $this->assertCount(1, $leaderboard['topUsers']);
        $this->assertEquals($student->id, $leaderboard['topUsers']->first()->user_id);
        $this->assertFalse($leaderboard['topUsers']->contains('user_id', $this->adminUser->id));
    }

    /** 11. Test Superadmin dapat membuat user dengan role admin */
    public function test_superadmin_can_create_user_with_role_admin(): void
    {
        $response = $this->actingAs($this->superAdmin)->postJson(route('admin.participants.store'), [
            'name' => 'Admin Baru',
            'email' => 'adminbaru@test.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => 'adminbaru@test.com',
            'role' => 'admin',
        ]);
    }
}
