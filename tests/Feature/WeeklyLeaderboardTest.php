<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserXp;
use App\Models\QuizSetting;
use App\Services\QuizService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WeeklyLeaderboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        QuizSetting::first()->update([
            'last_weekly_reset_at' => now(),
        ]);
    }

    public function test_leaderboard_displays_top_10_users_ordered_by_xp(): void
    {
        // Buat 12 user peserta dengan perolehan XP bervariasi
        $users = [];
        for ($i = 1; $i <= 12; $i++) {
            $user = User::factory()->create([
                'name' => "Peserta {$i}",
                'role' => 'participant',
                'jenjang' => 'SMA',
            ]);
            UserXp::create([
                'user_id' => $user->id,
                'total_xp' => $i * 10,
            ]);
            $users[] = $user;
        }

        $current = $users[0]; // Peserta 1 (XP: 10, rank ke-12)
        $this->actingAs($current);

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Leaderboard Mingguan');
        // Top 10 harus memuat Peserta 12 (120 XP) s/d Peserta 3 (30 XP)
        $response->assertSee('Peserta 12');
        $response->assertSee('Peserta 11');
        $response->assertSee('Peserta 3');
        // Peserta 1 di luar top 10 -> harus tampil di box posisi
        $response->assertSee('Posisi Kamu: #12');
    }

    public function test_admin_and_superadmin_excluded_from_leaderboard(): void
    {
        $superadmin = User::factory()->create(['name' => 'Superadmin Bos', 'role' => 'superadmin']);
        UserXp::create(['user_id' => $superadmin->id, 'total_xp' => 9999]);

        $participant = User::factory()->create(['name' => 'Siswa Juara', 'role' => 'participant', 'jenjang' => 'SMA']);
        UserXp::create(['user_id' => $participant->id, 'total_xp' => 100]);

        $this->actingAs($participant);
        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Siswa Juara');
        $response->assertDontSee('Superadmin Bos');
    }

    public function test_automatic_xp_reset_after_7_days(): void
    {
        $user = User::factory()->create(['role' => 'participant', 'jenjang' => 'SMA']);
        UserXp::create(['user_id' => $user->id, 'total_xp' => 150]);

        // Set last_weekly_reset_at ke 8 hari yang lalu
        QuizSetting::first()->update([
            'last_weekly_reset_at' => now()->subDays(8),
        ]);

        $service = app(QuizService::class);
        $didReset = $service->checkAndResetWeeklyXp();

        $this->assertTrue($didReset);
        $this->assertEquals(0, UserXp::where('user_id', $user->id)->value('total_xp'));
        $this->assertTrue(QuizSetting::first()->last_weekly_reset_at->isToday());
    }

    public function test_artisan_command_resets_weekly_xp_with_force(): void
    {
        $user = User::factory()->create(['role' => 'participant', 'jenjang' => 'SMA']);
        UserXp::create(['user_id' => $user->id, 'total_xp' => 200]);

        $this->artisan('leaderboard:reset-weekly --force')
            ->expectsOutput('Memaksa reset XP mingguan...')
            ->expectsOutput('Seluruh XP user berhasil direset ke 0.')
            ->assertExitCode(0);

        $this->assertEquals(0, UserXp::where('user_id', $user->id)->value('total_xp'));
    }

    public function test_empty_leaderboard_displays_placeholder(): void
    {
        $user = User::factory()->create(['role' => 'participant', 'jenjang' => 'SMA']);

        $this->actingAs($user);
        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Belum Ada Peringkat Minggu Ini');
    }

    public function test_leaderboard_only_displays_users_with_same_jenjang_as_current_user(): void
    {
        $userSma = User::factory()->create([
            'name' => 'Budi SMA',
            'role' => 'participant',
            'jenjang' => 'SMA',
        ]);
        UserXp::create(['user_id' => $userSma->id, 'total_xp' => 100]);

        $userSmp = User::factory()->create([
            'name' => 'Andi SMP',
            'role' => 'participant',
            'jenjang' => 'SMP',
        ]);
        UserXp::create(['user_id' => $userSmp->id, 'total_xp' => 200]);

        // Login as Budi (SMA) -> hanya melihat Budi SMA, tidak melihat Andi SMP
        $this->actingAs($userSma);
        $resSma = $this->get('/dashboard');
        $resSma->assertStatus(200);
        $resSma->assertSee('Budi SMA');
        $resSma->assertDontSee('Andi SMP');
        $resSma->assertSee('Jenjang SMA');

        // Login as Andi (SMP) -> hanya melihat Andi SMP, tidak melihat Budi SMA
        $this->actingAs($userSmp);
        $resSmp = $this->get('/dashboard');
        $resSmp->assertStatus(200);
        $resSmp->assertSee('Andi SMP');
        $resSmp->assertDontSee('Budi SMA');
        $resSmp->assertSee('Jenjang SMP');
    }
}
