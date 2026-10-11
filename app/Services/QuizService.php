<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizSetting;
use App\Models\User;
use App\Models\UserXp;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QuizService
{
    private ?QuizSetting $settings = null;

    public function settings(): QuizSetting
    {
        return $this->settings ??= QuizSetting::current();
    }

    // ---------- Periode (pengganti scheduler) ----------

    /** Waktu mulai periode yang sedang berjalan. */
    public function currentPeriodStart(?Carbon $now = null): Carbon
    {
        $now = $now ?? now();
        $resetTime = $this->settings()->reset_time ?: '00:00';

        $start = $now->copy()->setTimeFromTimeString($resetTime);
        if ($now->lt($start)) {
            $start = $start->subDay(); // belum lewat jam reset -> masih periode kemarin
        }

        return $start;
    }

    /** Kunci periode, contoh '2026-10-11'. */
    public function currentPeriodKey(?Carbon $now = null): string
    {
        return $this->currentPeriodStart($now)->toDateString();
    }

    /** Kapan quiz di-reset berikutnya. */
    public function nextResetAt(?Carbon $now = null): Carbon
    {
        return $this->currentPeriodStart($now)->copy()->addDay();
    }

    // ---------- Assign soal ----------

    /**
     * Semua soal user untuk periode ini (dibuat otomatis kalau belum ada).
     * Mengembalikan koleksi kosong jika user belum punya jenjang.
     */
    public function attemptsForToday(User $user): Collection
    {
        if (empty($user->jenjang)) {
            return collect();
        }

        $period = $this->currentPeriodKey();
        $fetch = fn () => QuizAttempt::with('quiz')
            ->where('user_id', $user->id)
            ->where('period_key', $period)
            ->whereHas('quiz')
            ->orderBy('id')
            ->get();

        $existing = $fetch();
        if ($existing->isNotEmpty()) {
            return $existing;
        }

        return DB::transaction(function () use ($user, $period, $fetch) {
            // Kunci baris user supaya 2 request bersamaan tidak membuat set soal ganda.
            User::whereKey($user->id)->lockForUpdate()->first();

            $existing = $fetch();
            if ($existing->isNotEmpty()) {
                return $existing;
            }

            $limit = max(1, (int) $this->settings()->questions_per_day);

            $doneIds = QuizAttempt::where('user_id', $user->id)
                ->whereNotNull('answered_at')
                ->pluck('quiz_id')
                ->filter()
                ->all();

            $pool = fn () => Quiz::where('jenjang', $user->jenjang);

            // 1) utamakan soal yang belum pernah dikerjakan
            $picked = $pool()->whereNotIn('id', $doneIds)->inRandomOrder()->limit($limit)->get();

            // 2) kalau kurang, lengkapi dengan soal lama
            if ($picked->count() < $limit) {
                $more = $pool()->whereNotIn('id', $picked->pluck('id')->all())
                    ->inRandomOrder()->limit($limit - $picked->count())->get();
                $picked = $picked->concat($more);
            }

            foreach ($picked as $quiz) {
                QuizAttempt::create([
                    'user_id' => $user->id,
                    'quiz_id' => $quiz->id,
                    'period_key' => $period,
                    // getRawOriginal: ambil teks asli tanpa accessor yang mengubah URL gambar
                    'question_snapshot' => $quiz->getRawOriginal('question_text'),
                ]);
            }

            return $fetch();
        });
    }

    /** Ringkasan untuk kartu dashboard. null = kartu tidak ditampilkan. */
    public function todaySummary(User $user): ?array
    {
        // Abaikan baris yang soalnya sudah dihapus superadmin
        $attempts = $this->attemptsForToday($user)->filter(fn ($a) => $a->quiz);

        // Jika tidak ada soal yang di-assign untuk user, kartu tidak muncul
        if ($attempts->isEmpty()) {
            return null;
        }

        $pending = $attempts->whereNull('answered_at');
        $isCompleted = $pending->isEmpty();
        $correctCount = $attempts->where('is_correct', true)->count();
        $earnedXpToday = (int) $attempts->sum('xp_earned');

        return [
            'is_completed' => $isCompleted,
            'pending' => $pending->count(),
            'total' => $attempts->count(),
            'correct_count' => $correctCount,
            'earned_xp_today' => $earnedXpToday,
            'xp_per_correct' => (int) $this->settings()->xp_per_correct,
            'total_xp' => (int) (UserXp::where('user_id', $user->id)->value('total_xp') ?? 0),
            'reset_at' => $this->nextResetAt(),
        ];
    }

    /** Data soal yang aman dikirim ke browser (TANPA kunci jawaban & pembahasan). */
    public function publicQuestion(QuizAttempt $attempt): array
    {
        $quiz = $attempt->quiz;

        return [
            'attempt_id' => $attempt->id,
            'type' => $quiz->type,
            'question_text' => $quiz->question_text,
            'question_image' => $quiz->question_image ? asset('storage/' . $quiz->question_image) : null,
            'options' => array_values((array) $quiz->options),
        ];
    }

    /**
     * Data soal untuk mode preview (menampilkan jawaban user, status benar/salah, kunci jawaban, dan pembahasan).
     */
    public function previewQuestion(QuizAttempt $attempt): array
    {
        $quiz = $attempt->quiz;
        $checker = app(QuizAnswerChecker::class);

        return [
            'attempt_id' => $attempt->id,
            'type' => $quiz->type,
            'question_text' => $quiz->question_text,
            'question_image' => $quiz->question_image ? asset('storage/' . $quiz->question_image) : null,
            'options' => array_values((array) $quiz->options),
            'user_answer' => $attempt->answer,
            'is_correct' => (bool) $attempt->is_correct,
            'xp_earned' => (int) $attempt->xp_earned,
            'correct_indices' => $checker->correctIndices($quiz),
            'explanation' => $quiz->explanation,
        ];
    }

    // ---------- Simpan jawaban ----------

    /**
     * Cek jawaban, simpan, tambah XP. Melempar HttpException (403/404/409/410) jika tidak valid.
     */
    public function submitAnswer(User $user, QuizAttempt $attempt, mixed $answer): array
    {
        return DB::transaction(function () use ($user, $attempt, $answer) {
            // Kunci baris attempt supaya klik ganda tidak memberi XP dua kali.
            $attempt = QuizAttempt::with('quiz')->whereKey($attempt->id)->lockForUpdate()->firstOrFail();

            abort_if($attempt->user_id !== $user->id, 403, 'Bukan soal kamu.');
            abort_if($attempt->answered_at !== null, 409, 'Quiz ini sudah kamu jawab.');
            abort_if($attempt->period_key !== $this->currentPeriodKey(), 410, 'Periode quiz sudah berakhir. Muat ulang halaman.');
            abort_if(! $attempt->quiz, 404, 'Soal tidak ditemukan.');

            $checker = app(QuizAnswerChecker::class);
            $isCorrect = $checker->isCorrect($attempt->quiz, $answer);
            $xp = $isCorrect ? (int) $this->settings()->xp_per_correct : 0;

            $attempt->update([
                'answer' => $answer,
                'is_correct' => $isCorrect,
                'xp_earned' => $xp,
                'answered_at' => now(),
            ]);

            $xpRow = UserXp::firstOrCreate(['user_id' => $user->id], ['total_xp' => 0]);
            if ($xp > 0) {
                $xpRow->increment('total_xp', $xp);
            }

            return [
                'is_correct' => $isCorrect,
                'xp_earned' => $xp,
                'total_xp' => $xpRow->total_xp,
                // Berikan info benar/salah & pembahasan HANYA SETELAH mereka menjawab
                'correct_indices' => $checker->correctIndices($attempt->quiz),
                'explanation' => $attempt->quiz->explanation,
            ];
        });
    }

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
}
