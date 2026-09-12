<?php

namespace App\Jobs;

use App\Models\ExamSessionParticipant;
use App\Services\AIService;
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
        $participant = ExamSessionParticipant::find($this->participantId);
        if (! $participant || $participant->privilege !== 'premium') {
            return;
        }

        $registrations = ExamSessionParticipant::where('user_id', $participant->user_id)
            ->where('exam_session_id', $participant->exam_session_id)
            ->with(['result', 'examSession'])
            ->orderBy('id', 'asc')
            ->get();

        $attemptsData = [];
        foreach ($registrations as $index => $reg) {
            if ($reg->result?->irt_score !== null) {
                $scores = [];
                if ($reg->examSession->showsRawScoreToParticipant()) {
                    $scores[] = 'Skor Raw '.number_format((float) $reg->result->score, 2).' (Predikat Raw '.$reg->examSession->predicateForRawScore((float) $reg->result->score).')';
                }
                if ($reg->examSession->showsIrtScoreToParticipant()) {
                    $scores[] = 'Skor IRT '.number_format((float) $reg->result->irt_score, 2).' (Predikat IRT '.$reg->examSession->predicateForIrtScore((float) $reg->result->irt_score).')';
                }
                $attemptsData[] = [
                    'attempt_number' => $index + 1,
                    'total_correct' => $reg->result->total_correct,
                    'total_incorrect' => $reg->result->total_incorrect,
                    'total_blank' => $reg->result->total_blank,
                    'score_summary' => implode('; ', $scores),
                ];
            }
        }

        if (count($attemptsData) >= 2) {
            $aiService = new AIService;
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
