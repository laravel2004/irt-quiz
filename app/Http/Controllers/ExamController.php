<?php

namespace App\Http\Controllers;

use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Models\QuestionBank;
use App\Models\UserAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
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

        if (!$session->is_active || $now->lt($start) || $now->gt($end)) {
            return back()->with('error', 'Sesi ujian belum dimulai atau sudah berakhir.');
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

    public function main()
    {
        $participantId = session('participant_id');
        if (!$participantId) return redirect()->route('exam.index');

        $participant = ExamSessionParticipant::with(['examSession.sessionCategories.category'])->findOrFail($participantId);
        $session = $participant->examSession;

        // If participant has no questions assigned yet (via pivot), assign them now
        if ($participant->examSession->questions()->wherePivot('exam_session_id', $session->id)->count() == 0) {
            // Note: This logic picks questions for the SESSION, not per-participant. 
            // In a real IRT system, we might want per-participant, but for now let's pick for the session if not set.
            // Actually, usually the system picks questions for the session once.
            
            // Let's check if the session already has questions.
            if ($session->questions()->count() == 0) {
                $this->generateSessionQuestions($session);
            }
        }

        $questions = $session->questions()->with('category')->inRandomOrder()->get();
        
        // Calculate remaining time
        $startTime = \Carbon\Carbon::parse($participant->started_at);
        $endTime = $startTime->copy()->addMinutes($session->duration);
        $remainingSeconds = max(0, now()->diffInSeconds($endTime, false));

        if ($remainingSeconds <= 0) {
            return redirect()->route('exam.index')->with('error', 'Waktu ujian Anda sudah habis.');
        }

        return view('exam.main', compact('session', 'participant', 'questions', 'remainingSeconds'));
    }

    private function generateSessionQuestions(ExamSession $session)
    {
        $allSelectedIds = [];

        foreach ($session->sessionCategories as $sc) {
            $count = round(($sc->percentage / 100) * $session->total_questions);
            
            $questionIds = QuestionBank::where('category_id', $sc->category_id)
                ->inRandomOrder()
                ->limit($count)
                ->pluck('id')
                ->toArray();
            
            $allSelectedIds = array_merge($allSelectedIds, $questionIds);
        }

        // Sync questions to session
        $session->questions()->sync($allSelectedIds);
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
