<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ResponseTrait;

    public function index()
    {
        $user = auth()->user();
        
        // Get sessions where this user is registered
        $registrations = ExamSessionParticipant::where('user_id', $user->id)
            ->with(['examSession', 'result'])
            ->latest()
            ->get();

        return view('participant.dashboard', compact('registrations'));
    }

    public function showResult($registrationId)
    {
        $registration = ExamSessionParticipant::where('user_id', auth()->id())
            ->with(['examSession', 'result'])
            ->findOrFail($registrationId);

        if (!$registration->result) {
            return redirect()->route('participant.dashboard')->with('error', 'Hasil belum tersedia.');
        }

        return view('participant.result', compact('registration'));
    }

    public function showReview($registrationId)
    {
        $user = auth()->user();
        if ($user->role !== 'premium') {
            return redirect()->route('participant.dashboard')->with('error', 'Fitur pembahasan hanya tersedia untuk akun Premium.');
        }

        $registration = ExamSessionParticipant::where('user_id', $user->id)
            ->with(['examSession', 'questions.category', 'userAnswers'])
            ->findOrFail($registrationId);

        if (!$registration->finished_at) {
            return redirect()->route('participant.dashboard')->with('error', 'Ujian belum selesai.');
        }

        // Map answers for easy access in view
        $answers = $registration->userAnswers->pluck('answer', 'question_bank_id')->toArray();

        return view('participant.review', compact('registration', 'answers'));
    }

    public function generateAIAnalysis(Request $request, $registrationId)
    {
        $user = auth()->user();
        if ($user->role !== 'premium') {
            return response()->json(['status' => 'error', 'message' => 'Hanya untuk pengguna Premium.'], 403);
        }

        $registration = ExamSessionParticipant::where('user_id', $user->id)
            ->with(['examSession', 'result', 'questions.category', 'userAnswers'])
            ->findOrFail($registrationId);

        if (!$registration->result) {
            return response()->json(['status' => 'error', 'message' => 'Hasil belum tersedia.'], 400);
        }

        if ($registration->result->ai_analysis) {
            return response()->json(['status' => 'error', 'message' => 'Analisis AI sudah digenerate sebelumnya.'], 400);
        }

        // Group performance by category
        $categoryStats = [];
        foreach ($registration->questions as $question) {
            $catName = $question->category->name;
            if (!isset($categoryStats[$catName])) {
                $categoryStats[$catName] = ['total' => 0, 'correct' => 0];
            }
            $categoryStats[$catName]['total']++;
            
            $answer = $registration->userAnswers->where('question_bank_id', $question->id)->first();
            if ($answer && $answer->is_correct) {
                $categoryStats[$catName]['correct']++;
            }
        }

        $aiService = new \App\Services\AIService();
        $analysis = $aiService->generateAnalysis([
            'participant_name' => $user->name,
            'session_name' => $registration->examSession->name,
            'correct' => $registration->result->total_correct,
            'incorrect' => $registration->result->total_incorrect,
            'blank' => $registration->result->total_blank,
            'irt_score' => $registration->result->irt_score,
            'max_irt' => $registration->examSession->max_score_irt,
            'category_stats' => $categoryStats
        ]);

        if (!$analysis) {
            return response()->json(['status' => 'error', 'message' => 'Gagal generate analisis AI. Silakan coba lagi.'], 500);
        }

        $registration->result->update([
            'ai_analysis' => json_encode($analysis)
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Analisis AI berhasil dibuat!',
            'analysis' => $analysis
        ]);
    }

    public function joinSession(Request $request)
    {
        $request->validate([
            'session_code' => 'required|string|exists:exam_sessions,code',
            'access_code' => 'required|string'
        ]);

        $session = ExamSession::where('code', $request->session_code)->firstOrFail();
        
        // Find registration for this user in this session
        $registration = ExamSessionParticipant::where('exam_session_id', $session->id)
            ->where('user_id', auth()->id())
            ->where('access_code', $request->access_code)
            ->first();

        if (!$registration) {
            return back()->with('error', 'Kode akses tidak valid untuk sesi ini.');
        }

        if (!$session->is_active) {
            return back()->with('error', 'Sesi ujian ini sedang ditutup.');
        }

        // Store registration ID in session for the ExamController to pick up
        session(['participant_id' => $registration->id]);

        return redirect()->route('exam.main', ['code' => $session->code]);
    }
}
