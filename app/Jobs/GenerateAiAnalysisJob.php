<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateAiAnalysisJob implements ShouldQueue
{
    use Queueable;

    public $participantId;

    /**
     * Create a new job instance.
     */
    public function __construct($participantId)
    {
        $this->participantId = $participantId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $participant = \App\Models\ExamSessionParticipant::find($this->participantId);
        if (!$participant || $participant->privilege !== 'premium') {
            return;
        }

        $registrations = \App\Models\ExamSessionParticipant::where('user_id', $participant->user_id)
            ->where('exam_session_id', $participant->exam_session_id)
            ->with('result')
            ->orderBy('id', 'asc')
            ->get();

        $attemptsData = [];
        foreach ($registrations as $index => $reg) {
            if ($reg->result) {
                $attemptsData[] = [
                    'attempt_number' => $index + 1,
                    'total_correct' => $reg->result->total_correct,
                    'total_incorrect' => $reg->result->total_incorrect,
                    'total_blank' => $reg->result->total_blank,
                    'raw_score' => $reg->result->score,
                    'irt_score' => $reg->result->irt_score,
                ];
            }
        }

        if (count($attemptsData) >= 2) {
            $aiService = new \App\Services\AIService();
            $analysis = $aiService->generateAggregateAnalysis([
                'participant_name' => $participant->name,
                'session_name' => $registrations->first()->examSession->name,
                'attempts' => $attemptsData,
            ]);

            if ($analysis) {
                $participant->update([
                    'ai_analysis' => $analysis['analysis'] ?? null,
                    'ai_weaknesses' => $analysis['weaknesses'] ?? null,
                    'ai_study_plan' => $analysis['study_plan'] ?? null,
                ]);
            }
        }
    }
}
