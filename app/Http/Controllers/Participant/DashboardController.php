<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\AggregateAiAnalysis;
use App\Models\ExamResult;
use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Services\AIService;
use App\Traits\ResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    use ResponseTrait;

    public function index()
    {
        $user = auth()->user();

        $registrations = ExamSessionParticipant::where('user_id', $user->id)
            ->with([
                'examSession.sessionCategories.category',
                'examSession.sessionCategories.subCategories.subCategory',
                'result',
            ])
            ->orderBy('created_at', 'asc')
            ->get();

        $groupedRegistrations = $registrations->groupBy('exam_session_id');

        $scoreChartData = $registrations
            ->filter(fn ($registration) => $registration->finished_at && $registration->result?->irt_score !== null)
            ->sortBy(fn ($registration) => $registration->finished_at ?? $registration->created_at)
            ->values()
            ->map(function ($registration, $index) {
                $sessionName = $registration->examSession->name ?? 'Sesi Ujian';
                $attemptLabel = $registration->finished_at
                    ? Carbon::parse($registration->finished_at)->format('d M Y')
                    : 'Percobaan '.($index + 1);

                return [
                    'label' => $sessionName.' - '.$attemptLabel,
                    'irt_score' => round((float) $registration->result->irt_score, 2),
                    'predicate' => $registration->examSession->predicateForIrtScore((float) $registration->result->irt_score),
                ];
            });

        $scoreChartData = [
            'labels' => $scoreChartData->pluck('label')->all(),
            'irt_scores' => $scoreChartData->pluck('irt_score')->all(),
            'predicates' => $scoreChartData->pluck('predicate')->all(),
        ];

        return view('participant.dashboard', compact('groupedRegistrations', 'scoreChartData'));
    }

    public function showSession($sessionId)
    {
        $user = auth()->user();

        $registrations = ExamSessionParticipant::where('user_id', $user->id)
            ->where('exam_session_id', $sessionId)
            ->with([
                'examSession.sessionCategories.category',
                'examSession.sessionCategories.subCategories.subCategory',
                'result',
            ])
            ->orderBy('created_at', 'asc')
            ->get();

        if ($registrations->isEmpty()) {
            return redirect()->route('participant.dashboard')->with('error', 'Sesi ujian tidak ditemukan atau Anda tidak memiliki akses.');
        }

        $latestRegistration = $registrations->last();
        $session = $latestRegistration->examSession;

        $now = now();
        $start = Carbon::parse($session->start_date.' '.$session->start_time);
        $end = Carbon::parse($session->end_date.' '.$session->end_time);
        $isPastEnd = $now->gt($end);
        $isBeforeStart = $now->lt($start);
        $isClosed = ! $session->is_active || $isPastEnd;

        $sessionCategories = $session->sessionCategories;
        $totalDuration = $sessionCategories->sum('duration');
        $totalQuestions = $sessionCategories->sum('total_questions');

        return view('participant.session_detail', compact(
            'session',
            'registrations',
            'latestRegistration',
            'sessionCategories',
            'totalDuration',
            'totalQuestions',
            'isPastEnd',
            'isBeforeStart',
            'isClosed',
            'start',
            'end'
        ));
    }

    public function showResult($registrationId)
    {
        $userId = auth()->id();

        $basicRegistration = ExamSessionParticipant::with('examSession')->where('user_id', $userId)->findOrFail($registrationId);

        $resultData = ExamResult::where('participant_id', $registrationId)->first();

        if (! $resultData || $resultData->irt_score === null) {
            // Jika hasil belum ada atau IRT belum selesai dihitung, kembalikan ke success page (polling)
            return redirect()->route('exam.success', $basicRegistration->examSession->code)
                ->with('info', 'Sedang menghitung hasil Anda...');
        }

        $registration = ExamSessionParticipant::with([
            'examSession.sessionCategories.category',
            'result.categoryResults.category',
        ])->find($registrationId);

        return view('participant.result', compact('registration'));
    }

    public function showReview($registrationId)
    {
        $user = auth()->user();

        $basicRegistration = ExamSessionParticipant::where('user_id', $user->id)
            ->with(['examSession', 'result'])
            ->findOrFail($registrationId);

        if (! $basicRegistration->finished_at) {
            return redirect()->route('participant.dashboard')->with('error', 'Ujian belum selesai.');
        }

        if ($basicRegistration->result?->irt_score === null) {
            return redirect()->route('exam.success', $basicRegistration->examSession->code)
                ->with('info', 'Hasil IRT masih dihitung. Silakan coba lagi sesaat lagi.');
        }

        // Cache the heavy data since it's immutable after finishing
        $registration = ExamSessionParticipant::with([
            'examSession.sessionCategories.category',
            'questions.category',
            'questions.subCategory',
            'userAnswers',
            'result.categoryResults.category',
        ])->find($registrationId);

        // Map answers for easy access in view
        $answers = $registration->userAnswers->pluck('answer', 'question_bank_id')->toArray();
        $userAnswersMapped = $registration->userAnswers->keyBy('question_bank_id');

        $mapelStats = [];

        foreach ($registration->questions as $question) {
            $catName = $question->category->name ?? 'Lainnya';
            $subCatName = $question->subCategory->name ?? 'Lainnya';

            if (! isset($mapelStats[$catName])) {
                $mapelStats[$catName] = [
                    'benar' => 0,
                    'salah' => 0,
                    'subMapel' => [],
                ];
            }
            if (! isset($mapelStats[$catName]['subMapel'][$subCatName])) {
                $mapelStats[$catName]['subMapel'][$subCatName] = ['benar' => 0, 'salah' => 0];
            }

            $userAnswer = $userAnswersMapped->get($question->id);
            if ($userAnswer && $userAnswer->is_correct) {
                $mapelStats[$catName]['benar']++;
                $mapelStats[$catName]['subMapel'][$subCatName]['benar']++;
            } else {
                $mapelStats[$catName]['salah']++;
                $mapelStats[$catName]['subMapel'][$subCatName]['salah']++;
            }
        }

        $chartDataMapel = [
            'labels' => array_keys($mapelStats),
            'benar' => array_column($mapelStats, 'benar'),
            'salah' => array_column($mapelStats, 'salah'),
            'details' => $mapelStats,
        ];

        $cacheData = [
            'registration' => $registration,
            'answers' => $answers,
            'chartDataMapel' => $chartDataMapel,
        ];

        return view('participant.review', $cacheData);
    }

    public function showReviewCategory($registrationId, $categoryId)
    {
        $user = auth()->user();

        $basicRegistration = ExamSessionParticipant::where('user_id', $user->id)->findOrFail($registrationId);

        if (! $basicRegistration->finished_at) {
            return redirect()->route('participant.dashboard')->with('error', 'Ujian belum selesai.');
        }

        $registration = ExamSessionParticipant::with(['examSession', 'questions' => function ($q) use ($categoryId) {
            $q->where('category_id', $categoryId)->with('category');
        }, 'userAnswers' => function ($q) use ($categoryId) {
            $q->whereHas('question', function ($sq) use ($categoryId) {
                $sq->where('category_id', $categoryId);
            });
        }])->find($registrationId);

        if ($registration->questions->isEmpty()) {
            $cacheData = null;
        } else {
            // Map answers for easy access in view
            $answers = $registration->userAnswers->pluck('answer', 'question_bank_id')->toArray();
            $category = $registration->questions->first()->category;

            $cacheData = [
                'registration' => $registration,
                'answers' => $answers,
                'category' => $category,
            ];
        }

        if (! $cacheData) {
            return redirect()->route('participant.review', $registrationId)->with('error', 'Mata pelajaran tidak ditemukan.');
        }

        return view('participant.review_category', $cacheData);
    }

    public function generateAIAnalysis(Request $request, $registrationId)
    {
        $user = auth()->user();

        $registration = ExamSessionParticipant::where('user_id', $user->id)
            ->with(['examSession.sessionCategories', 'result', 'questions.category', 'userAnswers'])
            ->findOrFail($registrationId);

        if ($registration->result && $registration->result->ai_analysis) {
            return response()->json(['status' => 'error', 'message' => 'Analisis AI sudah digenerate sebelumnya.'], 400);
        }

        // Group performance by category
        $categoryStats = [];
        foreach ($registration->questions as $question) {
            $catName = $question->category->name;
            if (! isset($categoryStats[$catName])) {
                $categoryStats[$catName] = ['total' => 0, 'correct' => 0];
            }
            $categoryStats[$catName]['total']++;

            $answer = $registration->userAnswers->where('question_bank_id', $question->id)->first();
            if ($answer && $answer->is_correct) {
                $categoryStats[$catName]['correct']++;
            }
        }

        $aiService = new AIService;

        if ($registration->result?->irt_score === null) {
            return response()->json(['status' => 'error', 'message' => 'Hasil IRT masih dihitung. Silakan coba lagi.'], 409);
        }

        $totalQuestions = $registration->questions->count();
        $totalAnswered = $registration->userAnswers->count();
        $totalCorrect = $registration->userAnswers->where('is_correct', true)->count();
        $totalIncorrect = $totalAnswered - $totalCorrect;
        $totalBlank = $totalQuestions - $totalAnswered;
        $irtScore = (float) $registration->result->irt_score;
        $predicate = $registration->examSession->predicateForIrtScore($irtScore);

        $analysis = $aiService->generateAnalysis([
            'participant_name' => $user->name,
            'session_name' => $registration->examSession->name,
            'correct' => $totalCorrect,
            'incorrect' => $totalIncorrect,
            'blank' => $totalBlank,
            'total_score' => number_format($irtScore, 2)." (Predikat {$predicate})",
            'category_stats' => $categoryStats,
        ]);

        if ($analysis) {
            $jsonAnalysis = is_array($analysis) ? json_encode($analysis) : $analysis;

            $registration->result->update(['ai_analysis' => $jsonAnalysis]);

            return response()->json([
                'status' => 'success',
                'message' => 'Analisis AI berhasil dibuat!',
                'analysis' => $analysis,
            ]);
        }

        return response()->json(['status' => 'error', 'message' => 'Gagal generate analisis AI.'], 500);
    }

    public function joinSession(Request $request)
    {
        $request->validate([
            'session_code' => 'required|string|exists:exam_sessions,code',
            'access_code' => 'required|string',
        ]);

        $session = ExamSession::where('code', $request->session_code)->firstOrFail();

        // Find registration for this user in this session
        $registration = ExamSessionParticipant::where('exam_session_id', $session->id)
            ->where('user_id', auth()->id())
            ->where('access_code', $request->access_code)
            ->first();

        if (! $registration) {
            return back()->with('error', 'Kode akses tidak valid untuk sesi ini.');
        }

        if (! $session->is_active) {
            return back()->with('error', 'Sesi ujian ini sedang ditutup.');
        }

        // Store registration ID in session for the ExamController to pick up
        session(['participant_id' => $registration->id]);

        Cache::forget('dashboard_registrations_v6_user_'.auth()->id());

        return redirect()->route('exam.main', ['code' => $session->code]);
    }

    public function retakeSession(Request $request, $sessionId)
    {
        $user = auth()->user();

        $session = ExamSession::findOrFail($sessionId);
        if (! $session->is_active) {
            return back()->with('error', 'Sesi ujian ini sedang ditutup atau tidak aktif.');
        }

        // Get the last registration
        $lastRegistration = ExamSessionParticipant::where('user_id', $user->id)
            ->where('exam_session_id', $session->id)
            ->latest('id')
            ->firstOrFail();

        if ($lastRegistration->privilege !== 'premium') {
            return back()->with('error', 'Fitur Kerjakan Ulang khusus untuk pengguna Premium.');
        }

        if (! $lastRegistration->finished_at) {
            return back()->with('error', 'Selesaikan percobaan Anda sebelumnya terlebih dahulu.');
        }

        // Generate new unique code
        do {
            $code = strtoupper(Str::random(6));
        } while (ExamSessionParticipant::where('access_code', $code)->exists());

        // Create new attempt
        $newRegistration = ExamSessionParticipant::create([
            'exam_session_id' => $session->id,
            'user_id' => $user->id,
            'name' => $lastRegistration->name,
            'whatsapp' => $lastRegistration->whatsapp,
            'address' => $lastRegistration->address,
            'access_code' => $code,
            'privilege' => $lastRegistration->privilege,
        ]);

        session(['participant_id' => $newRegistration->id]);

        Cache::forget('dashboard_registrations_v6_user_'.auth()->id());

        return redirect()->route('exam.terms', $session->code)->with('success', 'Percobaan baru berhasil dibuat. Silakan baca term sebelum mulai mengerjakan.');
    }

    public function generateAggregateAnalysis($sessionId)
    {
        $user = auth()->user();

        $registrations = ExamSessionParticipant::where('user_id', $user->id)
            ->where('exam_session_id', $sessionId)
            ->with(['result', 'examSession'])
            ->orderBy('id', 'asc')
            ->get();

        if ($registrations->isEmpty() || $registrations->last()->privilege !== 'premium') {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        // Check if an analysis already exists and is up to date (we can just generate it fresh or check attempts count)
        $aggregateRecord = AggregateAiAnalysis::where('user_id', $user->id)
            ->where('exam_session_id', $sessionId)
            ->first();

        // If the number of finished attempts is the same as the recorded attempts, maybe don't regenerate
        // But for simplicity, we just generate fresh on request.

        $attemptsData = [];
        foreach ($registrations as $index => $reg) {
            if ($reg->result?->irt_score !== null) {
                $attemptsData[] = [
                    'attempt_number' => $index + 1,
                    'total_correct' => $reg->result->total_correct,
                    'total_incorrect' => $reg->result->total_incorrect,
                    'total_blank' => $reg->result->total_blank,
                    'irt_score' => $reg->result->irt_score,
                    'predicate' => $reg->examSession->predicateForIrtScore((float) $reg->result->irt_score),
                ];
            }
        }

        if (count($attemptsData) < 2) {
            return response()->json(['status' => 'error', 'message' => 'Diperlukan minimal 2 percobaan yang sudah selesai untuk dianalisis.'], 400);
        }

        $aiService = new AIService;
        $analysis = $aiService->generateAggregateAnalysis([
            'participant_name' => $user->name,
            'session_name' => $registrations->first()->examSession->name,
            'attempts' => $attemptsData,
        ]);

        if ($analysis) {
            $jsonAnalysis = is_array($analysis) ? $analysis : json_decode($analysis, true);

            AggregateAiAnalysis::updateOrCreate(
                ['user_id' => $user->id, 'exam_session_id' => $sessionId],
                ['analysis_data' => $jsonAnalysis]
            );

            return response()->json(['status' => 'success', 'data' => $jsonAnalysis]);
        }

        return response()->json(['status' => 'error', 'message' => 'Gagal generate analisis AI agregat.'], 500);
    }

    public function showStatistics($sessionId)
    {
        $user = auth()->user();

        $session = ExamSession::findOrFail($sessionId);

        // Verify user has finished at least one attempt in this session
        $hasFinished = ExamSessionParticipant::where('user_id', $user->id)
            ->where('exam_session_id', $sessionId)
            ->whereNotNull('finished_at')
            ->exists();

        if (! $hasFinished) {
            return redirect()->route('participant.dashboard')->with('error', 'Anda harus menyelesaikan ujian terlebih dahulu untuk melihat statistik.');
        }

        // Determine if session is closed
        $now = now();
        $end = Carbon::parse($session->end_date.' '.$session->end_time);
        $isClosed = ! $session->is_active || $now->gt($end);

        $cacheKey = "statistics_session_{$sessionId}";

        $rankings = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($sessionId) {
            // Get all results for this session
            $allResults = ExamResult::where('exam_session_id', $sessionId)
                ->whereNotNull('irt_score')
                ->with(['participant.user'])
                ->get();

            // Group by user_id to get the best attempt per user
            $bestResults = collect();
            $groupedByUser = $allResults->groupBy(function ($result) {
                return $result->participant->user_id ?? $result->participant->name;
            });

            foreach ($groupedByUser as $userId => $userResults) {
                $bestResult = $userResults->sort(function ($left, $right) {
                    return $right->irt_score <=> $left->irt_score
                        ?: $right->total_correct <=> $left->total_correct
                        ?: $left->id <=> $right->id;
                })->first();
                $bestResults->push($bestResult);
            }

            return $bestResults->sort(function ($left, $right) {
                return $right->irt_score <=> $left->irt_score
                    ?: $right->total_correct <=> $left->total_correct
                    ?: $left->id <=> $right->id;
            })->values();
        });

        return view('participant.statistics', compact('session', 'isClosed', 'rankings', 'user'));
    }
}
