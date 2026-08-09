<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\ExamSessionParticipant;
use App\Models\ReportCard;
use App\Jobs\GenerateReportCardJob;
use App\Jobs\GenerateReportCardAiAnalysisJob;

class ParticipantController extends Controller
{
    use ResponseTrait;

    public function index(Request $request)
    {
        $query = User::whereIn('role', ['basic', 'admin_sesi', 'superadmin'])->latest();
        
        if ($request->has('search') && !empty($request->search)) {
            $search = strtolower($request->search);
            $query->where(function($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . $search . '%'])
                  ->orWhereRaw('LOWER(email) LIKE ?', ['%' . $search . '%']);
            });
        }
        
        $participants = $query->paginate(10)->withQueryString();
        
        if ($request->ajax()) {
            return $this->successResponse($participants->items());
        }

        return view('admin.participants.index', compact('participants'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'role' => 'required|in:basic,admin_sesi,superadmin',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) return $this->validationResponse($validator->errors());

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'role' => $request->role,
            'password' => Hash::make($request->password),
        ]);

        return $this->successResponse($user, 'Peserta berhasil ditambahkan', 201);
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        return $this->successResponse($user);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'role' => 'required|in:basic,admin_sesi,superadmin',
            'password' => 'nullable|string|min:6',
        ]);

        if ($validator->fails()) return $this->validationResponse($validator->errors());

        $data = $request->only(['name', 'email', 'phone', 'address', 'role']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        return $this->successResponse($user, 'Data peserta berhasil diperbarui');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return $this->successResponse(null, 'Peserta berhasil dihapus');
    }

    /**
     * Ambil daftar sesi ujian yang pernah diikuti user.
     * Digunakan untuk menampilkan checkbox di modal.
     */
    public function getReportSessions($id)
    {
        $user = \App\Models\User::findOrFail($id);

        // Ambil semua sesi ujian yang pernah diikuti user (yang sudah selesai)
        $sessions = ExamSessionParticipant::where('user_id', $user->id)
            ->whereNotNull('finished_at')
            ->with('examSession') // Load relasi nama sesi
            ->get()
            ->groupBy('exam_session_id') // Group agar tidak duplikat per sesi
            ->map(function ($participants) {
                $latest = $participants->sortByDesc('id')->first();
                return [
                    'exam_session_id'   => $latest->exam_session_id,
                    'session_name'      => $latest->examSession->name ?? '-',
                    'finished_at'       => $latest->finished_at,
                    'attempt_count'     => $participants->count(),
                ];
            })
            ->values();

        return $this->successResponse([
            'user_name' => $user->name,
            'sessions'  => $sessions,
        ]);
    }

    /**
     * Ambil history raport yang sudah pernah di-generate untuk peserta ini.
     */
    public function reportHistory($id)
    {
        $user = \App\Models\User::findOrFail($id);

        $reports = ReportCard::where('user_id', $user->id)
            ->with('generatedBy')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($report) {
                $sessionNames = \App\Models\ExamSession::whereIn('id', $report->session_ids)->pluck('name')->implode(', ');
                
                return [
                    'id' => $report->id,
                    'created_at' => $report->created_at->format('d M Y, H:i'),
                    'status' => $report->status,
                    'generated_by_name' => $report->generatedBy->name ?? 'Admin',
                    'sessions_text' => $sessionNames ?: '-',
                ];
            });

        return $this->successResponse([
            'user_name' => $user->name,
            'reports' => $reports
        ]);
    }

    /**
     * Terima pilihan sesi ujian dari admin, buat record ReportCard, dan dispatch Job.
     */
    public function generateReport(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'session_ids'   => 'required|array|min:1',
            'session_ids.*' => 'integer|exists:exam_sessions,id',
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }

        $user = \App\Models\User::findOrFail($id);

        // Buat record di tabel report_cards
        $reportCard = ReportCard::create([
            'user_id'      => $user->id,
            'generated_by' => auth()->id(),
            'session_ids'  => $request->session_ids,
            'status'       => 'processing',
        ]);

        // Dispatch job ke queue
        GenerateReportCardJob::dispatch($reportCard->id);

        return $this->successResponse([
            'report_card_id' => $reportCard->id,
        ], 'Raport sedang diproses. Silakan tunggu beberapa saat.');
    }

    /**
     * Cek status generate raport (dipanggil polling dari frontend).
     */
    public function reportStatus($id)
    {
        $reportCard = ReportCard::findOrFail($id);

        return $this->successResponse([
            'status'        => $reportCard->status,
            'error_message' => $reportCard->error_message,
        ]);
    }

    /**
     * Tampilkan halaman raport yang sudah selesai di-generate.
     */
    public function viewReport($id)
    {
        $reportCard = ReportCard::with('user')->findOrFail($id);

        if ($reportCard->status !== 'completed') {
            return redirect()->route('admin.participants.index')
                ->with('error', 'Raport belum selesai diproses.');
        }

        return view('admin.participants.report', compact('reportCard'));
    }

    /**
     * Tampilkan halaman raport untuk dicetak.
     */
    public function printReport($id)
    {
        $reportCard = ReportCard::with('user')->findOrFail($id);

        if ($reportCard->status !== 'completed') {
            return redirect()->route('admin.report-cards.view', $id)
                             ->with('error', 'Raport belum selesai diproses.');
        }

        return view('admin.participants.report-print', compact('reportCard'));
    }

    /**
     * Trigger generate analisis AI untuk sebuah report card.
     * Mendukung generate pertama kali dan re-generate.
     */
    public function generateAiAnalysis(Request $request, $id)
    {
        $reportCard = ReportCard::findOrFail($id);

        // Cek apakah raport sudah completed
        if ($reportCard->status !== 'completed') {
            return $this->errorResponse('Raport belum selesai diproses.', 400);
        }

        // Jika sedang diproses, jangan dispatch lagi
        if ($reportCard->ai_analysis_status === 'processing') {
            return $this->successResponse([
                'ai_analysis_status' => 'processing',
            ], 'Analisis AI sedang diproses.');
        }

        // Reset status dan dispatch job baru (untuk generate pertama kali ATAU re-generate)
        $reportCard->update([
            'ai_analysis_status' => 'processing',
            'ai_analysis'        => null,
        ]);

        GenerateReportCardAiAnalysisJob::dispatch($reportCard->id);

        return $this->successResponse([
            'ai_analysis_status' => 'processing',
        ], 'Analisis AI sedang diproses. Tunggu beberapa saat.');
    }

    /**
     * Cek status analisis AI (dipanggil polling dari frontend).
     */
    public function aiAnalysisStatus($id)
    {
        $reportCard = ReportCard::findOrFail($id);

        return $this->successResponse([
            'ai_analysis_status' => $reportCard->ai_analysis_status,
            'ai_analysis'        => $reportCard->ai_analysis_status === 'completed'
                ? $reportCard->ai_analysis
                : null,
        ]);
    }
}
