<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\QuestionBankService;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class QuestionBankController extends Controller
{
    use ResponseTrait;

    protected QuestionBankService $questionService;

    public function __construct(QuestionBankService $questionService)
    {
        $this->questionService = $questionService;
    }

    public function index(Request $request)
    {
        $questions = $this->questionService->getPaginated(10);
        $categories = Category::all();
        
        if ($request->ajax()) {
            return $this->successResponse($questions);
        }

        return view('admin.questions.index', compact('questions', 'categories'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'type' => 'required|in:pilihan_ganda,benar_salah,multiple_choice',
            'question_text' => 'required|string',
            'question_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'options' => 'nullable|array',
            'correct_answer' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }

        $data = $request->all();
        
        // Ensure correct_answer is an array
        if (!is_array($data['correct_answer'])) {
            $data['correct_answer'] = [$data['correct_answer']];
        }

        if ($request->hasFile('question_image')) {
            $path = $request->file('question_image')->store('questions', 'public');
            $data['question_image'] = $path;
        }

        $question = $this->questionService->store($data);
        return $this->successResponse($question, 'Pertanyaan berhasil ditambahkan', 201);
    }

    public function show($id)
    {
        $question = $this->questionService->getById($id);
        if (!$question) return $this->errorResponse('Pertanyaan tidak ditemukan', 404);
        return $this->successResponse($question);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'type' => 'required|in:pilihan_ganda,benar_salah,multiple_choice',
            'question_text' => 'required|string',
            'question_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'options' => 'nullable|array',
            'correct_answer' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }

        $question = $this->questionService->getById($id);
        if (!$question) return $this->errorResponse('Pertanyaan tidak ditemukan', 404);

        $data = $request->all();
        
        // Ensure correct_answer is an array
        if (!is_array($data['correct_answer'])) {
            $data['correct_answer'] = [$data['correct_answer']];
        }

        if ($request->hasFile('question_image')) {
            // Delete old image
            if ($question->question_image) {
                Storage::disk('public')->delete($question->question_image);
            }
            $path = $request->file('question_image')->store('questions', 'public');
            $data['question_image'] = $path;
        }

        $this->questionService->update($id, $data);
        return $this->successResponse(null, 'Pertanyaan berhasil diperbarui');
    }

    public function destroy($id)
    {
        $question = $this->questionService->getById($id);
        if ($question && $question->question_image) {
            Storage::disk('public')->delete($question->question_image);
        }
        
        $this->questionService->destroy($id);
        return $this->successResponse(null, 'Pertanyaan berhasil dihapus');
    }
}
