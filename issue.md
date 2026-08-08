# Issue: Fitur Cetak Raport Peserta (Lintas Sesi Ujian)

## Deskripsi
Menambahkan fitur **"Cetak Raport"** pada halaman **Manajemen Peserta** (`/admin/participants`).
Fitur ini memungkinkan admin memilih seorang peserta, lalu memilih beberapa sesi ujian yang pernah diikuti peserta tersebut, dan sistem akan meng-generate raport berisi **rekap jawaban benar/salah per Mata Pelajaran dan Sub Mata Pelajaran** dari seluruh sesi ujian yang dipilih.

Proses generate raport dilakukan menggunakan **Laravel Job (queue)** agar tidak membuat browser menunggu lama.

## Target Pekerja
Dokumen ini disusun secara detail agar mudah diimplementasikan oleh **Junior Programmer** atau **AI Coder model ringan/murah**.

---

## Peta Relasi Database (Yang Perlu Dipahami)

Sebelum mulai coding, pahami dulu hubungan antar tabel yang digunakan:

```
users (id, name, email, role)
  └── exam_session_participants (id, exam_session_id, user_id, name, started_at, finished_at)
        ├── user_answers (id, participant_id, exam_session_id, question_bank_id, answer, is_correct, score)
        └── exam_sessions (id, name, code)

question_banks (id, category_id, sub_category_id, question_text, correct_answer, score_correct, score_incorrect)
  ├── categories (id, name, slug)          ← Ini adalah "Mata Pelajaran"
  └── sub_categories (id, category_id, name, slug) ← Ini adalah "Sub Mata Pelajaran"
```

**Konsep Penting:**
- 1 `User` bisa punya banyak `ExamSessionParticipant` (karena ikut banyak sesi ujian).
- 1 `User` bisa ikut sesi ujian yang sama **lebih dari 1 kali** (retake). Setiap retake, dibuat row `ExamSessionParticipant` baru dengan `exam_session_id` yang sama dan `user_id` yang sama.
- Setiap `ExamSessionParticipant` punya banyak `UserAnswer`.
- Setiap `UserAnswer` merujuk ke 1 `QuestionBank` (soal).
- Setiap `QuestionBank` punya `category_id` (mata pelajaran) dan `sub_category_id` (sub mata pelajaran).

---

## Tahapan Implementasi Detail

### Tahap 1: Buat Migration untuk Tabel `report_cards`

Kita butuh tabel baru untuk menyimpan hasil generate raport. Ini diperlukan karena proses generate dilakukan di background (Job), jadi hasilnya harus disimpan di database.

**Jalankan perintah:**
```bash
php artisan make:migration create_report_cards_table
```

**Isi file migration yang dibuat** (di folder `database/migrations/`):
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('generated_by')->constrained('users')->onDelete('cascade'); // admin yang generate
            $table->json('session_ids');   // [1, 3, 5] - daftar exam_session_id yang dipilih
            $table->string('status')->default('processing'); // processing | completed | failed
            $table->json('report_data')->nullable(); // hasil raport dalam format JSON
            $table->text('error_message')->nullable(); // pesan error jika gagal
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_cards');
    }
};
```

**Lalu jalankan:**
```bash
php artisan migrate
```

---

### Tahap 2: Buat Model `ReportCard`

**Buat file baru:** `app/Models/ReportCard.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportCard extends Model
{
    protected $fillable = [
        'user_id',
        'generated_by',
        'session_ids',
        'status',
        'report_data',
        'error_message',
    ];

    protected $casts = [
        'session_ids'  => 'array',
        'report_data'  => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
```

---

### Tahap 3: Buat Job `GenerateReportCardJob`

Job ini adalah **inti dari fitur**. Dia yang melakukan semua proses berat di background.

**Buat file baru:** `app/Jobs/GenerateReportCardJob.php`

```php
<?php

namespace App\Jobs;

use App\Models\ReportCard;
use App\Models\ExamSessionParticipant;
use App\Models\UserAnswer;
use App\Models\QuestionBank;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateReportCardJob implements ShouldQueue
{
    use Queueable;

    public int $reportCardId;

    public function __construct(int $reportCardId)
    {
        $this->reportCardId = $reportCardId;
    }

    public function handle(): void
    {
        $reportCard = ReportCard::find($this->reportCardId);

        if (!$reportCard) {
            return;
        }

        try {
            $userId    = $reportCard->user_id;
            $sessionIds = $reportCard->session_ids; // contoh: [1, 3, 5]

            // ============================================================
            // LANGKAH 1: Cari participant_id TERBARU per sesi ujian
            // ============================================================
            // Kenapa terbaru? Karena 1 user bisa retake sesi yang sama berkali-kali.
            // Kita hanya ambil percobaan terakhir (ID paling besar).
            $latestParticipantIds = [];

            foreach ($sessionIds as $sessionId) {
                $latestParticipant = ExamSessionParticipant::where('user_id', $userId)
                    ->where('exam_session_id', $sessionId)
                    ->whereNotNull('finished_at') // Hanya yang sudah selesai ujian
                    ->orderBy('id', 'desc')        // Ambil yang paling baru (ID terbesar)
                    ->first();

                if ($latestParticipant) {
                    $latestParticipantIds[] = $latestParticipant->id;
                }
            }

            // Jika tidak ada participant yang ditemukan, set gagal
            if (empty($latestParticipantIds)) {
                $reportCard->update([
                    'status'        => 'failed',
                    'error_message' => 'Tidak ditemukan data ujian yang sudah selesai untuk sesi yang dipilih.',
                ]);
                return;
            }

            // ============================================================
            // LANGKAH 2: Ambil semua jawaban user dari participant terbaru
            // ============================================================
            $allAnswers = UserAnswer::whereIn('participant_id', $latestParticipantIds)
                ->with(['question.category', 'question.subCategory'])
                ->get();

            // ============================================================
            // LANGKAH 3: Deduplikasi soal yang sama
            // ============================================================
            // Jika soal (question_bank_id) muncul di lebih dari 1 sesi ujian,
            // kita hanya ambil salah satu jawaban (yang pertama ditemukan).
            $uniqueAnswers = $allAnswers->unique('question_bank_id');

            // ============================================================
            // LANGKAH 4: Mapping per Mata Pelajaran & Sub Mata Pelajaran
            // ============================================================
            $reportData = [];

            foreach ($uniqueAnswers as $answer) {
                $question = $answer->question;

                // Jaga-jaga jika data soal sudah dihapus
                if (!$question || !$question->category) {
                    continue;
                }

                $categoryName    = $question->category->name;
                $subCategoryName = $question->subCategory->name ?? 'Umum'; // Default 'Umum' jika sub kosong
                $categoryId      = $question->category_id;
                $subCategoryId   = $question->sub_category_id ?? 0;

                // Inisialisasi struktur data jika belum ada
                if (!isset($reportData[$categoryId])) {
                    $reportData[$categoryId] = [
                        'category_name' => $categoryName,
                        'total_soal'    => 0,
                        'total_benar'   => 0,
                        'total_salah'   => 0,
                        'sub_categories' => [],
                    ];
                }

                if (!isset($reportData[$categoryId]['sub_categories'][$subCategoryId])) {
                    $reportData[$categoryId]['sub_categories'][$subCategoryId] = [
                        'sub_category_name' => $subCategoryName,
                        'total_soal'        => 0,
                        'total_benar'       => 0,
                        'total_salah'       => 0,
                    ];
                }

                // Hitung benar/salah
                $isCorrect = (bool) $answer->is_correct;

                // Update hitungan Mata Pelajaran
                $reportData[$categoryId]['total_soal']++;
                if ($isCorrect) {
                    $reportData[$categoryId]['total_benar']++;
                } else {
                    $reportData[$categoryId]['total_salah']++;
                }

                // Update hitungan Sub Mata Pelajaran
                $reportData[$categoryId]['sub_categories'][$subCategoryId]['total_soal']++;
                if ($isCorrect) {
                    $reportData[$categoryId]['sub_categories'][$subCategoryId]['total_benar']++;
                } else {
                    $reportData[$categoryId]['sub_categories'][$subCategoryId]['total_salah']++;
                }
            }

            // Konversi sub_categories dari assoc ke indexed array agar JSON-nya rapi
            foreach ($reportData as $catId => $catData) {
                $reportData[$catId]['sub_categories'] = array_values($catData['sub_categories']);
            }
            $reportData = array_values($reportData);

            // ============================================================
            // LANGKAH 5: Simpan hasil ke database
            // ============================================================
            $reportCard->update([
                'status'      => 'completed',
                'report_data' => $reportData,
            ]);

        } catch (\Exception $e) {
            Log::error('GenerateReportCardJob failed: ' . $e->getMessage());
            $reportCard->update([
                'status'        => 'failed',
                'error_message' => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
            ]);
        }
    }
}
```

**Penjelasan alur di dalam Job:**
1. Ambil `participant_id` **terbaru** (retake terakhir) untuk setiap sesi ujian yang dipilih.
2. Ambil semua `UserAnswer` dari participant-participant tersebut, beserta relasi soal (`question`), mata pelajaran (`category`), dan sub mata pelajaran (`subCategory`).
3. **Deduplikasi**: Jika `question_bank_id` yang sama muncul di lebih dari satu sesi, hanya ambil 1.
4. Loop semua jawaban unik, kelompokkan per `category` (mata pelajaran) dan per `sub_category` (sub mata pelajaran), hitung total soal, benar, dan salah.
5. Simpan hasilnya ke kolom `report_data` di tabel `report_cards`.

---

### Tahap 4: Tambahkan Routes Baru

**File Target:** `routes/web.php`

Cari blok kode berikut (di dalam grup `SuperAdminMiddleware`):
```php
// Admin Management of Participants (Users)
Route::resource('/admin/participants', \App\Http\Controllers\Admin\ParticipantController::class)->names('admin.participants');
```

**Tambahkan route baru TEPAT DI BAWAH baris tersebut:**
```php
// Cetak Raport
Route::get('/admin/participants/{id}/report-sessions', [\App\Http\Controllers\Admin\ParticipantController::class, 'getReportSessions'])->name('admin.participants.report-sessions');
Route::post('/admin/participants/{id}/generate-report', [\App\Http\Controllers\Admin\ParticipantController::class, 'generateReport'])->name('admin.participants.generate-report');
Route::get('/admin/report-cards/{id}/status', [\App\Http\Controllers\Admin\ParticipantController::class, 'reportStatus'])->name('admin.report-cards.status');
Route::get('/admin/report-cards/{id}/view', [\App\Http\Controllers\Admin\ParticipantController::class, 'viewReport'])->name('admin.report-cards.view');
```

**Penjelasan setiap route:**
| Route | Method | Fungsi |
|---|---|---|
| `report-sessions` | GET | Mengambil daftar sesi ujian yang pernah diikuti user (untuk checkbox) |
| `generate-report` | POST | Menerima array `session_ids`, membuat record `ReportCard`, dan dispatch Job |
| `report-cards/{id}/status` | GET | Dicek secara polling oleh frontend untuk tahu apakah Job sudah selesai |
| `report-cards/{id}/view` | GET | Menampilkan halaman raport setelah status `completed` |

---

### Tahap 5: Tambahkan Method di `ParticipantController`

**File Target:** `app/Http/Controllers/Admin/ParticipantController.php`

**Tambahkan import di bagian atas file** (di bawah `use Illuminate\Support\Facades\Validator;`):
```php
use App\Models\ExamSessionParticipant;
use App\Models\ReportCard;
use App\Jobs\GenerateReportCardJob;
```

**Tambahkan 4 method baru di dalam class `ParticipantController`, setelah method `destroy()`:**

#### Method 1: `getReportSessions` — Ambil daftar sesi ujian user
```php
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
```

#### Method 2: `generateReport` — Dispatch Job
```php
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
```

#### Method 3: `reportStatus` — Cek status Job
```php
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
```

#### Method 4: `viewReport` — Tampilkan halaman raport
```php
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
```

---

### Tahap 6: Tambahkan Tombol "Cetak Raport" di View `index.blade.php`

**File Target:** `resources/views/admin/participants/index.blade.php`

#### 6a. Tambahkan tombol di kolom AKSI

Cari blok kode ini (sekitar baris 62-67):
```html
<td style="text-align: center;">
    <button class="btn-icon" onclick="editParticipant({{ $user->id }})" title="Edit">
        <i class="fas fa-edit"></i>
    </button>
    <button class="btn-icon delete" onclick="deleteParticipant({{ $user->id }})" title="Hapus">
        <i class="fas fa-trash"></i>
    </button>
</td>
```

**Ganti menjadi** (tambahkan tombol raport baru):
```html
<td style="text-align: center;">
    <button class="btn-icon" onclick="openReportModal({{ $user->id }})" title="Cetak Raport" style="color: #10b981;">
        <i class="fas fa-file-alt"></i>
    </button>
    <button class="btn-icon" onclick="editParticipant({{ $user->id }})" title="Edit">
        <i class="fas fa-edit"></i>
    </button>
    <button class="btn-icon delete" onclick="deleteParticipant({{ $user->id }})" title="Hapus">
        <i class="fas fa-trash"></i>
    </button>
</td>
```

#### 6b. Tambahkan Modal "Cetak Raport"

Tambahkan kode HTML berikut **TEPAT SEBELUM** baris `@endsection` (sebelum `</div>` terakhir / sebelum tag penutup participant modal, atau di atas baris `@endsection`):

```html
<!-- Modal Cetak Raport -->
<div class="modal-overlay" id="reportModal">
    <div class="modal-content glass animate-fade-in" style="max-width: 600px;">
        <div class="modal-header">
            <h3 id="reportModalTitle">Cetak Raport</h3>
            <button class="close-modal" onclick="closeReportModal()">&times;</button>
        </div>

        <!-- State 1: Loading daftar sesi -->
        <div id="reportLoading" style="text-align: center; padding: 40px;">
            <div style="width: 40px; height: 40px; border: 3px solid rgba(59, 130, 246, 0.3); border-top-color: #3b82f6; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 16px;"></div>
            <p style="color: var(--text-secondary);">Memuat daftar sesi ujian...</p>
        </div>

        <!-- State 2: Daftar sesi untuk dipilih -->
        <div id="reportSessionList" style="display: none;">
            <p style="color: var(--text-secondary); margin-bottom: 16px; font-size: 0.9rem;">
                Pilih sesi ujian yang ingin dimasukkan ke dalam raport:
            </p>
            <div id="sessionCheckboxes" style="max-height: 300px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px;">
                <!-- Checkbox sesi akan di-generate oleh JavaScript -->
            </div>
            <div style="display: flex; gap: 12px; margin-top: 24px; justify-content: flex-end;">
                <button type="button" class="btn-primary" style="background: transparent; border: 1px solid var(--glass-border); color: var(--text-secondary);" onclick="closeReportModal()">Batal</button>
                <button type="button" class="btn-primary" onclick="submitGenerateReport()" id="generateReportBtn" style="background: #10b981;">
                    <i class="fas fa-file-alt"></i> Generate Raport
                </button>
            </div>
        </div>

        <!-- State 3: Proses generate -->
        <div id="reportProcessing" style="display: none; text-align: center; padding: 40px;">
            <div style="width: 40px; height: 40px; border: 3px solid rgba(16, 185, 129, 0.3); border-top-color: #10b981; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 16px;"></div>
            <p style="color: var(--text-secondary);">Raport sedang digenerate, mohon tunggu...</p>
        </div>

        <!-- State 4: Selesai -->
        <div id="reportDone" style="display: none; text-align: center; padding: 40px;">
            <div style="width: 60px; height: 60px; background: rgba(16, 185, 129, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                <i class="fas fa-check" style="font-size: 1.5rem; color: #10b981;"></i>
            </div>
            <h4 style="margin-bottom: 8px;">Raport Berhasil Digenerate!</h4>
            <a id="viewReportLink" href="#" class="btn-primary" style="display: inline-flex; margin-top: 16px; background: #10b981; text-decoration: none;">
                <i class="fas fa-eye"></i> Lihat Raport
            </a>
        </div>
    </div>
</div>

<style>
    @keyframes spin { to { transform: rotate(360deg); } }
</style>
```

#### 6c. Tambahkan JavaScript

Di dalam blok `@push('scripts')`, **tambahkan kode JavaScript berikut** (di dalam tag `<script>`, setelah fungsi `deleteParticipant`):

```javascript
// ==================== CETAK RAPORT ====================
let currentReportUserId = null;

function openReportModal(userId) {
    currentReportUserId = userId;
    const modal = document.getElementById('reportModal');
    modal.classList.add('active');

    // Reset semua state
    document.getElementById('reportLoading').style.display = 'block';
    document.getElementById('reportSessionList').style.display = 'none';
    document.getElementById('reportProcessing').style.display = 'none';
    document.getElementById('reportDone').style.display = 'none';

    // Fetch daftar sesi ujian user ini
    fetch(`/admin/participants/${userId}/report-sessions`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('reportLoading').style.display = 'none';

        if (data.data.sessions.length === 0) {
            showToast('Peserta ini belum pernah menyelesaikan ujian.', 'error');
            closeReportModal();
            return;
        }

        document.getElementById('reportModalTitle').innerText = 'Cetak Raport: ' + data.data.user_name;

        // Generate checkbox untuk setiap sesi
        const container = document.getElementById('sessionCheckboxes');
        container.innerHTML = '';

        data.data.sessions.forEach(session => {
            const div = document.createElement('div');
            div.style.cssText = 'padding: 12px 16px; border: 1px solid var(--glass-border); border-radius: 12px; display: flex; align-items: center; gap: 12px; cursor: pointer; transition: all 0.2s ease;';

            div.innerHTML = `
                <input type="checkbox" value="${session.exam_session_id}" id="session_${session.exam_session_id}" style="width: 18px; height: 18px; cursor: pointer;">
                <label for="session_${session.exam_session_id}" style="cursor: pointer; flex: 1;">
                    <div style="font-weight: 600; font-size: 0.95rem;">${session.session_name}</div>
                    <div style="font-size: 0.75rem; color: var(--text-secondary);">Percobaan: ${session.attempt_count}x</div>
                </label>
            `;

            // Klik div = toggle checkbox
            div.addEventListener('click', function(e) {
                if (e.target.tagName !== 'INPUT') {
                    const cb = div.querySelector('input[type="checkbox"]');
                    cb.checked = !cb.checked;
                }
            });

            container.appendChild(div);
        });

        document.getElementById('reportSessionList').style.display = 'block';
    })
    .catch(err => {
        console.error(err);
        showToast('Gagal memuat data sesi ujian.', 'error');
        closeReportModal();
    });
}

function closeReportModal() {
    document.getElementById('reportModal').classList.remove('active');
}

function submitGenerateReport() {
    // Kumpulkan semua checkbox yang dicentang
    const checkboxes = document.querySelectorAll('#sessionCheckboxes input[type="checkbox"]:checked');
    const sessionIds = Array.from(checkboxes).map(cb => parseInt(cb.value));

    if (sessionIds.length === 0) {
        showToast('Pilih minimal 1 sesi ujian.', 'error');
        return;
    }

    // Tampilkan state processing
    document.getElementById('reportSessionList').style.display = 'none';
    document.getElementById('reportProcessing').style.display = 'block';

    // Kirim request ke backend
    fetch(`/admin/participants/${currentReportUserId}/generate-report`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ session_ids: sessionIds })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            const reportCardId = data.data.report_card_id;
            // Mulai polling status
            pollReportStatus(reportCardId);
        } else {
            showToast(data.message || 'Gagal generate raport.', 'error');
            document.getElementById('reportProcessing').style.display = 'none';
            document.getElementById('reportSessionList').style.display = 'block';
        }
    })
    .catch(err => {
        console.error(err);
        showToast('Terjadi kesalahan sistem.', 'error');
        document.getElementById('reportProcessing').style.display = 'none';
        document.getElementById('reportSessionList').style.display = 'block';
    });
}

function pollReportStatus(reportCardId) {
    const interval = setInterval(() => {
        fetch(`/admin/report-cards/${reportCardId}/status`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.data.status === 'completed') {
                clearInterval(interval);
                document.getElementById('reportProcessing').style.display = 'none';
                document.getElementById('reportDone').style.display = 'block';
                document.getElementById('viewReportLink').href = `/admin/report-cards/${reportCardId}/view`;
            } else if (data.data.status === 'failed') {
                clearInterval(interval);
                showToast('Gagal generate raport: ' + (data.data.error_message || 'Unknown error'), 'error');
                closeReportModal();
            }
            // Jika masih 'processing', polling lanjut
        })
        .catch(() => {
            clearInterval(interval);
            showToast('Gagal mengecek status raport.', 'error');
            closeReportModal();
        });
    }, 2000); // Poll setiap 2 detik
}
```

---

### Tahap 7: Buat View Halaman Raport

**Buat file baru:** `resources/views/admin/participants/report.blade.php`

```blade
@extends('layouts.admin')

@section('title', 'Raport: ' . $reportCard->user->name)
@section('header_title', 'Raport Peserta')

@section('content')
<div class="glass animate-fade-in" style="padding: 32px; margin-bottom: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
        <div>
            <h3 style="font-family: 'Outfit', sans-serif; margin-bottom: 4px;">
                Raport: {{ $reportCard->user->name }}
            </h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">
                Digenerate pada {{ $reportCard->updated_at->format('d M Y, H:i') }} WIB
            </p>
        </div>
        <a href="{{ route('admin.participants.index') }}" class="btn-primary" style="background: transparent; border: 1px solid var(--glass-border); color: var(--text-secondary); text-decoration: none;">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    @php $reportData = $reportCard->report_data; @endphp

    @if(!$reportData || count($reportData) === 0)
        <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
            Tidak ada data jawaban untuk ditampilkan.
        </div>
    @else
        @foreach($reportData as $category)
        <div style="margin-bottom: 32px; border: 1px solid var(--glass-border); border-radius: 16px; overflow: hidden;">
            <!-- Header Mata Pelajaran -->
            <div style="background: #f1f5f9; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
                <h4 style="font-family: 'Outfit', sans-serif; margin: 0;">
                    {{ $category['category_name'] }}
                </h4>
                <div style="display: flex; gap: 16px; font-size: 0.85rem;">
                    <span><strong>Total:</strong> {{ $category['total_soal'] }} soal</span>
                    <span style="color: #10b981;"><strong>Benar:</strong> {{ $category['total_benar'] }}</span>
                    <span style="color: #ef4444;"><strong>Salah:</strong> {{ $category['total_salah'] }}</span>
                </div>
            </div>

            <!-- Detail Sub Mata Pelajaran -->
            <div style="padding: 0;">
                <table class="data-table" style="margin: 0; border: none;">
                    <thead>
                        <tr>
                            <th>Sub Mata Pelajaran</th>
                            <th style="text-align: center; width: 100px;">Total Soal</th>
                            <th style="text-align: center; width: 100px;">Benar</th>
                            <th style="text-align: center; width: 100px;">Salah</th>
                            <th style="text-align: center; width: 120px;">Persentase</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($category['sub_categories'] as $sub)
                        @php
                            $persen = $sub['total_soal'] > 0 ? round(($sub['total_benar'] / $sub['total_soal']) * 100) : 0;
                            $barColor = $persen >= 70 ? '#10b981' : ($persen >= 40 ? '#eab308' : '#ef4444');
                        @endphp
                        <tr>
                            <td>{{ $sub['sub_category_name'] }}</td>
                            <td style="text-align: center;">{{ $sub['total_soal'] }}</td>
                            <td style="text-align: center; color: #10b981; font-weight: 600;">{{ $sub['total_benar'] }}</td>
                            <td style="text-align: center; color: #ef4444; font-weight: 600;">{{ $sub['total_salah'] }}</td>
                            <td style="text-align: center;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="flex: 1; height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden;">
                                        <div style="width: {{ $persen }}%; height: 100%; background: {{ $barColor }}; border-radius: 4px;"></div>
                                    </div>
                                    <span style="font-weight: 600; font-size: 0.85rem; color: {{ $barColor }};">{{ $persen }}%</span>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endforeach
    @endif
</div>
@endsection
```

---

## Checklist Pengerjaan

Gunakan checklist ini untuk memastikan semua langkah sudah diselesaikan:

- [ ] **Tahap 1:** Migration `create_report_cards_table` sudah dibuat dan sudah di-`php artisan migrate`.
- [ ] **Tahap 2:** Model `ReportCard.php` sudah dibuat di `app/Models/`.
- [ ] **Tahap 3:** Job `GenerateReportCardJob.php` sudah dibuat di `app/Jobs/`.
- [ ] **Tahap 4:** 4 route baru sudah ditambahkan di `routes/web.php`.
- [ ] **Tahap 5:** 4 method baru sudah ditambahkan di `ParticipantController.php`.
- [ ] **Tahap 6a:** Tombol icon raport (hijau) sudah muncul di kolom AKSI tabel peserta.
- [ ] **Tahap 6b:** Modal cetak raport sudah ditambahkan di `index.blade.php`.
- [ ] **Tahap 6c:** JavaScript untuk modal + polling sudah ditambahkan.
- [ ] **Tahap 7:** View `report.blade.php` sudah dibuat.
- [ ] **Testing:** Klik tombol raport → muncul modal → pilih sesi → klik generate → tunggu → lihat raport.

## Kriteria Penerimaan Pekerjaan (Acceptance Criteria)

- [ ] Tombol "Cetak Raport" muncul di setiap baris peserta pada halaman `/admin/participants`.
- [ ] Saat diklik, modal muncul dengan daftar sesi ujian yang **sudah pernah diselesaikan** oleh peserta tersebut (dalam bentuk checkbox).
- [ ] Admin bisa memilih satu atau beberapa sesi ujian.
- [ ] Setelah klik "Generate Raport", sistem memproses di background (tidak freeze/loading lama di browser).
- [ ] Setelah selesai, admin bisa melihat halaman raport yang menampilkan rekap **per Mata Pelajaran dan per Sub Mata Pelajaran**.
- [ ] Jika ada soal yang sama di 2 sesi ujian berbeda, soal tersebut **hanya dihitung 1 kali**.
- [ ] Raport menampilkan: nama sub pelajaran, total soal, jumlah benar, jumlah salah, dan persentase kebenaran.
- [ ] Queue worker harus jalan (`php artisan queue:work`) — di Docker sudah ada container `queue`.

## Catatan Penting

1. **Pastikan Queue Worker berjalan.** Di Docker production/dev, container `queue` sudah menjalankan `php artisan queue:work`. Jika develop tanpa Docker, jalankan manual di terminal terpisah:
   ```bash
   php artisan queue:work
   ```

2. **Urutan pengerjaan harus dari Tahap 1 ke Tahap 7** secara berurutan, karena setiap tahap bergantung pada tahap sebelumnya.

3. **Jangan ubah file `docker-compose.yml`** (production). Untuk development, gunakan `docker-compose.dev.yml`.
