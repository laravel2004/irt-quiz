<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ExamSession;
use App\Services\AssessmentService;
use App\Services\ExamSessionService;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
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
        $sessions = ExamSession::with('sessionCategories.category')->latest()->get();
        $categories = Category::all();
        
        if ($request->ajax()) {
            return $this->successResponse($sessions);
        }

        return view('admin.sessions.index', compact('sessions', 'categories'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'required',
            'end_time' => 'required',
            'duration' => 'required|integer|min:1',
            'total_questions' => 'required|integer|min:1',
            'max_score_raw' => 'required|integer|min:1',
            'max_score_irt' => 'required|integer|min:1',
            'categories' => 'required|array|min:1',
            'categories.*.id' => 'required|exists:categories,id',
            'categories.*.percentage' => 'required|integer|min:1|max:100',
        ]);

        if ($validator->fails()) return $this->validationResponse($validator->errors());

        // Validate total percentage equals 100
        $totalPercentage = collect($request->categories)->sum('percentage');
        if ($totalPercentage !== 100) {
            return $this->errorResponse('Total persentase harus 100%', 422);
        }

        $data = $request->all();
        $data['code'] = strtoupper(Str::random(8));

        $session = $this->sessionService->createWithCategories($data);
        return $this->successResponse($session, 'Sesi ujian berhasil dibuat', 201);
    }

    public function show(Request $request, $id)
    {
        $session = ExamSession::with(['sessionCategories.category', 'questions.category', 'participants', 'results.participant'])->find($id);
        
        if (!$session) {
            if ($request->ajax()) return $this->errorResponse('Sesi tidak ditemukan', 404);
            return redirect()->route('admin.sessions.index')->with('error', 'Sesi tidak ditemukan');
        }

        // Fetch all potential participants (Users)
        $availableParticipants = \App\Models\User::whereIn('role', ['basic', 'premium'])->orderBy('name')->get();

        if ($request->ajax() || $request->wantsJson()) {
            return $this->successResponse([
                'session' => $session,
                'availableParticipants' => $availableParticipants
            ]);
        }

        return view('admin.sessions.show', compact('session', 'availableParticipants'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'required',
            'end_time' => 'required',
            'duration' => 'required|integer|min:1',
            'total_questions' => 'required|integer|min:1',
            'max_score_raw' => 'required|integer|min:1',
            'max_score_irt' => 'required|integer|min:1',
            'categories' => 'required|array|min:1',
            'categories.*.id' => 'required|exists:categories,id',
            'categories.*.percentage' => 'required|integer|min:1|max:100',
        ]);

        if ($validator->fails()) return $this->validationResponse($validator->errors());

        $totalPercentage = collect($request->categories)->sum('percentage');
        if ($totalPercentage !== 100) {
            return $this->errorResponse('Total persentase harus 100%', 422);
        }

        $this->sessionService->updateWithCategories($id, $request->all());
        return $this->successResponse(null, 'Sesi ujian berhasil diperbarui');
    }

    public function destroy($id)
    {
        $this->sessionService->destroy($id);
        return $this->successResponse(null, 'Sesi ujian berhasil dihapus');
    }

    public function toggleStatus($id)
    {
        $session = ExamSession::findOrFail($id);
        $session->update(['is_active' => !$session->is_active]);
        
        $status = $session->is_active ? 'diaktifkan' : 'ditutup';
        return $this->successResponse(null, "Sesi ujian berhasil {$status}");
    }

    public function generateIRTResults($id)
    {
        $result = $this->assessmentService->calculateIRT($id);
        if ($result['status'] === 'error') return $this->errorResponse($result['message'], 422);
        return $this->successResponse(null, $result['message']);
    }

    public function exportResults($id)
    {
        $session = ExamSession::with(['results.participant'])->findOrFail($id);
        $results = $session->results->sortByDesc(fn($r) => [$r->irt_score, $r->total_correct]);

        $fileName = 'Hasil_IRT_' . str_replace(' ', '_', $session->name) . '_' . date('Y-m-d_H-i') . '.csv';

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array('Rank', 'Nama Peserta', 'Kode Akses', 'Benar', 'Salah', 'Kosong', 'Skor Raw', 'Skor IRT');

        $callback = function() use($results, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($results->values() as $index => $result) {
                fputcsv($file, array(
                    $index + 1,
                    data_get($result->participant, 'name'),
                    data_get($result->participant, 'access_code'),
                    $result->total_correct,
                    $result->total_incorrect,
                    $result->total_blank,
                    number_format($result->score, 1),
                    round($result->irt_score)
                ));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function previewQuestions($id)
    {
        $session = ExamSession::with(['sessionCategories.category', 'questions.category'])->findOrFail($id);
        
        // Generate questions if none exist
        if ($session->questions()->count() == 0) {
            $this->sessionService->generateSessionQuestions($id);
            $session->load('questions.category');
        }

        return view('admin.sessions.preview', compact('session'));
    }

    public function uploadDiscussionPdf(Request $request, $id)
    {
        $request->validate([
            'discussion_pdf' => 'required|mimes:pdf|max:10240', // Max 10MB
        ]);

        $session = ExamSession::findOrFail($id);

        if ($request->hasFile('discussion_pdf')) {
            // Delete old file if exists
            if ($session->discussion_pdf && \Illuminate\Support\Facades\Storage::disk('public')->exists($session->discussion_pdf)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($session->discussion_pdf);
            }

            $path = $request->file('discussion_pdf')->store('discussions', 'public');
            $session->update(['discussion_pdf' => $path]);
        }

        return $this->successResponse(null, 'File pembahasan berhasil diunggah');
    }
}
