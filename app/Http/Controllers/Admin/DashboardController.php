<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ExamSession;
use App\Models\QuestionBank;
use App\Models\ExamSessionParticipant;
use App\Models\ExamResult;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalParticipants = ExamSessionParticipant::count();
        $totalQuestions = QuestionBank::count();
        $totalCategories = Category::count();
        $activeSessionsCount = ExamSession::where('is_active', true)->count();
        
        $avgScore = ExamResult::avg('irt_score') ?? 0;
        
        $recentSessions = ExamSession::withCount(['participants', 'questions'])
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalParticipants',
            'totalQuestions',
            'totalCategories',
            'activeSessionsCount',
            'avgScore',
            'recentSessions'
        ));
    }
}
