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
            $session = ExamSession::with(['participants'])->findOrFail($sessionId);
            $participants = $session->participants()->whereNotNull('finished_at')->get();
            
            // Get all unique questions that were actually answered in this session
            $usedQuestionIds = UserAnswer::where('exam_session_id', $sessionId)
                ->pluck('question_bank_id')
                ->unique();
            
            $questions = \App\Models\QuestionBank::whereIn('id', $usedQuestionIds)->get();

            if ($participants->isEmpty()) {
                return ['status' => 'error', 'message' => 'Tidak ada peserta yang menyelesaikan ujian.'];
            }

            // Step 1: Calculate Item Difficulty (Item Response)
            $itemWeights = [];
            foreach ($questions as $question) {
                $correctCount = UserAnswer::where('exam_session_id', $sessionId)
                    ->where('question_bank_id', $question->id)
                    ->where('is_correct', true)
                    ->count();
                
                $difficulty = 1 - ($correctCount / $participants->count());
                $difficulty = max(0.1, $difficulty);
                $itemWeights[$question->id] = $difficulty;
                
                // Update the question difficulty in bank
                $question->update(['difficulty' => $difficulty]);
            }

            // Calculate max possible scores for the session pool
            $maxPossibleRaw = $questions->sum('score_correct');
            $maxPossibleIRT = 0;
            foreach ($questions as $q) {
                $maxPossibleIRT += $itemWeights[$q->id] ?? 0;
            }

            // Step 2: Calculate Participant Scores
            foreach ($participants as $participant) {
                $answers = UserAnswer::where('participant_id', $participant->id)
                    ->where('exam_session_id', $sessionId)
                    ->get();

                $totalCorrect = 0;
                $totalIncorrect = 0;
                $assignedCount = $participant->questions()->count();
                $totalBlank = $assignedCount - $answers->count();
                $rawIRTScore = 0;
                $rawPoints = 0;

                foreach ($answers as $ans) {
                    $question = $questions->firstWhere('id', $ans->question_bank_id);
                    if (!$question) continue; // Skip if question not found
                    
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
                        $rawPoints += $question->score_correct;
                    } else {
                        $totalIncorrect++;
                        $rawPoints += $question->score_incorrect;
                    }
                }

                // Ratio scaling for Raw Score
                $finalRawScore = ($maxPossibleRaw > 0) ? ($rawPoints / $maxPossibleRaw) * $session->max_score_raw : 0;
                $finalRawScore = max(0, min($finalRawScore, $session->max_score_raw)); // Clamp just in case

                // Ratio scaling for IRT Score
                $finalIRTScore = ($maxPossibleIRT > 0) ? ($rawIRTScore / $maxPossibleIRT) * $session->max_score_irt : 0;
                $finalIRTScore = max(0, min($finalIRTScore, $session->max_score_irt)); // Clamp

                ExamResult::updateOrCreate(
                    [
                        'participant_id' => $participant->id,
                        'exam_session_id' => $sessionId
                    ],
                    [
                        'total_correct' => $totalCorrect,
                        'total_incorrect' => $totalIncorrect,
                        'total_blank' => $totalBlank,
                        'score' => $finalRawScore,
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
