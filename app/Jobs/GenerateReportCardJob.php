<?php

namespace App\Jobs;

use App\Models\ReportCard;
use App\Models\ExamSessionParticipant;
use App\Models\UserAnswer;
use App\Models\QuestionBank;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateReportCardJob implements ShouldQueue
{
    use Queueable;

    public int $reportCardId;

    public function __construct(int $reportCardId)
    {
        $this->reportCardId = $reportCardId;
    }

    public function handle(): void
    {
        $reportCard = ReportCard::find($this->reportCardId);

        if (!$reportCard) {
            return;
        }

        try {
            $userId    = $reportCard->user_id;
            $sessionIds = $reportCard->session_ids; // contoh: [1, 3, 5]

            // ============================================================
            // LANGKAH 1: Cari participant_id TERBARU per sesi ujian
            // ============================================================
            // Kenapa terbaru? Karena 1 user bisa retake sesi yang sama berkali-kali.
            // Kita hanya ambil percobaan terakhir (ID paling besar).
            $latestParticipantIds = [];

            foreach ($sessionIds as $sessionId) {
                $latestParticipant = ExamSessionParticipant::where('user_id', $userId)
                    ->where('exam_session_id', $sessionId)
                    ->whereNotNull('finished_at') // Hanya yang sudah selesai ujian
                    ->orderBy('id', 'desc')        // Ambil yang paling baru (ID terbesar)
                    ->first();

                if ($latestParticipant) {
                    $latestParticipantIds[] = $latestParticipant->id;
                }
            }

            // Jika tidak ada participant yang ditemukan, set gagal
            if (empty($latestParticipantIds)) {
                $reportCard->update([
                    'status'        => 'failed',
                    'error_message' => 'Tidak ditemukan data ujian yang sudah selesai untuk sesi yang dipilih.',
                ]);
                return;
            }

            // ============================================================
            // LANGKAH 2: Ambil semua jawaban user dari participant terbaru
            // ============================================================
            $allAnswers = UserAnswer::whereIn('participant_id', $latestParticipantIds)
                ->with(['question.category', 'question.subCategory'])
                ->get();

            // ============================================================
            // LANGKAH 3: Deduplikasi soal yang sama
            // ============================================================
            // Jika soal (question_bank_id) muncul di lebih dari 1 sesi ujian,
            // kita hanya ambil salah satu jawaban (yang pertama ditemukan).
            $uniqueAnswers = $allAnswers->unique('question_bank_id');

            // ============================================================
            // LANGKAH 4: Mapping per Mata Pelajaran & Sub Mata Pelajaran
            // ============================================================
            $reportData = [];

            foreach ($uniqueAnswers as $answer) {
                $question = $answer->question;

                // Jaga-jaga jika data soal sudah dihapus
                if (!$question || !$question->category) {
                    continue;
                }

                $categoryName    = $question->category->name;
                $subCategoryName = $question->subCategory->name ?? 'Umum'; // Default 'Umum' jika sub kosong
                $categoryId      = $question->category_id;
                $subCategoryId   = $question->sub_category_id ?? 0;

                // Inisialisasi struktur data jika belum ada
                if (!isset($reportData[$categoryId])) {
                    $reportData[$categoryId] = [
                        'category_name' => $categoryName,
                        'total_soal'    => 0,
                        'total_benar'   => 0,
                        'total_salah'   => 0,
                        'sub_categories' => [],
                    ];
                }

                if (!isset($reportData[$categoryId]['sub_categories'][$subCategoryId])) {
                    $reportData[$categoryId]['sub_categories'][$subCategoryId] = [
                        'sub_category_name' => $subCategoryName,
                        'total_soal'        => 0,
                        'total_benar'       => 0,
                        'total_salah'       => 0,
                    ];
                }

                // Hitung benar/salah
                $isCorrect = (bool) $answer->is_correct;

                // Update hitungan Mata Pelajaran
                $reportData[$categoryId]['total_soal']++;
                if ($isCorrect) {
                    $reportData[$categoryId]['total_benar']++;
                } else {
                    $reportData[$categoryId]['total_salah']++;
                }

                // Update hitungan Sub Mata Pelajaran
                $reportData[$categoryId]['sub_categories'][$subCategoryId]['total_soal']++;
                if ($isCorrect) {
                    $reportData[$categoryId]['sub_categories'][$subCategoryId]['total_benar']++;
                } else {
                    $reportData[$categoryId]['sub_categories'][$subCategoryId]['total_salah']++;
                }
            }

            // Konversi sub_categories dari assoc ke indexed array agar JSON-nya rapi
            foreach ($reportData as $catId => $catData) {
                $reportData[$catId]['sub_categories'] = array_values($catData['sub_categories']);
            }
            $reportData = array_values($reportData);

            // ============================================================
            // LANGKAH 5: Simpan hasil ke database
            // ============================================================
            $reportCard->update([
                'status'      => 'completed',
                'report_data' => $reportData,
            ]);

        } catch (\Exception $e) {
            Log::error('GenerateReportCardJob failed: ' . $e->getMessage());
            $reportCard->update([
                'status'        => 'failed',
                'error_message' => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
            ]);
        }
    }
}
