<?php

namespace App\Services;

use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Models\UserAnswer;
use App\Models\ExamResult;
use App\Models\ExamCategoryResult;
use Illuminate\Support\Facades\DB;

class AssessmentService
{
    public function calculateIRT(int $sessionId)
    {
        return DB::transaction(function () use ($sessionId) {
            $session = ExamSession::with(['participants', 'sessionCategories.category'])->findOrFail($sessionId);
            $participants = $session->participants()->whereNotNull('finished_at')->get();
            
            // Get all unique questions that were actually answered in this session
            $usedQuestionIds = UserAnswer::where('exam_session_id', $sessionId)
                ->pluck('question_bank_id')
                ->unique();
            
            $questions = \App\Models\QuestionBank::whereIn('id', $usedQuestionIds)->get();

            if ($participants->isEmpty()) {
                return ['status' => 'error', 'message' => 'Tidak ada peserta yang menyelesaikan ujian.'];
            }

            // Step 1: Pre-calculate is_correct for all answers before checking item difficulty
            $allAnswers = UserAnswer::where('exam_session_id', $sessionId)->get();
            foreach ($allAnswers as $ans) {
                $question = $questions->firstWhere('id', $ans->question_bank_id);
                if (!$question) continue;
                
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
                } elseif ($question->type === 'multiple_benar_salah') {
                    $totalStatements = count($options);
                    $correctCount = 0;
                    $ansArray = is_array($ans->answer) ? $ans->answer : json_decode($ans->answer, true);
                    if (is_array($ansArray)) {
                        foreach ($options as $idx => $optText) {
                            $userAns = $ansArray[strval($idx)] ?? null;
                            $shouldBeBenar = in_array(strval($idx), array_map('strval', $correctArr));
                            if (($shouldBeBenar && $userAns === 'benar') || (!$shouldBeBenar && $userAns === 'salah')) {
                                $correctCount++;
                            }
                        }
                    }
                    // For multiple benar salah, full marks if all statements correct, else false
                    // Or you could make it partial. For now, boolean isCorrect:
                    $isCorrect = ($correctCount === $totalStatements);
                }

                $ans->update(['is_correct' => $isCorrect]);
            }

            // Step 2: Calculate Item Difficulty (Item Response)
            $itemWeights = [];
            foreach ($questions as $question) {
                $correctCount = UserAnswer::where('exam_session_id', $sessionId)
                    ->where('question_bank_id', $question->id)
                    ->where('is_correct', true)
                    ->count();
                
                $difficulty = 1 - ($correctCount / $participants->count());
                $difficulty = max(0.1, $difficulty);
                $itemWeights[$question->id] = $difficulty;
                
                // Update the question difficulty in the session pivot table
                DB::table('session_questions')
                    ->where('exam_session_id', $sessionId)
                    ->where('question_bank_id', $question->id)
                    ->update(['difficulty' => $difficulty]);
            }

            // Calculate max possible scores for EACH category based on the session's question pool
            $categoryMaxRaw = [];
            $categoryMaxIRT = [];
            foreach ($session->sessionCategories as $sessionCategory) {
                $catId = $sessionCategory->category_id;
                $catQuestions = $questions->where('category_id', $catId);
                
                $categoryMaxRaw[$catId] = $catQuestions->sum('score_correct');
                $catMaxIRT = 0;
                foreach ($catQuestions as $q) {
                    $catMaxIRT += $itemWeights[$q->id] ?? 0;
                }
                $categoryMaxIRT[$catId] = $catMaxIRT;
            }

            // Step 3: Calculate Participant Scores
            foreach ($participants as $participant) {
                $answers = $allAnswers->where('participant_id', $participant->id);

                $totalCorrectAll = 0;
                $totalIncorrectAll = 0;
                $totalBlankAll = $participant->questions()->count() - $answers->count();
                $totalRawAll = 0;
                $totalIRTAll = 0;

                foreach ($session->sessionCategories as $sessionCategory) {
                    $catId = $sessionCategory->category_id;
                    $catAnswers = $answers->filter(function($ans) use ($questions, $catId) {
                        $q = $questions->firstWhere('id', $ans->question_bank_id);
                        return $q && $q->category_id == $catId;
                    });

                    $catCorrect = 0;
                    $catIncorrect = 0;
                    $catRawIRT = 0;
                    $catRawPoints = 0;

                    foreach ($catAnswers as $ans) {
                        $question = $questions->firstWhere('id', $ans->question_bank_id);
                        if (!$question) continue;
                        
                        if ($ans->is_correct) {
                            $catCorrect++;
                            $catRawIRT += $itemWeights[$ans->question_bank_id] ?? 0;
                            $catRawPoints += $question->score_correct;
                        } else {
                            $catIncorrect++;
                            $catRawPoints += $question->score_incorrect;
                        }
                    }

                    // Ratio scaling for Raw Score
                    $maxPossibleRaw = $categoryMaxRaw[$catId] ?? 0;
                    $finalRawScore = ($maxPossibleRaw > 0) ? ($catRawPoints / $maxPossibleRaw) * $sessionCategory->max_score_raw : 0;
                    $finalRawScore = max(0, min($finalRawScore, $sessionCategory->max_score_raw));

                    // Ratio scaling for IRT Score
                    $maxPossibleIRT = $categoryMaxIRT[$catId] ?? 0;
                    $finalIRTScore = ($maxPossibleIRT > 0) ? ($catRawIRT / $maxPossibleIRT) * $sessionCategory->max_score_irt : 0;
                    $finalIRTScore = max(0, min($finalIRTScore, $sessionCategory->max_score_irt));

                    // Accumulate totals
                    $totalCorrectAll += $catCorrect;
                    $totalIncorrectAll += $catIncorrect;
                    $totalRawAll += $finalRawScore;
                    $totalIRTAll += $finalIRTScore;

                    // Save category result
                    $examResult = ExamResult::firstOrCreate([
                        'participant_id' => $participant->id,
                        'exam_session_id' => $sessionId
                    ], [
                        'total_correct' => 0,
                        'total_incorrect' => 0,
                        'total_blank' => 0,
                        'score' => 0,
                        'irt_score' => 0
                    ]);

                    $catTotalBlank = $catQuestions->count() - $catAnswers->count();

                    ExamCategoryResult::updateOrCreate(
                        [
                            'exam_result_id' => $examResult->id,
                            'category_id' => $catId
                        ],
                        [
                            'total_correct' => $catCorrect,
                            'total_incorrect' => $catIncorrect,
                            'total_blank' => $catTotalBlank,
                            'score' => $finalRawScore,
                            'irt_score' => $finalIRTScore
                        ]
                    );
                }

                // Update total ExamResult
                ExamResult::updateOrCreate(
                    [
                        'participant_id' => $participant->id,
                        'exam_session_id' => $sessionId
                    ],
                    [
                        'total_correct' => $totalCorrectAll,
                        'total_incorrect' => $totalIncorrectAll,
                        'total_blank' => $totalBlankAll,
                        'score' => $totalRawAll,
                        'irt_score' => $totalIRTAll
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
