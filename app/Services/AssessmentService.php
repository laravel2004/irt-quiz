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

    private function normalizeAnswerValue($value): string
    {
        if (is_array($value)) {
            $value = json_encode($value);
        }

        return strtolower(trim(preg_replace('/\s+/', ' ', strip_tags((string) $value))));
    }

    private function resolveCorrectValues(array $correctAnswers, $options): array
    {
        $options = (array) $options;
        $values = [];

        foreach ($correctAnswers as $correctAnswer) {
            $key = (string) $correctAnswer;
            $upperKey = strtoupper(trim($key));

            if (array_key_exists($key, $options)) {
                $values[] = $options[$key];
                continue;
            }

            if (preg_match('/^[A-Z]$/', $upperKey)) {
                $index = ord($upperKey) - 65;
                if (array_key_exists($index, $options)) {
                    $values[] = $options[$index];
                    continue;
                }
            }

            $values[] = $correctAnswer;
        }

        return array_values(array_filter($values, fn ($value) => $value !== null));
    }

    private function answersMatch($expected, $actual): bool
    {
        return (string) $expected === (string) $actual
            || $this->normalizeAnswerValue($expected) === $this->normalizeAnswerValue($actual);
    }

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

                $answer = is_array($ans->answer) ? $ans->answer : (json_decode($ans->answer, true) ?? $ans->answer);
                
                if ($question->type === 'pilihan_ganda' || $question->type === 'benar_salah') {
                    $correctValue = $this->resolveCorrectValues($correctArr, $options)[0] ?? null;
                    
                    // Frontend might send index or legacy text. If numeric index, map to text.
                    $mappedAnswer = $answer;
                    if (is_numeric($answer) && array_key_exists((int)$answer, $options)) {
                        $mappedAnswer = $options[(int)$answer];
                    }
                    
                    $isCorrect = ($correctValue !== null && $this->answersMatch($correctValue, $mappedAnswer));
                    $score = $isCorrect ? ($question->score_correct ?? 1) : ($question->score_incorrect ?? 0);
                } elseif ($question->type === 'multiple_choice') {
                    $correctValues = $this->resolveCorrectValues($correctArr, $options);
                    $isCorrect = false;
                    
                    if (is_array($answer)) {
                        $mappedAnswers = array_map(function($val) use ($options) {
                            return (is_numeric($val) && array_key_exists((int)$val, $options)) ? $options[(int)$val] : $val;
                        }, $answer);

                        $normalizedAnswers = array_map(fn($value) => $this->normalizeAnswerValue($value), $mappedAnswers);
                        $normalizedCorrect = array_map(fn($value) => $this->normalizeAnswerValue($value), $correctValues);
                        
                        $totalCorrectAvailable = count($normalizedCorrect);
                        $correctSelected = count(array_intersect($normalizedAnswers, $normalizedCorrect));
                        $netCorrect = $correctSelected;
                        $percentage = $totalCorrectAvailable > 0 ? ($netCorrect / $totalCorrectAvailable) : 0;
                        $score = round($percentage * ($question->score_correct ?? 1), 2);
                        
                        if ($netCorrect === $totalCorrectAvailable) {
                            $isCorrect = true;
                        } else if ($percentage == 0) {
                            $score = $question->score_incorrect ?? 0;
                        }
                    } else {
                        $score = $question->score_incorrect ?? 0;
                    }
                } elseif ($question->type === 'multiple_benar_salah') {
                    $isCorrect = false;
                    if (is_array($answer)) {
                        $totalStatements = count($options);
                        $correctCount = 0;
                        
                        foreach ($options as $idx => $optText) {
                            $userAnswer = $answer[strval($idx)] ?? null;
                            $shouldBeBenar = in_array(strval($idx), $correctArr);
                            
                            if (($shouldBeBenar && $userAnswer === 'benar') || (!$shouldBeBenar && $userAnswer === 'salah')) {
                                $correctCount++;
                            }
                        }
                        
                        $percentage = $totalStatements > 0 ? ($correctCount / $totalStatements) : 0;
                        $score = round($percentage * ($question->score_correct ?? 1), 2);
                        
                        if ($correctCount === $totalStatements) {
                            $isCorrect = true;
                        } else if ($percentage == 0) {
                            $score = $question->score_incorrect ?? 0;
                        }
                    } else {
                        $score = $question->score_incorrect ?? 0;
                    }
                }

                $ans->update(['is_correct' => $isCorrect, 'score' => $score]);
            }

            // Step 2: Calculate Item Difficulty (Item Response)
            $itemWeights = [];
            foreach ($questions as $question) {
                $avgScore = UserAnswer::where('exam_session_id', $sessionId)
                    ->where('question_bank_id', $question->id)
                    ->avg('score') ?? 0;
                
                $maxScore = max(0.001, $question->score_correct ?? 1);
                $difficulty = 1 - ($avgScore / $maxScore);
                $difficulty = max(0.1, min(1, $difficulty)); // Ensure difficulty is between 0.1 and 1
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
                        } else {
                            $catIncorrect++;
                        }
                        
                        $maxScore = max(0.001, $question->score_correct ?? 1);
                        $percentageScore = min(1, max(0, $ans->score / $maxScore));
                        
                        $catRawIRT += ($itemWeights[$ans->question_bank_id] ?? 0) * $percentageScore;
                        $catRawPoints += $ans->score;
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
