<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuizSetting;
use App\Services\QuizService;
use Illuminate\Http\Request;

class QuizSettingController extends Controller
{
    public function index(QuizService $quiz)
    {
        return view('admin.quiz_settings.index', [
            'setting' => QuizSetting::current(),
            'periodStart' => $quiz->currentPeriodStart(),
            'nextReset' => $quiz->nextResetAt(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'reset_time' => 'required|date_format:H:i',
            'questions_per_day' => 'required|integer|min:1|max:20',
            'xp_per_correct' => 'required|integer|min:0|max:100000',
        ]);

        QuizSetting::current()->update($data);

        return redirect()->route('admin.quiz-settings.index')->with('success', 'Pengaturan quiz berhasil disimpan.');
    }
}
