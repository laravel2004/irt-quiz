<?php

namespace App\Services;

use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Models\UserAnswer;
use App\Models\ExamResult;
use Illuminate\Support\Facades\DB;

class AssessmentService
{
    public function calculateIRT(int $sessionId)
    {
        return DB::transaction(function () use ($sessionId) {
            $session = ExamSession::with(['questions', 'participants'])->findOrFail($sessionId);
            $participants = $session->participants()->whereNotNull('finished_at')->get();
            $questions = $session->questions;

            if ($participants->isEmpty()) {
                return ['status' => 'error', 'message' => 'Tidak ada peserta yang menyelesaikan ujian.'];
            }

            // Step 1: Calculate Item Difficulty (Item Response)
            // Difficulty = 1 - (Correct Count / Total Participants)
            // Questions that fewer people answer correctly are "harder" (higher weight)
            $itemWeights = [];
            foreach ($questions as $question) {
                $correctCount = UserAnswer::where('exam_session_id', $sessionId)
                    ->where('question_bank_id', $question->id)
                    ->where('is_correct', true)
                    ->count();
                
                $difficulty = 1 - ($correctCount / $participants->count());
                // Ensure difficulty is not 0 (at least a small weight)
                $itemWeights[$question->id] = max(0.1, $difficulty);
            }

            // Step 2: Calculate Participant Scores
            foreach ($participants as $participant) {
                $answers = UserAnswer::where('participant_id', $participant->id)
                    ->where('exam_session_id', $sessionId)
                    ->get();

                $totalCorrect = 0;
                $totalIncorrect = 0;
                $totalBlank = $questions->count() - $answers->count();
                $rawIRTScore = 0;

                foreach ($answers as $ans) {
                    // Re-validate correctness in case it was stored incorrectly
                    $question = $questions->firstWhere('id', $ans->question_bank_id);
                    $correctArr = (array) $question->correct_answer;
                    $options = (array) $question->options;
                    $isCorrect = false;

                    if ($question->type === 'pilihan_ganda' || $question->type === 'benar_salah') {
                        $correctIndex = $correctArr[0] ?? null;
                        $correctValue = $options[$correctIndex] ?? null;
                        $isCorrect = ($correctValue !== null && $correctValue == $ans->answer);
                    } elseif ($question->type === 'multiple_choice') {
                        $correctValues = array_map(fn($idx) => $options[$idx] ?? null, $correctArr);
                        $correctValues = array_filter($correctValues, fn($v) => $v !== null);
                        
                        $ansArray = (array) $ans->answer;
                        sort($ansArray);
                        sort($correctValues);
                        $isCorrect = ($ansArray === $correctValues);
                    }

                    // Update the answer record
                    $ans->update(['is_correct' => $isCorrect]);

                    if ($isCorrect) {
                        $totalCorrect++;
                        $rawIRTScore += $itemWeights[$ans->question_bank_id];
                    } else {
                        $totalIncorrect++;
                    }
                }

                // Normalization (optional, e.g., scale to 1000 like UTBK)
                // For now, let's keep it as is or multiply by 10 for better numbers
                $finalIRTScore = $rawIRTScore * 10;

                ExamResult::updateOrCreate(
                    [
                        'participant_id' => $participant->id,
                        'exam_session_id' => $sessionId
                    ],
                    [
                        'total_correct' => $totalCorrect,
                        'total_incorrect' => $totalIncorrect,
                        'total_blank' => $totalBlank,
                        'score' => ($totalCorrect / $questions->count()) * 100, // Standard percentage
                        'irt_score' => $finalIRTScore
                    ]
                );
            }

            return [
                'status' => 'success',
                'message' => 'Penilaian IRT berhasil digenerate.',
                'total_participants' => $participants->count()
            ];
        });
    }
}
