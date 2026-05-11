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

    public function index()
    {
        return view('exam.login');
    }

    public function finish()
    {
        return view('exam.finish');
    }

    public function login(Request $request)
    {
        $request->validate(['access_code' => 'required|string|size:6']);

        $participant = ExamSessionParticipant::where('access_code', $request->access_code)
            ->with(['examSession.sessionCategories.category'])
            ->first();

        if (!$participant) {
            return back()->with('error', 'Kode akses tidak valid.');
        }

        $session = $participant->examSession;

        // Check if session is active and current time is within range
        $now = now();
        $start = \Carbon\Carbon::parse($session->start_date . ' ' . $session->start_time);
        $end = \Carbon\Carbon::parse($session->end_date . ' ' . $session->end_time);

        if (!$session->is_active) {
            return back()->with('error', 'Sesi ujian sedang ditutup oleh administrator.');
        }

        if ($now->lt($start)) {
            $formattedStart = $start->translatedFormat('d F Y, H:i');
            return back()->with('error', "Ujian belum dimulai. Silakan masuk kembali pada $formattedStart WIB.");
        }

        if ($now->gt($end)) {
            $formattedEnd = $end->translatedFormat('d F Y, H:i');
            return back()->with('error', "Sesi ujian telah berakhir pada $formattedEnd WIB.");
        }

        if ($participant->finished_at) {
            return back()->with('error', 'Anda sudah menyelesaikan ujian ini.');
        }

        // Store participant in session
        session(['participant_id' => $participant->id]);

        // Mark started if not already
        if (!$participant->started_at) {
            $participant->update(['started_at' => now()]);
        }

        return redirect()->route('exam.main');
    }

    public function main($code)
    {
        $participantId = session('participant_id');
        if (!$participantId) return redirect()->route('participant.dashboard')->with('error', 'Silakan masukkan kode akses terlebih dahulu.');

        $participant = ExamSessionParticipant::with(['examSession.sessionCategories.category'])->findOrFail($participantId);
        $session = $participant->examSession;

        if ($session->code !== $code) {
            return redirect()->route('participant.dashboard')->with('error', 'Sesi ujian tidak sesuai.');
        }

        // Mark started if not already
        if (!$participant->started_at) {
            $participant->update(['started_at' => now()]);
        }

        // Ensure session has questions
        if ($session->questions()->count() == 0) {
            $this->sessionService->generateSessionQuestions($session->id);
        }

        // If participant has no questions assigned yet, assign them now
        if ($participant->questions()->count() == 0) {
            $this->generateParticipantQuestions($participant);
        }

        $questions = $participant->questions()->with('category')->get();
        
        // Calculate remaining time
        $startTime = \Carbon\Carbon::parse($participant->started_at);
        $endTime = $startTime->copy()->addMinutes((int) $session->duration);
        $remainingSeconds = max(0, now()->diffInSeconds($endTime, false));

        if ($remainingSeconds <= 0) {
            return redirect()->route('participant.dashboard')->with('error', 'Waktu ujian Anda sudah habis.');
        }

        return view('exam.main', compact('session', 'participant', 'questions', 'remainingSeconds'));
    }

    private function generateParticipantQuestions(ExamSessionParticipant $participant)
    {
        $session = $participant->examSession;
        
        // Get question IDs from session
        $questionIds = $session->questions()->pluck('question_bank_id')->toArray();

        // Shuffle questions to randomize order for this specific participant
        shuffle($questionIds);

        // Map to pivot data with order
        $syncData = [];
        foreach ($questionIds as $index => $id) {
            $syncData[$id] = ['order' => $index + 1];
        }

        $participant->questions()->sync($syncData);
    }

    public function submit(Request $request)
    {
        $participantId = session('participant_id');
        if (!$participantId) return response()->json(['status' => 'error'], 403);

        $participant = ExamSessionParticipant::findOrFail($participantId);
        $participant->update(['finished_at' => now()]);

        $answers = $request->input('answers', []);
        
        foreach ($answers as $questionId => $answer) {
            $question = QuestionBank::find($questionId);
            if (!$question) continue;

            $isCorrect = false;
            
            // Check correctness based on question type
            $correctArr = (array) $question->correct_answer;
            $options = (array) $question->options;
            
            if ($question->type === 'pilihan_ganda' || $question->type === 'benar_salah') {
                // The DB stores the INDEX of the correct option
                $correctIndex = $correctArr[0] ?? null;
                $correctValue = $options[$correctIndex] ?? null;
                $isCorrect = ($correctValue !== null && $correctValue == $answer);
            } elseif ($question->type === 'multiple_choice') {
                // Map indices to values
                $correctValues = array_map(fn($idx) => $options[$idx] ?? null, $correctArr);
                $correctValues = array_filter($correctValues, fn($v) => $v !== null);
                
                if (is_array($answer)) {
                    sort($answer);
                    sort($correctValues);
                    $isCorrect = ($answer === $correctValues);
                }
            }

            UserAnswer::create([
                'participant_id' => $participant->id,
                'exam_session_id' => $participant->exam_session_id,
                'question_bank_id' => $questionId,
                'answer' => $answer,
                'is_correct' => $isCorrect
            ]);
        }

        session()->forget('participant_id');

        return response()->json(['status' => 'success', 'message' => 'Ujian berhasil dikumpulkan']);
    }
}
