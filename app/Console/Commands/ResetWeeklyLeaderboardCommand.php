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
