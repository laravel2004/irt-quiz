<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ExamSession;
use App\Models\QuestionBank;
use App\Models\SubCategory;
use App\Models\User;
use App\Services\AssessmentService;
use App\Services\ExamSessionService;
use App\Traits\ResponseTrait;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ExamSessionController extends Controller
{
    use ResponseTrait;

    protected ExamSessionService $sessionService;

    protected AssessmentService $assessmentService;

    public function __construct(ExamSessionService $sessionService, AssessmentService $assessmentService)
    {
        $this->sessionService = $sessionService;
        $this->assessmentService = $assessmentService;
    }

    public function index(Request $request)
    {
        $query = ExamSession::with('sessionCategories.category')->latest();

        if (auth()->user() && auth()->user()->role === 'admin_sesi') {
            $query->whereHas('participants', function ($q) {
                $q->where('user_id', auth()->id());
            });
        }

        if ($request->has('search') && ! empty($request->search)) {
            $search = strtolower($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%'.$search.'%'])
                    ->orWhereRaw('LOWER(code) LIKE ?', ['%'.$search.'%']);
            });
        }

        $sessions = $query->paginate(10)->withQueryString();
        $categories = Category::with(['subCategories' => fn ($query) => $query->withCount([
            'questions as available_questions_count' => fn ($query) => $query->whereNull('locked_by_exam_session_id'),
        ])])->get();

        if ($request->ajax()) {
            return $this->successResponse($sessions->items());
        }

        return view('admin.sessions.index', compact('sessions', 'categories'));
    }

    public function store(Request $request)
    {
        $validation = $this->validateSessionData($request);
        if ($validation instanceof JsonResponse) {
            return $validation;
        }

        $data = $validation;
        $data['code'] = strtoupper(Str::random(8));

        try {
            $session = $this->sessionService->createWithCategories($data);

            return $this->successResponse($session, 'Sesi ujian berhasil dibuat', 201);
        } catch (DomainException $exception) {
            return $this->errorResponse($exception->getMessage(), 422);
        }
    }

    public function show(Request $request, $id)
    {
        $session = ExamSession::with(['sessionCategories.category', 'sessionCategories.subCategories.subCategory', 'questions.category', 'participants.user', 'results.participant'])->find($id);

        if (! $session) {
            if ($request->ajax()) {
                return $this->errorResponse('Sesi tidak ditemukan', 404);
            }

            return redirect()->route('admin.sessions.index')->with('error', 'Sesi tidak ditemukan');
        }

        // Fetch all potential participants (Users)
        $availableParticipants = User::whereIn('role', ['basic', 'premium', 'user', 'admin_sesi'])->orderBy('name')->get();

        if ($request->ajax() || $request->wantsJson()) {
            return $this->successResponse([
                'session' => $session,
                'availableParticipants' => $availableParticipants,
                'availabilityCounts' => QuestionBank::availableForSession($session->id)
                    ->whereNotNull('sub_category_id')
                    ->selectRaw('sub_category_id, COUNT(*) as total')
                    ->groupBy('sub_category_id')
                    ->pluck('total', 'sub_category_id'),
            ]);
        }

        return view('admin.sessions.show', compact('session', 'availableParticipants'));
    }

    public function update(Request $request, $id)
    {
        $validation = $this->validateSessionData($request);
        if ($validation instanceof JsonResponse) {
            return $validation;
        }

        try {
            $this->sessionService->updateWithCategories($id, $validation);

            return $this->successResponse(null, 'Sesi ujian berhasil diperbarui');
        } catch (DomainException $exception) {
            return $this->errorResponse($exception->getMessage(), 422);
        }
    }

    public function destroy($id)
    {
        $this->sessionService->destroy($id);

        return $this->successResponse(null, 'Sesi ujian berhasil dihapus');
    }

    public function toggleStatus($id)
    {
        $session = ExamSession::findOrFail($id);
        $session->update(['is_active' => ! $session->is_active]);

        $status = $session->is_active ? 'diaktifkan' : 'ditutup';

        return $this->successResponse(null, "Sesi ujian berhasil {$status}");
    }

    public function generateIRTResults($id)
    {
        $result = $this->assessmentService->calculateIRT($id);
        if ($result['status'] === 'error') {
            return $this->errorResponse($result['message'], 422);
        }

        return $this->successResponse(null, $result['message']);
    }

    public function exportResults($id)
    {
        $session = ExamSession::with(['results.participant'])->findOrFail($id);
        $results = $session->results->sortByDesc(fn ($r) => [$r->irt_score, $r->total_correct]);

        $fileName = 'Hasil_IRT_'.str_replace(' ', '_', $session->name).'_'.date('Y-m-d_H-i').'.csv';

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = ['Rank', 'Nama Peserta', 'Kode Akses', 'Benar', 'Salah', 'Kosong', 'Skor Raw', 'Predikat Raw', 'Skor IRT', 'Predikat IRT'];

        $callback = function () use ($results, $columns, $session) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($results->values() as $index => $result) {
                fputcsv($file, [
                    $index + 1,
                    data_get($result->participant, 'name'),
                    data_get($result->participant, 'access_code'),
                    $result->total_correct,
                    $result->total_incorrect,
                    $result->total_blank,
                    number_format($result->score, 1),
                    $session->predicateForRawScore((float) $result->score),
                    round($result->irt_score),
                    $session->predicateForIrtScore((float) $result->irt_score),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function previewQuestions(Request $request, $id)
    {
        $session = ExamSession::with(['sessionCategories.category', 'questions.category'])->findOrFail($id);

        if ($request->boolean('regenerate') || $session->questions()->count() == 0) {
            try {
                $this->sessionService->generateSessionQuestions($id);
            } catch (DomainException $exception) {
                abort(422, $exception->getMessage());
            }
            $session->load('questions.category');
        }

        return view('admin.sessions.preview', compact('session'));
    }

    public function previewQuestionsOnly(Request $request, $id)
    {
        $session = ExamSession::with(['sessionCategories.category', 'questions.category'])->findOrFail($id);

        if ($request->boolean('regenerate') || $session->questions()->count() == 0) {
            try {
                $this->sessionService->generateSessionQuestions($id);
            } catch (DomainException $exception) {
                abort(422, $exception->getMessage());
            }
            $session->load('questions.category');
        }

        return view('admin.sessions.preview-only', compact('session'));
    }

    public function uploadDiscussionPdf(Request $request, $id)
    {
        $request->validate([
            'discussion_pdf' => 'required|mimes:pdf|max:10240', // Max 10MB
        ]);

        $session = ExamSession::findOrFail($id);

        if ($request->hasFile('discussion_pdf')) {
            // Delete old file if exists
            if ($session->discussion_pdf && Storage::disk('public')->exists($session->discussion_pdf)) {
                Storage::disk('public')->delete($session->discussion_pdf);
            }

            $path = $request->file('discussion_pdf')->store('discussions', 'public');
            $session->update(['discussion_pdf' => $path]);
        }

        return $this->successResponse(null, 'File pembahasan berhasil diunggah');
    }

    private function validateSessionData(Request $request): array|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'required',
            'end_time' => 'required',
            'is_lock_quiz' => 'required|boolean',
            'participant_score_display' => 'required|in:raw,irt,both',
            'predicate_raw_kurang_min' => 'required|numeric|min:0',
            'predicate_raw_memadai_min' => 'required|numeric|min:0',
            'predicate_raw_baik_min' => 'required|numeric|min:0',
            'predicate_raw_istimewa_min' => 'required|numeric|min:0',
            'predicate_kurang_min' => 'required|numeric|min:0',
            'predicate_memadai_min' => 'required|numeric|min:0',
            'predicate_baik_min' => 'required|numeric|min:0',
            'predicate_istimewa_min' => 'required|numeric|min:0',
            'categories' => 'required|array|min:1',
            'categories.*.id' => 'required|distinct|exists:categories,id',
            'categories.*.duration' => 'required|integer|min:1',
            'categories.*.total_questions' => 'required|integer|min:1',
            'categories.*.max_score_raw' => 'required|integer|min:1',
            'categories.*.min_score_irt' => 'required|numeric|min:0',
            'categories.*.max_score_irt' => 'required|integer|min:1',
            'categories.*.sub_categories' => 'sometimes|array',
            'categories.*.sub_categories.*.id' => 'required|distinct|exists:sub_categories,id',
            'categories.*.sub_categories.*.percentage' => 'required|integer|min:1|max:100',
        ]);

        $validator->after(function ($validator) use ($request) {
            $categories = collect($request->input('categories', []));
            if ($categories->isEmpty()) {
                return;
            }

            foreach ($categories as $index => $category) {
                if (is_numeric($category['min_score_irt'] ?? null)
                    && is_numeric($category['max_score_irt'] ?? null)
                    && (float) $category['max_score_irt'] <= (float) $category['min_score_irt']) {
                    $validator->errors()->add("categories.{$index}.max_score_irt", 'Batas atas IRT harus lebih besar dari batas bawah IRT.');
                }
            }

            $thresholdKeys = [
                'predicate_kurang_min',
                'predicate_memadai_min',
                'predicate_baik_min',
                'predicate_istimewa_min',
            ];
            if ($categories->contains(fn ($category) => ! is_numeric($category['min_score_irt'] ?? null) || ! is_numeric($category['max_score_irt'] ?? null))
                || collect($thresholdKeys)->contains(fn ($key) => ! is_numeric($request->input($key)))) {
                return;
            }

            $totalMin = round((float) $categories->sum('min_score_irt'), 2);
            $totalMax = round((float) $categories->sum('max_score_irt'), 2);
            $thresholds = collect($thresholdKeys)
                ->map(fn ($key) => round((float) $request->input($key), 2))
                ->all();

            if ($thresholds[0] !== $totalMin) {
                $validator->errors()->add('predicate_kurang_min', "Nilai minimum predikat Kurang harus sama dengan total batas bawah IRT, yaitu {$totalMin}.");
            }
            if (! ($thresholds[0] < $thresholds[1] && $thresholds[1] < $thresholds[2] && $thresholds[2] < $thresholds[3])) {
                $validator->errors()->add('predicate_memadai_min', 'Urutan ambang predikat harus Kurang < Memadai < Baik < Istimewa.');
            }
            if ($thresholds[3] > $totalMax) {
                $validator->errors()->add('predicate_istimewa_min', "Ambang Istimewa tidak boleh melebihi total batas atas IRT, yaitu {$totalMax}.");
            }

            $rawThresholdKeys = [
                'predicate_raw_kurang_min',
                'predicate_raw_memadai_min',
                'predicate_raw_baik_min',
                'predicate_raw_istimewa_min',
            ];
            if ($categories->contains(fn ($category) => ! is_numeric($category['max_score_raw'] ?? null))
                || collect($rawThresholdKeys)->contains(fn ($key) => ! is_numeric($request->input($key)))) {
                return;
            }

            $totalRawMax = round((float) $categories->sum('max_score_raw'), 2);
            $rawThresholds = collect($rawThresholdKeys)
                ->map(fn ($key) => round((float) $request->input($key), 2))
                ->all();

            if ($rawThresholds[0] !== 0.0) {
                $validator->errors()->add('predicate_raw_kurang_min', 'Nilai minimum Predikat Raw Kurang harus 0.');
            }
            if (! ($rawThresholds[0] < $rawThresholds[1] && $rawThresholds[1] < $rawThresholds[2] && $rawThresholds[2] < $rawThresholds[3])) {
                $validator->errors()->add('predicate_raw_memadai_min', 'Urutan ambang Predikat Raw harus Kurang < Memadai < Baik < Istimewa.');
            }
            if ($rawThresholds[3] > $totalRawMax) {
                $validator->errors()->add('predicate_raw_istimewa_min', "Ambang Predikat Raw Istimewa tidak boleh melebihi total skor raw, yaitu {$totalRawMax}.");
            }
        });

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }

        $data = $validator->validated();
        foreach ($data['categories'] as $index => $category) {
            $subCategories = $category['sub_categories'] ?? [];
            if ($subCategories && collect($subCategories)->sum('percentage') !== 100) {
                return $this->errorResponse('Total persentase sub mata pelajaran pada kategori ke-'.($index + 1).' harus 100%', 422);
            }

            $subCategoryIds = collect($subCategories)->pluck('id');
            if ($subCategoryIds->isNotEmpty() && SubCategory::whereIn('id', $subCategoryIds)
                ->where('category_id', '!=', $category['id'])->exists()) {
                return $this->errorResponse('Sub mata pelajaran harus berasal dari mata pelajaran yang dipilih.', 422);
            }
        }

        return $data;
    }
}
