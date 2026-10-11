# Rencana Implementasi: Leaderboard Mingguan Top 10 & Reset XP Otomatis (7 Hari Sekali)

Dokumen ini disusun sebagai panduan teknis langkah-demi-langkah bagi **Junior Programmer** atau **Model AI (Cost-effective / Budget AI)** untuk mengimplementasikan fitur **Leaderboard Mingguan (Top 10)** di halaman Dashboard Peserta dengan sistem reset otomatis setiap 7 hari sekali.

---

## 1. Ringkasan Fitur & Spesifikasi

### Konteks & URL Terkait
- **URL Dashboard Peserta:** `http://127.0.0.1:8000/dashboard` (Route: `participant.dashboard`)
- **Controller:** `app/Http/Controllers/Participant/DashboardController.php`
- **Service:** `app/Services/QuizService.php`
- **Model Terkait:** `app/Models/UserXp.php`, `app/Models/User.php`, `app/Models/QuizSetting.php`
- **Blade View:** `resources/views/participant/dashboard.blade.php`

### Kebutuhan yang Diinginkan
1. **Posisi Komponen:**
   - Komponen Leaderboard diletakkan **di bawah section kartu Quiz** dan **di atas section Grafik Nilai Sesi Ujian** pada halaman dashboard peserta.
2. **Kapasitas Leaderboard (Top 10) & Filter Per Jenjang:**
   - Menampilkan maksimal **10 peserta teratas** dengan perolehan XP tertinggi.
   - **Filter Berdasarkan Jenjang Peserta:** Leaderboard bersifat terisolasi per jenjang pendidikan (`SD`, `SMP`, `SMA`, dll.). Peserta yang login hanya melihat peserta lain yang memiliki jenjang yang sama (`where('jenjang', $currentUser->jenjang)`).
   - Hanya peserta (`role` peserta: `basic`, `participant`, `premium`) yang boleh masuk leaderboard. User admin (`superadmin`, `admin_sesi`) **tidak boleh** masuk ke dalam leaderboard.
   - Setiap baris menampilkan:
     - Peringkat (#1 medali emas 🥇, #2 medali perak 🥈, #3 medali perunggu 🥉, #4 s/d #10 nomor urut).
     - Nama peserta & badge jenjang (SD/SMP/SMA/dsb).
     - Total XP mingguan (dengan icon bintang).
   - Baris peserta yang sedang login diberi penanda highlight khusus jika masuk top 10.
   - Jika peserta yang login berada di luar top 10, tampilkan ringkasan posisinya di bawah tabel (contoh: *"Posisi Kamu: #15 • 20 XP"* di jenjang yang sama).
3. **Siklus Reset 7 Hari Sekali:**
   - Leaderboard dan XP user memiliki siklus **7 hari sekali**.
   - Saat waktu reset tiba: **seluruh XP users (`user_xps.total_xp`) di-reset kembali menjadi 0**.
   - Tampilkan informasi kapan reset berikutnya akan terjadi pada header leaderboard (contoh: *"Reset dalam 4 hari"*).
   - Mekanisme reset harus **bulletproof**:
     - **Metode A (Lazy Evaluation / Fallback Otomatis di Service):** Setiap kali dashboard diakses, service memeriksa apakah sudah lewat 7 hari sejak reset terakhir. Jika ya, reset otomatis dieksekusi secara instan. *Ini menjamin reset tetap berjalan di environment local/Docker yang tidak memiliki cron background running.*
     - **Metode B (Artisan Command & Scheduler):** Disediakan perintah `php artisan leaderboard:reset-weekly` yang didaftarkan ke Laravel scheduler (`routes/console.php`).

---

## 2. Struktur Data & Perubahan Database

### 2.1. Kolom Tambahan di Tabel `quiz_settings`
Kita memerlukan penanda kapan reset mingguan terakhir kali dieksekusi.
Buat migration baru untuk menambahkan kolom `last_weekly_reset_at` pada tabel `quiz_settings`:
- Nama kolom: `last_weekly_reset_at` (nullable timestamp, default: `now()`).

---

## 3. File yang Terlibat

| No | File | Jenis Tindakan | Deskripsi |
|---|---|---|---|
| 1 | `database/migrations/2026_10_11_020000_add_last_weekly_reset_at_to_quiz_settings_table.php` | Buat File Baru | Migration untuk menambah kolom `last_weekly_reset_at` pada tabel `quiz_settings`. |
| 2 | `app/Models/QuizSetting.php` | Modifikasi | Tambahkan `last_weekly_reset_at` ke dalam property `$fillable` dan `$casts`. |
| 3 | `app/Services/QuizService.php` | Modifikasi | Tambahkan method untuk cek & eksekusi reset 7 hari, hitung waktu reset berikutnya, dan query data Top 10 leaderboard. |
| 4 | `app/Console/Commands/ResetWeeklyLeaderboardCommand.php` | Buat File Baru | Artisan command `leaderboard:reset-weekly` untuk mereset XP setiap 7 hari. |
| 5 | `routes/console.php` | Modifikasi | Daftarkan jadwal scheduler untuk command reset leaderboard. |
| 6 | `app/Http/Controllers/Participant/DashboardController.php` | Modifikasi | Ambil data leaderboard dari `QuizService` dan kirim ke view `participant.dashboard`. |
| 7 | `resources/views/participant/dashboard.blade.php` | Modifikasi | Tambahkan markup UI tabel Leaderboard Top 10 di bawah section kuis. |
| 8 | `tests/Feature/WeeklyLeaderboardTest.php` | Buat File Baru | Feature test untuk memverifikasi query top 10, filter role non-admin, dan mekanisme reset XP 7 hari. |

---

## 4. Langkah-demi-Langkah Implementasi

---

### Tahap 1: Migration Database

Buat migration baru:
`database/migrations/2026_10_11_020000_add_last_weekly_reset_at_to_quiz_settings_table.php`

**Isi File:**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_settings', function (Blueprint $table) {
            $table->timestamp('last_weekly_reset_at')->nullable()->after('xp_per_correct');
        });

        // Set nilai awal pada baris setting yang sudah ada
        DB::table('quiz_settings')->update([
            'last_weekly_reset_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('quiz_settings', function (Blueprint $table) {
            $table->dropColumn('last_weekly_reset_at');
        });
    }
};
```

Jalankan perintah migration:
```bash
docker compose -f docker-compose.dev.yml exec app php artisan migrate
```
*(Atau `php artisan migrate` jika di luar docker)*.

---

### Tahap 2: Update Model `app/Models/QuizSetting.php`

Buka file `app/Models/QuizSetting.php`.

**Kode Sebelum Perubahan:**
```php
#[Fillable(['reset_time', 'questions_per_day', 'xp_per_correct'])]
class QuizSetting extends Model
{
    /** Ambil satu-satunya baris setting (dibuat otomatis jika belum ada). */
    public static function current(): self
    {
        return static::query()->first() ?? static::create([])->fresh();
    }
}
```

**Kode Sesudah Perubahan:**
```php
#[Fillable(['reset_time', 'questions_per_day', 'xp_per_correct', 'last_weekly_reset_at'])]
class QuizSetting extends Model
{
    protected $casts = [
        'last_weekly_reset_at' => 'datetime',
    ];

    /** Ambil satu-satunya baris setting (dibuat otomatis jika belum ada). */
    public static function current(): self
    {
        return static::query()->first() ?? static::create([
            'last_weekly_reset_at' => now(),
        ])->fresh();
    }
}
```

---

### Tahap 3: Tambahkan Logika Leaderboard di `app/Services/QuizService.php`

Buka `app/Services/QuizService.php`.
Tambahkan method-method pendukung leaderboard dan reset mingguan:

1. **`checkAndResetWeeklyXp(?Carbon $now = null): bool`**: Memeriksa apakah selisih hari sejak `last_weekly_reset_at` sudah >= 7 hari. Jika ya, setel semua `user_xps.total_xp` ke `0` dan perbarui `last_weekly_reset_at`.
2. **`nextWeeklyResetAt(): Carbon`**: Mengembalikan Carbon datetime kapan reset 7 hari berikutnya akan terjadi (`last_weekly_reset_at + 7 days`).
3. **`getWeeklyLeaderboardData(?User $currentUser = null): array`**: Mengambil top 10 peserta beserta ranking user yang login.

**Tambahkan kode berikut pada `app/Services/QuizService.php`:**
```php
    // ---------- Leaderboard Mingguan & Reset XP ----------

    /**
     * Waktu kapan reset XP mingguan berikutnya akan dieksekusi.
     */
    public function nextWeeklyResetAt(): Carbon
    {
        $lastReset = $this->settings()->last_weekly_reset_at ?? now();
        return $lastReset->copy()->addDays(7);
    }

    /**
     * Periksa apakah sudah mencapai 7 hari sejak reset terakhir.
     * Jika ya, reset semua XP users menjadi 0.
     */
    public function checkAndResetWeeklyXp(?Carbon $now = null): bool
    {
        $now = $now ?? now();
        $settings = $this->settings();

        if (! $settings->last_weekly_reset_at) {
            $settings->update(['last_weekly_reset_at' => $now]);
            return false;
        }

        // Jika sudah 7 hari atau lebih sejak reset terakhir
        if ($now->greaterThanOrEqualTo($settings->last_weekly_reset_at->copy()->addDays(7))) {
            $this->performWeeklyXpReset($now);
            return true;
        }

        return false;
    }

    /**
     * Eksekusi reset XP semua users ke 0 dan catat waktu reset terbaru.
     */
    public function performWeeklyXpReset(?Carbon $now = null): void
    {
        $now = $now ?? now();

        DB::transaction(function () use ($now) {
            // Reset seluruh XP users menjadi 0
            UserXp::query()->update(['total_xp' => 0]);

            // Update timestamp reset mingguan di settings
            $this->settings()->update([
                'last_weekly_reset_at' => $now,
            ]);
        });
    }

    /**
     * Ambil data Leaderboard Top 10 per jenjang peserta beserta info posisi user yang sedang login.
     */
    public function getWeeklyLeaderboardData(?User $currentUser = null): array
    {
        // 1. Cek lazy reset otomatis jika sudah lewat 7 hari
        $this->checkAndResetWeeklyXp();

        $jenjang = $currentUser?->jenjang;

        // 2. Query top 10 users (hanya peserta non-admin, difilter sesuai jenjang peserta)
        $topUsers = UserXp::with('user')
            ->whereHas('user', function ($query) use ($jenjang) {
                $query->whereNotIn('role', ['superadmin', 'admin_sesi']);
                if ($jenjang) {
                    $query->where('jenjang', $jenjang);
                }
            })
            ->where('total_xp', '>', 0)
            ->orderByDesc('total_xp')
            ->orderBy('updated_at', 'asc') // tie breaker: yang mencapai skor duluan di atas
            ->limit(10)
            ->get();

        // 3. Hitung posisi / rank user saat ini di jenjang yang sama
        $myRank = null;
        $myXp = 0;

        if ($currentUser && ! in_array($currentUser->role, ['superadmin', 'admin_sesi'])) {
            $myXp = (int) (UserXp::where('user_id', $currentUser->id)->value('total_xp') ?? 0);

            if ($myXp > 0) {
                // Rank adalah jumlah user dengan XP lebih tinggi di jenjang yang sama + 1
                $higherCount = UserXp::whereHas('user', function ($query) use ($jenjang) {
                    $query->whereNotIn('role', ['superadmin', 'admin_sesi']);
                    if ($jenjang) {
                        $query->where('jenjang', $jenjang);
                    }
                })->where('total_xp', '>', $myXp)->count();

                $myRank = $higherCount + 1;
            }
        }

        $nextReset = $this->nextWeeklyResetAt();
        $daysUntilReset = max(0, (int) now()->diffInDays($nextReset, false));
        $hoursUntilReset = max(0, (int) now()->diffInHours($nextReset, false));

        return [
            'topUsers' => $topUsers,
            'jenjang' => $jenjang,
            'myRank' => $myRank,
            'myXp' => $myXp,
            'nextResetAt' => $nextReset,
            'daysUntilReset' => $daysUntilReset,
            'hoursUntilReset' => $hoursUntilReset,
        ];
    }
```

---

### Tahap 4: Buat Artisan Command `app/Console/Commands/ResetWeeklyLeaderboardCommand.php`

Buat file baru:
`app/Console/Commands/ResetWeeklyLeaderboardCommand.php`

**Isi File:**
```php
<?php

namespace App\Console\Commands;

use App\Services\QuizService;
use Illuminate\Console\Command;

class ResetWeeklyLeaderboardCommand extends Command
{
    protected $signature = 'leaderboard:reset-weekly {--force : Paksa reset tanpa menunggu 7 hari}';
    protected $description = 'Reset seluruh total XP peserta kembali ke 0 setiap 7 hari sekali';

    public function handle(QuizService $quizService): int
    {
        if ($this->option('force')) {
            $this->info('Memaksa reset XP mingguan...');
            $quizService->performWeeklyXpReset();
            $this->info('Seluruh XP user berhasil direset ke 0.');
            return Command::SUCCESS;
        }

        $this->info('Memeriksa jadwal reset XP mingguan...');
        $didReset = $quizService->checkAndResetWeeklyXp();

        if ($didReset) {
            $this->info('Siklus 7 hari tercapai. Seluruh XP peserta berhasil direset ke 0.');
        } else {
            $next = $quizService->nextWeeklyResetAt()->format('Y-m-d H:i:s');
            $this->info("Belum mencapai 7 hari. Reset berikutnya pada: {$next}");
        }

        return Command::SUCCESS;
    }
}
```

#### Daftarkan ke `routes/console.php`
Buka `routes/console.php` dan tambahkan:
```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('leaderboard:reset-weekly')->daily();
```

---

### Tahap 5: Update `app/Http/Controllers/Participant/DashboardController.php`

Buka file `app/Http/Controllers/Participant/DashboardController.php`.
Di method `index()`, ambil data leaderboard dan kirimkan ke view.

**Kode Sebelum Perubahan (sekitar baris 77-80):**
```php
        $quizCard = app(QuizService::class)->todaySummary($user);

        return view('participant.dashboard', compact('groupedRegistrations', 'scoreChartData', 'quizCard'));
```

**Kode Sesudah Perubahan:**
```php
        $quizService = app(QuizService::class);
        $quizCard = $quizService->todaySummary($user);
        $leaderboardData = $quizService->getWeeklyLeaderboardData($user);

        return view('participant.dashboard', compact(
            'groupedRegistrations',
            'scoreChartData',
            'quizCard',
            'leaderboardData'
        ));
```

---

### Tahap 6: Tambahkan Komponen UI Leaderboard di `resources/views/participant/dashboard.blade.php`

Buka `resources/views/participant/dashboard.blade.php`.
Temukan akhir dari section kartu kuis:
```html
@endif
```
(Tepat di atas `<div class="glass animate-fade-in" style="padding: 32px; margin-bottom: 24px;">` yang merupakan blok Grafik Nilai).

**Sisipkan markup Leaderboard berikut:**
```html
    <!-- Section Leaderboard Mingguan (Top 10) -->
    <div class="glass animate-fade-in" style="padding: 32px; margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 24px; flex-wrap: wrap;">
            <div>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(245, 158, 11, 0.15); display: flex; align-items: center; justify-content: center; color: #f59e0b; font-size: 1.2rem;">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <h3 style="font-family: 'Outfit', sans-serif; margin: 0; font-size: 1.4rem;">Leaderboard Mingguan {{ !empty($leaderboardData['jenjang']) ? '• Jenjang ' . $leaderboardData['jenjang'] : '' }}</h3>
                </div>
                <p style="color: var(--text-secondary); margin: 0; font-size: 0.95rem;">
                    Top 10 pejuang XP {{ !empty($leaderboardData['jenjang']) ? 'jenjang ' . $leaderboardData['jenjang'] : '' }} minggu ini &middot; Reset otomatis setiap 7 hari sekali.
                </p>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span class="badge" style="background: #fef3c7; color: #92400e; padding: 6px 14px; border-radius: 99px; font-weight: 600; font-size: 0.8rem; border: 1px solid #fde68a;">
                    <i class="fas fa-clock" style="margin-right: 4px;"></i> 
                    Reset: {{ $leaderboardData['daysUntilReset'] > 0 ? $leaderboardData['daysUntilReset'] . ' hari lagi' : $leaderboardData['hoursUntilReset'] . ' jam lagi' }}
                </span>
            </div>
        </div>

        @if($leaderboardData['topUsers']->isNotEmpty())
            <div class="table-responsive">
                <table class="data-table" style="width: 100%; border-collapse: separate; border-spacing: 0;">
                    <thead>
                        <tr>
                            <th style="width: 80px; text-align: center;">RANK</th>
                            <th>NAMA PESERTA</th>
                            <th>JENJANG</th>
                            <th style="text-align: right; width: 140px;">TOTAL XP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($leaderboardData['topUsers'] as $index => $row)
                            @php
                                $rank = $index + 1;
                                $isMe = auth()->id() === $row->user_id;
                            @endphp
                            <tr style="{{ $isMe ? 'background: rgba(37, 99, 235, 0.06); font-weight: 600;' : '' }}">
                                <td style="text-align: center;">
                                    @if($rank === 1)
                                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; background: #fef08a; color: #854d0e; font-weight: 800; font-size: 0.95rem; box-shadow: 0 2px 8px rgba(234, 179, 8, 0.3);">
                                            🥇
                                        </span>
                                    @elseif($rank === 2)
                                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; background: #e2e8f0; color: #334155; font-weight: 800; font-size: 0.95rem;">
                                            🥈
                                        </span>
                                    @elseif($rank === 3)
                                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; background: #fed7aa; color: #9a3412; font-weight: 800; font-size: 0.95rem;">
                                            🥉
                                        </span>
                                    @else
                                        <span style="color: var(--text-secondary); font-weight: 700; font-size: 0.95rem;">
                                            #{{ $rank }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 34px; height: 34px; border-radius: 50%; background: {{ $isMe ? '#2563eb' : '#e0e7ff' }}; color: {{ $isMe ? '#fff' : '#3730a3' }}; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem;">
                                            {{ strtoupper(substr($row->user->name ?? 'U', 0, 1)) }}
                                        </div>
                                        <div>
                                            <div style="color: #0f172a;">
                                                {{ $row->user->name ?? 'Peserta' }}
                                                @if($isMe)
                                                    <span class="badge" style="background: #dbeafe; color: #1d4ed8; font-size: 0.7rem; padding: 2px 6px; margin-left: 4px;">Kamu</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge" style="background: rgba(59, 130, 246, 0.1); color: var(--accent); font-size: 0.75rem;">
                                        {{ $row->user->jenjang ?? '-' }}
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <span style="color: #7c3aed; font-weight: 700; font-size: 1rem; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="fas fa-star" style="color: #f59e0b; font-size: 0.85rem;"></i>
                                        {{ number_format($row->total_xp) }} XP
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Jika user login berada di luar Top 10 -->
            @if(!empty($leaderboardData['myRank']) && $leaderboardData['myRank'] > 10)
                <div style="margin-top: 16px; padding: 14px 20px; background: #eff6ff; border: 1px dashed #93c5fd; border-radius: 12px; display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-weight: 700; color: #1e40af;">Posisi Kamu: #{{ $leaderboardData['myRank'] }}</span>
                        <span style="color: var(--text-secondary); font-size: 0.85rem;">Terus kumpulkan XP untuk masuk Top 10!</span>
                    </div>
                    <span style="color: #7c3aed; font-weight: 800; font-size: 0.95rem;">
                        <i class="fas fa-star" style="color: #f59e0b;"></i> {{ number_format($leaderboardData['myXp']) }} XP
                    </span>
                </div>
            @endif
        @else
            <div style="padding: 36px 20px; text-align: center; background: #f8fafc; border-radius: 16px; border: 1px dashed #cbd5e1;">
                <i class="fas fa-medal" style="font-size: 2.4rem; color: #cbd5e1; margin-bottom: 12px; display: inline-block;"></i>
                <h4 style="font-family: 'Outfit', sans-serif; margin: 0 0 4px; color: #475569;">Belum Ada Peringkat Minggu Ini {{ !empty($leaderboardData['jenjang']) ? 'di Jenjang ' . $leaderboardData['jenjang'] : '' }}</h4>
                <p style="color: var(--text-secondary); margin: 0; font-size: 0.9rem;">
                    Jadilah yang pertama mengumpulkan XP dengan menyelesaikan Quiz Hari Ini!
                </p>
            </div>
        @endif
    </div>
```

---

## 5. Jebakan Umum yang Harus Dihindari (Common Pitfalls)

1. **User Admin Masuk Leaderboard:**
   Pastikan selalu memfilter query dengan `whereHas('user', fn($q) => $q->whereNotIn('role', ['superadmin', 'admin_sesi']))`. Jika admin masuk, kredibilitas leaderboard peserta akan rusak.
2. **Hanya Mengandalkan Scheduler Tanpa Lazy Evaluation:**
   Di environment local development atau server docker tanpa crontab sistem yang berjalan, scheduler `schedule:run` sering kali tidak menyala. Karena itu, method `checkAndResetWeeklyXp()` harus selalu dipanggil saat `getWeeklyLeaderboardData()` dieksekusi agar reset tetap berjalan tepat waktu.
3. **Database Transaction saat Reset:**
   Selalu bungkus `UserXp::query()->update(['total_xp' => 0])` dan pembaruan `last_weekly_reset_at` ke dalam satu `DB::transaction()` untuk menjaga konsistensi ACID.
4. **Tie Breaker saat XP Sama:**
   Jika ada dua peserta memiliki total XP yang sama (misal sama-sama 30 XP), gunakan `orderBy('updated_at', 'asc')` agar user yang mengumpulkan XP lebih cepat berada di peringkat atas secara adil.

---

## 6. Skenario Pengujian & QA Checklist

Buat feature test di `tests/Feature/WeeklyLeaderboardTest.php` untuk memverifikasi:

- [x] **Test 1: Leaderboard Menampilkan Top 10 Peserta Berdasarkan XP Terbanyak**
  - Buat 12 user peserta dengan perolehan XP bervariasi (10, 20, ..., 120 XP).
  - Akses `/dashboard`.
  - Verifikasi response menampilkan Top 10 user dengan XP tertinggi berurutan.
  - Verifikasi user peringkat ke-11 dan ke-12 tidak muncul di tabel utama.

- [x] **Test 2: Admin dan Superadmin Tidak Muncul di Leaderboard**
  - Buat user dengan role `superadmin` yang memiliki XP tinggi (misal 999 XP).
  - Akses dashboard peserta.
  - Verifikasi superadmin tidak tercantum dalam daftar leaderboard.

- [x] **Test 3: Reset XP Otomatis Setelah 7 Hari**
  - Buat user dengan XP 50.
  - Set `last_weekly_reset_at` di setting menjadi 7 hari yang lalu (`now()->subDays(7)`).
  - Panggil `checkAndResetWeeklyXp()` atau akses `/dashboard`.
  - Verifikasi `total_xp` user berubah menjadi 0.
  - Verifikasi `last_weekly_reset_at` terupdate ke waktu sekarang.

- [x] **Test 4: Command `php artisan leaderboard:reset-weekly`**
  - Jalankan artisan command `leaderboard:reset-weekly --force`.
  - Verifikasi command selesai dengan status exit 0 dan seluruh `total_xp` kembali 0.

- [x] **Test 5: Empty State Tampil Saat Belum Ada XP**
  - Saat database baru atau seluruh user ber-XP 0, verifikasi box "Belum Ada Peringkat Minggu Ini" tampil dengan rapi.

- [x] **Test 6: Isolasi Leaderboard Per Jenjang Pendidikan**
  - Buat peserta jenjang SMA dan jenjang SMP dengan perolehan XP masing-masing.
  - Verifikasi peserta SMA hanya melihat leaderboard sesama SMA.
  - Verifikasi peserta SMP hanya melihat leaderboard sesama SMP.

---

## 7. Definition of Done (DoD)

1. [x] Migration `last_weekly_reset_at` berhasil dieksekusi tanpa error.
2. [x] Section Leaderboard tampil di dashboard tepat di bawah section kuis dan di atas grafik nilai.
3. [x] Tabel menampilkan maksimal 10 peserta terurut berdasarkan total XP tertinggi.
4. [x] Leaderboard difilter per jenjang pendidikan sesuai akun peserta yang sedang login.
5. [x] XP seluruh peserta otomatis ter-reset ke 0 saat siklus 7 hari tercapai.
6. [x] Artisan command `leaderboard:reset-weekly` berfungsi dengan baik.
7. [x] Seluruh automated test berjalan sukses (100% Passed).
