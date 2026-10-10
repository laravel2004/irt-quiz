<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\QuizAnswerChecker;
use Illuminate\Http\Request;

class QuizLogController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');   // '', benar, salah, belum
        $date = $request->query('date');       // period_key (Y-m-d)
        $jenjang = $request->query('jenjang');

        $query = QuizAttempt::query()->with('user:id,name,email,jenjang');

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        if ($jenjang) {
            $query->whereHas('user', fn($q) => $q->where('jenjang', $jenjang));
        }
        if ($date) {
            $query->where('period_key', $date);
        }
        if ($status === 'benar') {
            $query->where('is_correct', true);
        } elseif ($status === 'salah') {
            $query->where('is_correct', false)->whereNotNull('answered_at');
        } elseif ($status === 'belum') {
            $query->whereNull('answered_at');
        }

        $logs = $query->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.quiz_logs.index', compact('logs', 'search', 'status', 'date', 'jenjang'));
    }

    public function show($id)
    {
        $attempt = QuizAttempt::with(['user', 'quiz'])->findOrFail($id);
        
        $correct = [];
        if ($attempt->quiz) {
            $checker = app(QuizAnswerChecker::class);
            $correct = $checker->correctIndices($attempt->quiz);
        }

        return view('admin.quiz_logs.show', compact('attempt', 'correct'));
    }
}
