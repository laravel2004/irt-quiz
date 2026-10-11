<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuestionBank;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Traits\ResponseTrait;

class QuizController extends Controller
{
    use ResponseTrait;

    public function index(Request $request)
    {
        $search = $request->query('search');
        $jenjang = $request->query('jenjang');

        $query = Quiz::query();

        if ($search) {
            $query->where('question_text', 'like', "%{$search}%");
        }
        if ($jenjang) {
            $query->where('jenjang', $jenjang);
        }

        $quizzes = $query->orderBy('id', 'desc')->paginate(10);
        $jenjangList = Quiz::select('jenjang')->distinct()->pluck('jenjang');

        if ($request->ajax()) {
            return response()->json($quizzes);
        }

        return view('admin.quizzes.index', compact('quizzes', 'jenjangList', 'search', 'jenjang'));
    }

    
    public function show($id)
    {
        $quiz = Quiz::findOrFail($id);
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['data' => $quiz]);
        }
        return redirect()->route('admin.quizzes.index');
    }

    public function create()
    {
        return view('admin.quizzes.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'jenjang' => 'required|string|max:100',
            'type' => 'required|string',
            'question_text' => 'required|string',
            'question_image' => 'nullable|image|max:2048',
            'options' => 'nullable|array',
            'correct_answer' => 'required',
            'score_correct' => 'required|numeric',
            'score_incorrect' => 'required|numeric',
            'difficulty' => 'nullable|integer',
            'explanation' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $validator->errors()->first()]);
            }
            return back()->withErrors($validator)->withInput();
        }

        $data = $request->all();
        if (empty($data['difficulty'])) {
            unset($data['difficulty']);
        }
        if (!is_array($data['correct_answer'])) {
            $data['correct_answer'] = [$data['correct_answer']];
        }

        if ($request->hasFile('question_image')) {
            $data['question_image'] = $request->file('question_image')->store('quizzes', 'public');
        }

        Quiz::create($data);
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Soal Quiz berhasil ditambahkan.']);
        }
        return redirect()->route('admin.quizzes.index')->with('success', 'Soal Quiz berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $quiz = Quiz::findOrFail($id);
        return view('admin.quizzes.edit', compact('quiz'));
    }

    public function update(Request $request, $id)
    {
        $quiz = Quiz::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'jenjang' => 'required|string|max:100',
            'type' => 'required|string',
            'question_text' => 'required|string',
            'question_image' => 'nullable|image|max:2048',
            'options' => 'nullable|array',
            'correct_answer' => 'required',
            'score_correct' => 'required|numeric',
            'score_incorrect' => 'required|numeric',
            'difficulty' => 'nullable|integer',
            'explanation' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $validator->errors()->first()]);
            }
            return back()->withErrors($validator)->withInput();
        }

        $data = $request->all();
        if (empty($data['difficulty'])) {
            unset($data['difficulty']);
        }
        if (!is_array($data['correct_answer'])) {
            $data['correct_answer'] = [$data['correct_answer']];
        }

        if ($request->hasFile('question_image')) {
            if ($quiz->question_image) {
                Storage::disk('public')->delete($quiz->question_image);
            }
            $data['question_image'] = $request->file('question_image')->store('quizzes', 'public');
        }

        $quiz->update($data);
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Soal Quiz berhasil diperbarui.']);
        }
        return redirect()->route('admin.quizzes.index')->with('success', 'Soal Quiz berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $quiz = Quiz::findOrFail($id);
        if ($quiz->question_image) {
            Storage::disk('public')->delete($quiz->question_image);
        }
        $quiz->delete();
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Soal Quiz berhasil dihapus.']);
        }
        return redirect()->route('admin.quizzes.index')->with('success', 'Soal Quiz berhasil dihapus.');
    }

    public function importBankSoalView(Request $request)
    {
        $search = $request->query('search');
        $categoryId = $request->query('category_id');

        $query = QuestionBank::with('category');

        if ($search) {
            $query->where('question_text', 'like', "%{$search}%");
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $questions = $query->orderBy('id', 'desc')->paginate(20)->withQueryString();
        $categories = Category::orderBy('name', 'asc')->get();

        return view('admin.quizzes.import', compact('questions', 'categories', 'search', 'categoryId'));
    }

    public function processImportBankSoal(Request $request)
    {
        $request->validate([
            'question_ids' => 'required|array',
            'question_ids.*' => 'exists:question_banks,id',
            'jenjang' => 'required|string'
        ]);

        $questions = QuestionBank::whereIn('id', $request->question_ids)->get();

        foreach ($questions as $q) {
            Quiz::create([
                'jenjang' => $request->jenjang,
                'type' => $q->type,
                'question_text' => $q->question_text,
                'question_image' => $q->question_image,
                'options' => $q->options,
                'correct_answer' => $q->correct_answer,
                'difficulty' => $q->difficulty,
                'score_correct' => $q->score_correct,
                'score_incorrect' => $q->score_incorrect,
                'explanation' => $q->explanation,
            ]);
        }

        return redirect()->route('admin.quizzes.index')->with('success', count($questions) . ' soal berhasil di-import ke Quiz.');
    }
}
