<?php

namespace App\Jobs;

use App\Models\ReportCard;
use App\Services\AIService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateReportCardAiAnalysisJob implements ShouldQueue
{
    use Queueable;

    public int $reportCardId;

    /**
     * Buat instance baru.
     */
    public function __construct(int $reportCardId)
    {
        $this->reportCardId = $reportCardId;
    }

    /**
     * Jalankan job.
     */
    public function handle(): void
    {
        $reportCard = ReportCard::with('user')->find($this->reportCardId);

        // Guard: pastikan report card ada dan sudah completed
        if (!$reportCard || $reportCard->status !== 'completed') {
            Log::warning("ReportCardAiAnalysis: ReportCard #{$this->reportCardId} not found or not completed.");
            return;
        }

        // Guard: pastikan ada report_data
        if (empty($reportCard->report_data)) {
            Log::warning("ReportCardAiAnalysis: ReportCard #{$this->reportCardId} has no report_data.");
            $reportCard->update(['ai_analysis_status' => 'failed']);
            return;
        }

        try {
            // Update status jadi processing
            $reportCard->update(['ai_analysis_status' => 'processing']);

            // Panggil AI Service
            $aiService = new AIService();
            $analysis = $aiService->generateReportCardAnalysis([
                'participant_name' => $reportCard->user->name,
                'report_data'      => $reportCard->report_data,
            ]);

            if ($analysis) {
                $reportCard->update([
                    'ai_analysis'        => $analysis,
                    'ai_analysis_status' => 'completed',
                ]);
                Log::info("ReportCardAiAnalysis: ReportCard #{$this->reportCardId} analysis completed.");
            } else {
                $reportCard->update(['ai_analysis_status' => 'failed']);
                Log::error("ReportCardAiAnalysis: ReportCard #{$this->reportCardId} AI returned null.");
            }

        } catch (\Exception $e) {
            Log::error("ReportCardAiAnalysis: ReportCard #{$this->reportCardId} failed: " . $e->getMessage());
            $reportCard->update(['ai_analysis_status' => 'failed']);
        }
    }
}
