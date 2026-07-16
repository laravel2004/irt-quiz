<?php

namespace App\Http\Controllers;

use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Models\QuestionBank;
use App\Models\UserAnswer;
use App\Services\ExamSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    protected ExamSessionService $sessionService;

    public function __construct(ExamSessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }


    private function normalizeAnswerValue($value): string
    {
        if (is_array($value)) {
            $value = json_encode($value);
        }

        return strtolower(trim(preg_replace('/\s+/', ' ', strip_tags((string) $value))));
    }

    private function resolveCorrectValues(array $correctAnswers, array $options): array
    {
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

    private function getParticipant($code)
    {
        $userId = auth()->id();
        $session = ExamSession::where('code', $code)->firstOrFail();
        
        $participant = ExamSessionParticipant::where('exam_session_id', $session->id)
            ->where('user_id', $userId)
            ->latest('id')
            ->first();

        return $participant;
    }

    public function terms($code)
    {
        $participant = $this->getParticipant($code);

        if (!$participant) {
            return redirect()->route('participant.dashboard')->with('error', 'Anda belum terdaftar di sesi ujian ini.');
        }

        $session = $participant->examSession;

        // Validasi waktu aktif
        $now = now();
        $start = \Carbon\Carbon::parse($session->start_date . ' ' . $session->start_time);
        $end = \Carbon\Carbon::parse($session->end_date . ' ' . $session->end_time);

        if (!$session->is_active) {
            return redirect()->route('participant.dashboard')->with('error', 'Sesi ujian sedang ditutup oleh administrator.');
        }

        if ($now->lt($start)) {
            $formattedStart = $start->translatedFormat('d F Y, H:i');
            return redirect()->route('participant.dashboard')->with('error', "Ujian belum dimulai. Silakan masuk kembali pada $formattedStart WIB.");
        }

        if ($now->gt($end)) {
            return redirect()->route('participant.dashboard')->with('error', 'Waktu ujian telah berakhir.');
        }

        if ($participant->finished_at) {
            return redirect()->route('participant.dashboard')->with('error', 'Anda sudah menyelesaikan ujian ini.');
        }

        session(['participant_id' => $participant->id]);

        return view('exam.terms', compact('session', 'participant'));
    }

    public function agreeTerms(Request $request, $code)
    {
        $request->validate([
            'agree_terms' => 'accepted',
        ], [
            'agree_terms.accepted' => 'Anda harus menyetujui syarat dan ketentuan sebelum memulai ujian.',
        ]);

        $participant = $this->getParticipant($code);
        if (!$participant) return redirect()->route('participant.dashboard');

        $session = $participant->examSession;

        // Generate questions if not exist
        if ($session->questions()->count() == 0) {
            $this->sessionService->generateSessionQuestions($session->id);
        }

        if ($participant->questions()->count() == 0) {
            $this->generateParticipantQuestions($participant);
        }

        // Mark started if not already
        if (!$participant->started_at) {
            $participant->update(['started_at' => now()]);
        }

        return redirect()->route('exam.categories', $session->code);
    }

    public function categories($code)
    {
        $participant = $this->getParticipant($code);
        if (!$participant) return redirect()->route('participant.dashboard');

        $session = $participant->examSession;
        if (!$session->is_active) {
            return redirect()->route('participant.dashboard')->with('error', 'Sesi ujian telah ditutup oleh administrator.');
        }
        
        // Cek status mapel
        $categoryStatuses = \App\Models\ParticipantCategoryStatus::where('exam_session_participant_id', $participant->id)
            ->get()->keyBy('exam_session_category_id');

        return view('exam.categories', compact('session', 'participant', 'categoryStatuses'));
    }

    public function startCategory(Request $request, $code, $categoryId)
    {
        $participant = $this->getParticipant($code);
        if (!$participant) return redirect()->route('participant.dashboard');

        if (!$participant->examSession->is_active) {
            return redirect()->route('participant.dashboard')->with('error', 'Sesi ujian telah ditutup oleh administrator.');
        }

        $sessionCategory = \App\Models\ExamSessionCategory::where('exam_session_id', $participant->exam_session_id)
            ->where('id', $categoryId)
            ->firstOrFail();

        $status = \App\Models\ParticipantCategoryStatus::firstOrCreate(
            [
                'exam_session_participant_id' => $participant->id,
                'exam_session_category_id' => $sessionCategory->id
            ],
            [
                'started_at' => now()
            ]
        );

        return redirect()->route('exam.main', ['code' => $code, 'id' => $categoryId]);
    }

    public function main($code, $categoryId)
    {
        $participant = $this->getParticipant($code);
        if (!$participant) return redirect()->route('participant.dashboard');

        $session = $participant->examSession;
        if (!$session->is_active) {
            return redirect()->route('participant.dashboard')->with('error', 'Sesi ujian telah ditutup oleh administrator.');
        }

        $sessionCategory = \App\Models\ExamSessionCategory::with('category')->findOrFail($categoryId);

        $status = \App\Models\ParticipantCategoryStatus::where('exam_session_participant_id', $participant->id)
            ->where('exam_session_category_id', $categoryId)
            ->first();

        if (!$status || !$status->started_at) {
            return redirect()->route('exam.categories', $code)->with('error', 'Silakan mulai mata pelajaran terlebih dahulu.');
        }

        if ($status->finished_at) {
            return redirect()->route('exam.categories', $code)->with('error', 'Anda sudah menyelesaikan mata pelajaran ini.');
        }

        // Get questions specifically for this category
        $questions = $participant->questions()
            ->where('category_id', $sessionCategory->category_id)
            ->with('category')
            ->get();
        
        // Calculate remaining time for this category
        $startTime = \Carbon\Carbon::parse($status->started_at);
        $endTime = $startTime->copy()->addMinutes((int) $sessionCategory->duration);
        $remainingSeconds = max(0, now()->diffInSeconds($endTime, false));

        if ($remainingSeconds <= 0) {
            // Auto submit
            $status->update(['finished_at' => now()]);
            return redirect()->route('exam.categories', $code)->with('error', 'Waktu mata pelajaran ini sudah habis.');
        }

        return view('exam.main', compact('session', 'participant', 'questions', 'remainingSeconds', 'sessionCategory'));
    }

    public function submitCategory(Request $request, $code, $categoryId)
    {
        $participant = $this->getParticipant($code);
        if (!$participant) return response()->json(['status' => 'error', 'message' => 'Not found'], 404);

        $answers = $request->input('answers', []);
        
        DB::transaction(function () use ($participant, $answers, $categoryId, $request) {
            foreach ($answers as $questionId => $answerData) {
                $question = QuestionBank::find($questionId);
                if (!$question) continue;

                $answer = is_array($answerData) && isset($answerData['answer']) ? $answerData['answer'] : $answerData;
                $isDoubtful = is_array($answerData) && isset($answerData['is_doubtful']) ? $answerData['is_doubtful'] : false;

                $isCorrect = false;
                $score = 0;
                
                // Check correctness based on question type
                $correctArr = (array) $question->correct_answer;
                $options = (array) $question->options;
                
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
                        
                        // 1. Hitung total jawaban yang seharusnya benar
                        $totalCorrectAvailable = count($normalizedCorrect);
                        
                        // 2. Hitung berapa jawaban benar yang berhasil ditebak user
                        $correctSelected = count(array_intersect($normalizedAnswers, $normalizedCorrect));
                        
                        // 4. Hitung jawaban benar bersih (tanpa penalti)
                        $netCorrect = $correctSelected;
                        
                        // 5. Hitung persentase
                        $percentage = $totalCorrectAvailable > 0 ? ($netCorrect / $totalCorrectAvailable) : 0;
                        
                        // 6. Tentukan skor dan status
                        $score = round($percentage * ($question->score_correct ?? 1), 2);
                        
                        // Jika netCorrect sama dengan total kunci jawaban, anggap benar sempurna
                        if ($netCorrect === $totalCorrectAvailable) {
                            $isCorrect = true;
                        } else if ($percentage == 0) {
                            // Jika persentase 0 (salah semua / netral), berikan skor salah
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

                UserAnswer::updateOrCreate(
                    [
                        'participant_id' => $participant->id,
                        'question_bank_id' => $questionId
                    ],
                    [
                        'exam_session_id' => $participant->exam_session_id,
                        'answer' => $answer,
                        'is_correct' => $isCorrect,
                        'score' => $score,
                        'is_doubtful' => $isDoubtful
                    ]
                );
            }

            if ($request->has('finish_category') && $request->finish_category) {
                \App\Models\ParticipantCategoryStatus::where('exam_session_participant_id', $participant->id)
                    ->where('exam_session_category_id', $categoryId)
                    ->update(['finished_at' => now()]);
            }
        });

        return response()->json(['status' => 'success']);
    }

    private function generateParticipantQuestions(ExamSessionParticipant $participant)
    {
        $session = $participant->examSession;
        
        // Fetch raw IDs from pivot to guarantee duplicates are included
        $questionIds = DB::table('session_questions')
            ->where('exam_session_id', $session->id)
            ->pluck('question_bank_id')
            ->toArray();
            
        shuffle($questionIds);
        
        DB::table('participant_questions')->where('participant_id', $participant->id)->delete();
        $insertData = [];
        $now = now();
        foreach ($questionIds as $index => $id) {
            $insertData[] = [
                'participant_id' => $participant->id,
                'question_bank_id' => $id,
                'order' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now
            ];
        }
        
        if (!empty($insertData)) {
            foreach (array_chunk($insertData, 500) as $chunk) {
                DB::table('participant_questions')->insert($chunk);
            }
        }
    }

    public function finishSession(Request $request, $code)
    {
        $participant = $this->getParticipant($code);
        if (!$participant) return response()->json(['status' => 'error'], 404);

        $participant->update(['finished_at' => now()]);
        session()->forget('participant_id');

        // Generate result for all participants so result page is always available
        $assessmentService = new \App\Services\AssessmentService();
        $assessmentService->calculateIRT($participant->exam_session_id);

        // Generate Aggregate AI Analysis only for Premium participants
        if ($participant->privilege === 'premium') {
            $registrations = ExamSessionParticipant::where('user_id', $participant->user_id)
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
                    $jsonAnalysis = is_array($analysis) ? $analysis : json_decode($analysis, true);
                    \App\Models\AggregateAiAnalysis::updateOrCreate(
                        ['user_id' => $participant->user_id, 'exam_session_id' => $participant->exam_session_id],
                        ['analysis_data' => $jsonAnalysis]
                    );
                }
            }
        }

        return response()->json(['status' => 'success', 'message' => 'Ujian berhasil diselesaikan secara keseluruhan.']);
    }

    public function success($code)
    {
        $participant = $this->getParticipant($code);
        if (!$participant) {
            return redirect()->route('participant.dashboard');
        }

        $session = $participant->examSession;
        $rawScore = 0;
        $userAnswers = \App\Models\UserAnswer::where('participant_id', $participant->id)->get();
        $participantQuestions = $participant->questions()->get();
        
        foreach ($session->sessionCategories as $sessionCategory) {
            $catId = $sessionCategory->category_id;
            $catQuestions = $participantQuestions->where('category_id', $catId);
            $maxPossiblePoints = $catQuestions->sum('score_correct');
            
            $participantPoints = 0;
            foreach ($catQuestions as $q) {
                $ans = $userAnswers->where('question_bank_id', $q->id)->first();
                if ($ans) {
                    $participantPoints += $ans->score;
                }
            }
            
            if ($maxPossiblePoints > 0) {
                $scaledScore = ($participantPoints / $maxPossiblePoints) * $sessionCategory->max_score_raw;
                $rawScore += max(0, min($scaledScore, $sessionCategory->max_score_raw));
            }
        }
        $rawScore = number_format($rawScore, 2);
        
        $answeredQuestions = \App\Models\UserAnswer::where('participant_id', $participant->id)->count();
        $totalQuestions = $session->questions()->count();

        $categoryScores = [];
        foreach ($session->sessionCategories as $sc) {
            $catId = $sc->category_id;
            
            $catAnswers = \App\Models\UserAnswer::where('participant_id', $participant->id)
                ->whereHas('question', function($q) use ($catId) {
                    $q->where('category_id', $catId);
                })->get();
                
            $categoryScores[] = [
                'name' => $sc->category->name,
                'score' => $catAnswers->sum('score'),
                'answered' => $catAnswers->count(),
                'total' => $sc->total_questions
            ];
        }

        return view('exam.success', compact('session', 'participant', 'rawScore', 'answeredQuestions', 'totalQuestions', 'categoryScores'));
    }
}


