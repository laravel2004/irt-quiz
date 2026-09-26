<?php

namespace App\Http\Controllers;

use App\Jobs\CalculateIRTJob;
use App\Models\ExamResult;
use App\Models\ExamSession;
use App\Models\ExamSessionCategory;
use App\Models\ExamSessionParticipant;
use App\Models\ParticipantCategoryStatus;
use App\Models\UserAnswer;
use App\Services\ExamSessionService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

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

        $strValue = (string) $value;
        $stripped = trim(preg_replace('/\s+/', ' ', strip_tags($strValue)));

        if ($stripped === '' && trim($strValue) !== '') {
            $stripped = trim(preg_replace('/\s+/', ' ', $strValue));
        }

        return strtolower($stripped);
    }

    private function resolveCorrectIndices(array $correctAnswers, array $options): array
    {
        $indices = [];
        foreach ($correctAnswers as $correctAnswer) {
            $key = (string) $correctAnswer;
            $upperKey = strtoupper(trim($key));
            if (preg_match('/^[A-Z]$/', $upperKey)) {
                $index = ord($upperKey) - 65;
                if (array_key_exists($index, $options)) {
                    $indices[] = (string) $index;

                    continue;
                }
            }
            if (array_key_exists($key, $options)) {
                $indices[] = (string) $key;
            }
        }

        return array_values(array_unique($indices));
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

    private function countActiveParticipants(): int
    {
        return ExamSessionParticipant::activeInExam()->count();
    }

    public function checkCapacity()
    {
        $limit = config('exam.concurrent_limit', 30);
        $activeCount = $this->countActiveParticipants();

        return response()->json([
            'is_full' => $activeCount >= $limit,
            'active_count' => $activeCount,
            'limit' => $limit,
        ]);
    }

    public function terms($code)
    {
        $participant = $this->getParticipant($code);

        if (! $participant) {
            return redirect()->route('participant.dashboard')->with('error', 'Anda belum terdaftar di sesi ujian ini.');
        }

        $session = $participant->examSession;

        // Validasi waktu aktif
        $now = now();
        $start = Carbon::parse($session->start_date.' '.$session->start_time);
        $end = Carbon::parse($session->end_date.' '.$session->end_time);

        if (! $session->is_active) {
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
        if (! $participant) {
            return redirect()->route('participant.dashboard');
        }

        $session = $participant->examSession;

        // Cek limit hanya untuk peserta yang BELUM pernah started
        // (Peserta yang lanjut dari sesi sebelumnya tidak kena limit)
        if (! $participant->started_at) {
            $limit = config('exam.concurrent_limit', 30);
            $activeCount = $this->countActiveParticipants();

            if ($activeCount >= $limit) {
                return back()->with('exam_full', true);
            }
        }

        // Generate questions if not exist
        if ($session->questions()->count() == 0) {
            try {
                $this->sessionService->generateSessionQuestions($session->id);
            } catch (DomainException $exception) {
                return back()->withErrors(['exam' => $exception->getMessage()]);
            }
        }

        if ($participant->questions()->count() == 0) {
            $this->generateParticipantQuestions($participant);
        }

        // Mark started if not already
        if (! $participant->started_at) {
            $participant->update(['started_at' => now()]);
        }

        return redirect()->route('exam.categories', $session->code);
    }

    public function categories($code)
    {
        $participant = $this->getParticipant($code);
        if (! $participant) {
            return redirect()->route('participant.dashboard');
        }

        $session = $participant->examSession;
        if (! $session->is_active) {
            return redirect()->route('participant.dashboard')->with('error', 'Sesi ujian telah ditutup oleh administrator.');
        }

        // Cek status mapel
        $categoryStatuses = ParticipantCategoryStatus::where('exam_session_participant_id', $participant->id)
            ->get()->keyBy('exam_session_category_id');

        return view('exam.categories', compact('session', 'participant', 'categoryStatuses'));
    }

    public function startCategory(Request $request, $code, $categoryId)
    {
        $participant = $this->getParticipant($code);
        if (! $participant) {
            return redirect()->route('participant.dashboard');
        }

        if (! $participant->examSession->is_active) {
            return redirect()->route('participant.dashboard')->with('error', 'Sesi ujian telah ditutup oleh administrator.');
        }

        $sessionCategory = ExamSessionCategory::where('exam_session_id', $participant->exam_session_id)
            ->where('id', $categoryId)
            ->firstOrFail();

        $status = ParticipantCategoryStatus::firstOrCreate(
            [
                'exam_session_participant_id' => $participant->id,
                'exam_session_category_id' => $sessionCategory->id,
            ],
            [
                'started_at' => now(),
            ]
        );

        return redirect()->route('exam.main', ['code' => $code, 'id' => $categoryId]);
    }

    public function main($code, $categoryId)
    {
        $participant = $this->getParticipant($code);
        if (! $participant) {
            return redirect()->route('participant.dashboard');
        }

        $session = $participant->examSession;
        if (! $session->is_active) {
            return redirect()->route('participant.dashboard')->with('error', 'Sesi ujian telah ditutup oleh administrator.');
        }

        $sessionCategory = ExamSessionCategory::with('category')
            ->where('exam_session_id', $participant->exam_session_id)
            ->findOrFail($categoryId);

        $status = ParticipantCategoryStatus::where('exam_session_participant_id', $participant->id)
            ->where('exam_session_category_id', $categoryId)
            ->first();

        if (! $status || ! $status->started_at) {
            return redirect()->route('exam.categories', $code)->with('error', 'Silakan mulai mata pelajaran terlebih dahulu.');
        }

        if ($status->finished_at) {
            return redirect()->route('exam.categories', $code)->with('error', 'Anda sudah menyelesaikan mata pelajaran ini.');
        }

        $questions = $participant->questions()
            ->where('category_id', $sessionCategory->category_id)
            ->with('category')
            ->get();

        // Calculate remaining time for this category
        $startTime = Carbon::parse($status->started_at);
        $endTime = $startTime->copy()->addMinutes((int) $sessionCategory->duration);
        $remainingSeconds = max(0, now()->diffInSeconds($endTime, false));

        return view('exam.main', compact('session', 'participant', 'questions', 'remainingSeconds', 'sessionCategory'));
    }

    public function submitCategory(Request $request, $code, $categoryId)
    {
        $requestStartedAt = microtime(true);
        $transactionDurationMs = 0;
        $participant = null;

        try {
            $participant = $this->getParticipant($code);
            if (! $participant) {
                return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
            }

            $sessionCategory = ExamSessionCategory::where('exam_session_id', $participant->exam_session_id)
                ->findOrFail($categoryId);
            $status = ParticipantCategoryStatus::where('exam_session_participant_id', $participant->id)
                ->where('exam_session_category_id', $sessionCategory->id)
                ->first();

            if (! $status || ! $status->started_at) {
                return response()->json(['status' => 'error', 'message' => 'Mata pelajaran belum dimulai.'], 422);
            }

            if ($status->finished_at) {
                return response()->json([
                    'status' => 'success',
                    'saved_count' => 0,
                    'category_finished' => true,
                    'already_finished' => true,
                ]);
            }

            if (! $participant->examSession->is_active) {
                return response()->json(['status' => 'error', 'message' => 'Sesi ujian telah ditutup.'], 409);
            }

            $validated = $request->validate([
                'answers' => ['present', 'array'],
                'answers.*' => ['required', 'array'],
                'answers.*.answer' => ['nullable'],
                'answers.*.is_doubtful' => ['nullable', 'boolean'],
                'finish_category' => ['required', 'boolean'],
            ]);
            $answers = $validated['answers'];
            $questionIds = array_map('strval', array_keys($answers));
            $questions = $participant->questions()
                ->where('question_banks.category_id', $sessionCategory->category_id)
                ->whereIn('question_banks.id', $questionIds)
                ->get()
                ->keyBy('id');
            $allowedQuestionIds = $questions->keys()->map(fn ($id) => (string) $id)->all();

            if (array_diff($questionIds, $allowedQuestionIds) !== []) {
                throw ValidationException::withMessages([
                    'answers' => 'Terdapat soal yang tidak ditugaskan untuk peserta atau mata pelajaran ini.',
                ]);
            }

            $now = now();
            $upsertData = [];

            foreach ($answers as $questionId => $answerData) {
                $question = $questions->get($questionId);
                $answer = $answerData['answer'] ?? null;
                $isCorrect = false;
                $score = 0;
                $correctArr = (array) $question->correct_answer;
                $options = (array) $question->options;

                if ($question->type === 'pilihan_ganda' || $question->type === 'benar_salah') {
                    $correctIndex = $this->resolveCorrectIndices($correctArr, $options)[0] ?? null;

                    if (is_numeric($answer) && array_key_exists((int) $answer, $options)) {
                        $isCorrect = $correctIndex !== null && (string) $answer === $correctIndex;
                    } else {
                        $correctValue = $this->resolveCorrectValues($correctArr, $options)[0] ?? null;
                        $isCorrect = $correctValue !== null && $this->answersMatch($correctValue, $answer);
                    }

                    $score = $isCorrect ? ($question->score_correct ?? 1) : ($question->score_incorrect ?? 0);
                } elseif ($question->type === 'multiple_choice') {
                    $correctIndices = $this->resolveCorrectIndices($correctArr, $options);

                    if (is_array($answer)) {
                        $userIndices = [];
                        foreach ($answer as $val) {
                            if (is_numeric($val) && array_key_exists((int) $val, $options)) {
                                $userIndices[] = (string) $val;
                            }
                        }

                        $totalCorrectAvailable = count($correctIndices);
                        $correctSelected = count(array_intersect($userIndices, $correctIndices));
                        $wrongSelected = count(array_diff($userIndices, $correctIndices));
                        $isCorrect = $correctSelected === $totalCorrectAvailable && $wrongSelected === 0;
                        $netCorrect = max(0, $correctSelected - $wrongSelected);
                        $percentage = $totalCorrectAvailable > 0 ? $netCorrect / $totalCorrectAvailable : 0;
                        $score = $percentage == 0
                            ? ($question->score_incorrect ?? 0)
                            : round($percentage * ($question->score_correct ?? 1), 2);
                    } else {
                        $score = $question->score_incorrect ?? 0;
                    }
                } elseif ($question->type === 'multiple_benar_salah') {
                    if (is_array($answer)) {
                        $totalStatements = count($options);
                        $correctCount = 0;

                        foreach ($options as $idx => $optText) {
                            $userAnswer = $answer[(string) $idx] ?? null;
                            $shouldBeBenar = in_array((string) $idx, $correctArr);

                            if (($shouldBeBenar && $userAnswer === 'benar') || (! $shouldBeBenar && $userAnswer === 'salah')) {
                                $correctCount++;
                            }
                        }

                        $percentage = $totalStatements > 0 ? $correctCount / $totalStatements : 0;
                        $isCorrect = $correctCount === $totalStatements;
                        $score = $percentage == 0
                            ? ($question->score_incorrect ?? 0)
                            : round($percentage * ($question->score_correct ?? 1), 2);
                    } else {
                        $score = $question->score_incorrect ?? 0;
                    }
                }

                $upsertData[] = [
                    'participant_id' => $participant->id,
                    'exam_session_id' => $participant->exam_session_id,
                    'question_bank_id' => $questionId,
                    'answer' => $answer === null ? null : json_encode($answer, JSON_UNESCAPED_UNICODE),
                    'is_correct' => $isCorrect,
                    'score' => $score,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $transactionStartedAt = microtime(true);
            $alreadyFinished = DB::transaction(function () use ($participant, $sessionCategory, $validated, $upsertData) {
                $lockedStatus = ParticipantCategoryStatus::where('exam_session_participant_id', $participant->id)
                    ->where('exam_session_category_id', $sessionCategory->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedStatus->finished_at) {
                    return true;
                }

                foreach (array_chunk($upsertData, 500) as $chunk) {
                    UserAnswer::upsert(
                        $chunk,
                        ['participant_id', 'question_bank_id'],
                        ['answer', 'is_correct', 'score', 'updated_at']
                    );
                }

                if ($validated['finish_category']) {
                    $lockedStatus->update(['finished_at' => now()]);
                }

                return false;
            });
            $transactionDurationMs = (int) round((microtime(true) - $transactionStartedAt) * 1000);
            $durationMs = (int) round((microtime(true) - $requestStartedAt) * 1000);

            if ($durationMs >= 2000) {
                Log::warning('Slow exam category submit', [
                    'participant_id' => $participant->id,
                    'exam_session_id' => $participant->exam_session_id,
                    'exam_session_category_id' => $sessionCategory->id,
                    'answer_count' => count($answers),
                    'duration_ms' => $durationMs,
                    'transaction_duration_ms' => $transactionDurationMs,
                    'status' => 'success',
                    'cf_ray' => $request->header('CF-Ray'),
                ]);
            }

            return response()->json([
                'status' => 'success',
                'saved_count' => $alreadyFinished ? 0 : count($upsertData),
                'category_finished' => $alreadyFinished || $validated['finish_category'],
                'already_finished' => $alreadyFinished,
            ]);
        } catch (Throwable $exception) {
            Log::error('Exam category submit failed', [
                'participant_id' => $participant?->id,
                'exam_session_id' => $participant?->exam_session_id,
                'exam_session_category_id' => $categoryId,
                'answer_count' => is_array($request->input('answers')) ? count($request->input('answers')) : 0,
                'duration_ms' => (int) round((microtime(true) - $requestStartedAt) * 1000),
                'transaction_duration_ms' => $transactionDurationMs,
                'status' => 'failed',
                'exception' => $exception::class,
                'cf_ray' => $request->header('CF-Ray'),
            ]);

            throw $exception;
        }
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
                'updated_at' => $now,
            ];
        }

        if (! empty($insertData)) {
            foreach (array_chunk($insertData, 500) as $chunk) {
                DB::table('participant_questions')->insert($chunk);
            }
        }
    }

    public function finishSession(Request $request, $code)
    {
        $participant = $this->getParticipant($code);
        if (! $participant) {
            return response()->json(['status' => 'error'], 404);
        }

        $participant->update(['finished_at' => now()]);
        session()->forget('participant_id');

        // Dispatch IRT calculation to Queue
        CalculateIRTJob::dispatch($participant->exam_session_id);

        // Invalidate dashboard cache
        Cache::forget("dashboard_registrations_v6_user_{$participant->user_id}");

        return response()->json(['status' => 'success', 'message' => 'Ujian berhasil diselesaikan secara keseluruhan.']);
    }

    public function checkStatus($code)
    {
        $participant = $this->getParticipant($code);
        if (! $participant) {
            return response()->json(['status' => 'error'], 404);
        }

        $result = ExamResult::where('participant_id', $participant->id)->first();
        if ($result && $result->irt_score !== null) {
            return response()->json(['status' => 'done']);
        }

        return response()->json(['status' => 'processing']);
    }

    public function success($code)
    {
        $participant = $this->getParticipant($code);
        if (! $participant) {
            return redirect()->route('participant.dashboard');
        }

        $session = $participant->examSession;
        $result = ExamResult::with('categoryResults.category')->where('participant_id', $participant->id)->first();

        // If background job hasn't finished calculating
        if (! $result || $result->irt_score === null) {
            return view('exam.success', [
                'isCalculating' => true,
                'session' => $session,
                'code' => $code,
            ]);
        }

        $rawScore = number_format($result->score, 2);
        $irtScore = number_format($result->irt_score, 2);
        $rawPredicate = $session->predicateForRawScore((float) $result->score);
        $irtPredicate = $session->predicateForIrtScore((float) $result->irt_score);

        $answeredQuestions = UserAnswer::where('participant_id', $participant->id)->count();
        $totalQuestions = $session->questions()->count();

        $categoryScores = [];
        foreach ($result->categoryResults as $cr) {
            $catId = $cr->category_id;
            $sc = $session->sessionCategories->where('category_id', $catId)->first();

            $catAnswersCount = UserAnswer::where('participant_id', $participant->id)
                ->whereHas('question', function ($q) use ($catId) {
                    $q->where('category_id', $catId);
                })->count();

            $categoryScores[] = [
                'name' => $cr->category->name,
                'raw_score' => number_format($cr->score, 2),
                'irt_score' => number_format($cr->irt_score, 2),
                'answered' => $catAnswersCount,
                'total' => $sc ? $sc->total_questions : 0,
            ];
        }

        return view('exam.success', [
            'isCalculating' => false,
            'session' => $session,
            'participant' => $participant,
            'rawScore' => $rawScore,
            'irtScore' => $irtScore,
            'rawPredicate' => $rawPredicate,
            'irtPredicate' => $irtPredicate,
            'answeredQuestions' => $answeredQuestions,
            'totalQuestions' => $totalQuestions,
            'categoryScores' => $categoryScores,
            'code' => $code,
        ]);
    }
}
