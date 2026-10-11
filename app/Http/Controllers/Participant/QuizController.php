<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use App\Models\UserXp;
use App\Services\QuizService;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function __construct(private QuizService $quiz)
    {
    }

    /** Halaman mengerjakan quiz atau preview pembahasan jika sudah dikerjakan. */
    public function show()
    {
        $user = auth()->user();
        $attempts = $this->quiz->attemptsForToday($user)->filter(fn ($a) => $a->quiz)->values();

        if ($attempts->isEmpty()) {
            return redirect()->route('participant.dashboard');
        }

        $pending = $attempts->whereNull('answered_at')->values();

        if ($pending->isEmpty()) {
            return view('participant.quiz', [
                'isPreview' => true,
                'questions' => $attempts->map(fn ($a) => $this->quiz->previewQuestion($a))->values(),
                'total' => $attempts->count(),
                'answeredCount' => $attempts->count(),
                'xpPerCorrect' => (int) $this->quiz->settings()->xp_per_correct,
                'totalXp' => (int) (UserXp::where('user_id', $user->id)->value('total_xp') ?? 0),
            ]);
        }

        return view('participant.quiz', [
            'isPreview' => false,
            'questions' => $pending->map(fn ($a) => $this->quiz->publicQuestion($a))->values(),
            'total' => $attempts->count(),
            'answeredCount' => $attempts->count() - $pending->count(),
            'xpPerCorrect' => (int) $this->quiz->settings()->xp_per_correct,
            'totalXp' => (int) (UserXp::where('user_id', $user->id)->value('total_xp') ?? 0),
        ]);
    }

    /** Terima jawaban (JSON). */
    public function answer(Request $request, QuizAttempt $attempt)
    {
        $request->validate(['answer' => 'required']);

        abort_unless($attempt->user_id === $request->user()->id, 403);

        return response()->json($this->quiz->submitAnswer($request->user(), $attempt, $request->input('answer')));
    }
}
