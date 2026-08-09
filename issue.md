# 🎯 ISSUE: Fitur Analisis AI pada Raport Peserta

## Ringkasan

Menambahkan fitur **Analisis AI** pada halaman **View Raport** (`/admin/report-cards/{id}/view`).

Ketika admin sudah men-generate raport peserta dari halaman **Manajemen Peserta** (`/admin/participants`), raport yang sudah selesai akan ditampilkan di halaman view raport. **Fitur baru ini** akan menambahkan section analisis AI di **bagian paling bawah** halaman view raport tersebut.

Analisis AI ini menggunakan **OpenAI API** yang sudah ada di project (lihat `app/Services/AIService.php`), dan kontennya berisi analisis mendalam berdasarkan data raport yang sudah di-generate (per mata pelajaran dan sub mata pelajaran).

---

## Referensi File yang Sudah Ada

Sebelum mulai coding, **baca dan pahami** file-file ini:

| File | Fungsi |
|---|---|
| `app/Http/Controllers/Admin/ParticipantController.php` | Controller utama. Method `viewReport()` (baris 210-220) menampilkan halaman raport |
| `app/Models/ReportCard.php` | Model raport. Field penting: `report_data` (JSON berisi data per kategori/sub-kategori) |
| `app/Jobs/GenerateReportCardJob.php` | Job yang men-generate data raport. Pahami struktur `report_data` yang dihasilkan |
| `app/Services/AIService.php` | Service AI yang sudah ada. Ada method `generateAnalysis()` dan `generateAggregateAnalysis()` |
| `resources/views/admin/participants/report.blade.php` | View halaman raport (84 baris). Ini yang akan ditambah section AI |
| `database/migrations/2026_08_08_120000_create_report_cards_table.php` | Struktur tabel `report_cards` |
| `.env` | Konfigurasi OpenAI API Key, Model, dan Base URL sudah ada (baris 68-71) |

---

## Struktur Data `report_data` yang Sudah Ada

Data raport disimpan di kolom `report_data` (JSON) pada tabel `report_cards`. Strukturnya seperti ini:

```json
[
  {
    "category_name": "Matematika",
    "total_soal": 20,
    "total_benar": 15,
    "total_salah": 5,
    "sub_categories": [
      {
        "sub_category_name": "Aljabar",
        "total_soal": 10,
        "total_benar": 8,
        "total_salah": 2
      },
      {
        "sub_category_name": "Geometri",
        "total_soal": 10,
        "total_benar": 7,
        "total_salah": 3
      }
    ]
  },
  {
    "category_name": "IPA",
    "total_soal": 15,
    "total_benar": 10,
    "total_salah": 5,
    "sub_categories": [
      {
        "sub_category_name": "Biologi",
        "total_soal": 8,
        "total_benar": 6,
        "total_salah": 2
      }
    ]
  }
]
```

Data inilah yang akan dikirim ke OpenAI API untuk dianalisis.

---

## Tahapan Implementasi

---

### TAHAP 1: Buat Migration — Tambah Kolom `ai_analysis` di Tabel `report_cards`

**Tujuan:** Menambah kolom baru untuk menyimpan hasil analisis AI.

**Langkah-langkah:**

1. Jalankan command untuk membuat migration baru:
   ```bash
   php artisan make:migration add_ai_analysis_to_report_cards_table
   ```

2. Buka file migration yang baru dibuat di `database/migrations/`, lalu isi seperti ini:

   ```php
   <?php

   use Illuminate\Database\Migrations\Migration;
   use Illuminate\Database\Schema\Blueprint;
   use Illuminate\Support\Facades\Schema;

   return new class extends Migration
   {
       public function up(): void
       {
           Schema::table('report_cards', function (Blueprint $table) {
               $table->json('ai_analysis')->nullable()->after('report_data');
               $table->string('ai_analysis_status')->default('pending')->after('ai_analysis');
               // Nilai status: pending | processing | completed | failed
           });
       }

       public function down(): void
       {
           Schema::table('report_cards', function (Blueprint $table) {
               $table->dropColumn(['ai_analysis', 'ai_analysis_status']);
           });
       }
   };
   ```

3. Jalankan migration:
   ```bash
   php artisan migrate
   ```

**Penjelasan kolom baru:**
- `ai_analysis` (JSON, nullable) → Menyimpan hasil analisis AI dalam format JSON
- `ai_analysis_status` (string, default 'pending') → Status proses analisis: `pending`, `processing`, `completed`, `failed`

---

### TAHAP 2: Update Model `ReportCard`

**Tujuan:** Menambahkan kolom baru ke `$fillable` dan `$casts` di model.

**File:** `app/Models/ReportCard.php`

**Ubah menjadi:**

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
        'ai_analysis',           // ← TAMBAH INI
        'ai_analysis_status',    // ← TAMBAH INI
    ];

    protected $casts = [
        'session_ids'  => 'array',
        'report_data'  => 'array',
        'ai_analysis'  => 'array',   // ← TAMBAH INI
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

**Yang berubah:**
- Tambah `'ai_analysis'` dan `'ai_analysis_status'` ke array `$fillable`
- Tambah `'ai_analysis' => 'array'` ke array `$casts`

---

### TAHAP 3: Tambah Method Baru di `AIService`

**Tujuan:** Membuat method khusus untuk menganalisis data raport.

**File:** `app/Services/AIService.php`

**Tambahkan method baru berikut di akhir class (sebelum closing `}` terakhir):**

```php
/**
 * Analisis raport peserta berdasarkan data report_data.
 *
 * @param array $data  Berisi: participant_name, report_data (array kategori & sub-kategori)
 * @return array|null  Berisi: ringkasan, kelebihan, kekurangan, rekomendasi
 */
public function generateReportCardAnalysis(array $data): ?array
{
    try {
        $prompt = $this->buildReportCardPrompt($data);

        $response = Http::timeout((int) env('OPENAI_TIMEOUT', 60))
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model'    => $this->model,
                'messages' => [
                    [
                        'role'    => 'system',
                        'content' => 'Anda adalah Konsultan Pendidikan Senior yang ahli dalam analisis hasil belajar siswa. Tugas Anda adalah memberikan analisis raport yang mendalam, spesifik, dan actionable berdasarkan data per mata pelajaran dan sub mata pelajaran. Gunakan bahasa Indonesia yang profesional namun mudah dipahami. Output wajib dalam format JSON murni (tanpa markdown) dengan keys: ringkasan, kelebihan, kekurangan, rekomendasi. Setiap value berupa string berisi 2-3 paragraf.',
                    ],
                    [
                        'role'    => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.8,
            ]);

        if ($response->successful()) {
            $content = $response->json()['choices'][0]['message']['content'];
            $content = str_replace(['```json', '```'], '', $content);
            $decoded = json_decode(trim($content), true);

            // Pastikan semua key yang diharapkan berupa string
            if (is_array($decoded)) {
                foreach (['ringkasan', 'kelebihan', 'kekurangan', 'rekomendasi'] as $key) {
                    if (isset($decoded[$key]) && is_array($decoded[$key])) {
                        $decoded[$key] = implode("\n\n", array_map(
                            fn($v) => is_array($v) ? json_encode($v) : (string) $v,
                            $decoded[$key]
                        ));
                    }
                }
            }

            return $decoded;
        }

        Log::error('OpenAI ReportCard Analysis Error: ' . $response->body());
        return null;

    } catch (\Exception $e) {
        Log::error('AI ReportCard Analysis Error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Bangun prompt untuk analisis raport.
 */
protected function buildReportCardPrompt(array $data): string
{
    $name = $data['participant_name'];
    $reportData = $data['report_data'];

    // Bangun ringkasan per mata pelajaran
    $categoryLines = "";
    $totalSoalAll = 0;
    $totalBenarAll = 0;
    $totalSalahAll = 0;

    foreach ($reportData as $category) {
        $categoryName = $category['category_name'];
        $totalSoal    = $category['total_soal'];
        $totalBenar   = $category['total_benar'];
        $totalSalah   = $category['total_salah'];
        $persen       = $totalSoal > 0 ? round(($totalBenar / $totalSoal) * 100) : 0;

        $totalSoalAll  += $totalSoal;
        $totalBenarAll += $totalBenar;
        $totalSalahAll += $totalSalah;

        $categoryLines .= "\n📚 $categoryName: $totalBenar benar dari $totalSoal soal ($persen%)\n";

        // Detail sub-kategori
        if (!empty($category['sub_categories'])) {
            foreach ($category['sub_categories'] as $sub) {
                $subPersen = $sub['total_soal'] > 0
                    ? round(($sub['total_benar'] / $sub['total_soal']) * 100)
                    : 0;
                $categoryLines .= "   - {$sub['sub_category_name']}: {$sub['total_benar']} benar dari {$sub['total_soal']} soal ($subPersen%)\n";
            }
        }
    }

    $persenAll = $totalSoalAll > 0 ? round(($totalBenarAll / $totalSoalAll) * 100) : 0;

    return "Berikan analisis raport ujian untuk siswa bernama: $name.

Berikut data raport per mata pelajaran dan sub mata pelajaran:
$categoryLines

Ringkasan Keseluruhan:
- Total Soal: $totalSoalAll
- Total Benar: $totalBenarAll
- Total Salah: $totalSalahAll
- Persentase Keseluruhan: $persenAll%

Instruksi Analisis:
1. **ringkasan**: Berikan gambaran umum performa siswa secara keseluruhan. Sebutkan persentase total dan highlight bidang terkuat dan terlemah.
2. **kelebihan**: Identifikasi mata pelajaran dan sub mata pelajaran yang dikuasai dengan baik (persentase tinggi). Jelaskan apa artinya bagi siswa.
3. **kekurangan**: Analisis mata pelajaran dan sub mata pelajaran yang masih lemah. Jelaskan apakah kelemahannya merata atau terpusat di sub-bidang tertentu.
4. **rekomendasi**: Berikan saran belajar yang spesifik dan taktis per mata pelajaran yang lemah. Prioritaskan sub mata pelajaran yang paling butuh perbaikan.

Berikan jawaban dalam format JSON murni (tanpa markdown wrapper) dengan keys: ringkasan, kelebihan, kekurangan, rekomendasi. Setiap value berupa narasi 2-3 paragraf.";
}
```

---

### TAHAP 4: Buat Job `GenerateReportCardAiAnalysisJob`

**Tujuan:** Membuat background job yang memanggil AI API, supaya tidak blocking request HTTP.

**Langkah:**

1. Buat file baru: `app/Jobs/GenerateReportCardAiAnalysisJob.php`

2. Isi file tersebut:

```php
<?php

namespace App\Jobs;

use App\Models\ReportCard;
use App\Services\AIService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateReportCardAiAnalysisJob implements ShouldQueue
{
    use Queueable;

    public int $reportCardId;

    /**
     * Buat instance baru.
     */
    public function __construct(int $reportCardId)
    {
        $this->reportCardId = $reportCardId;
    }

    /**
     * Jalankan job.
     */
    public function handle(): void
    {
        $reportCard = ReportCard::with('user')->find($this->reportCardId);

        // Guard: pastikan report card ada dan sudah completed
        if (!$reportCard || $reportCard->status !== 'completed') {
            Log::warning("ReportCardAiAnalysis: ReportCard #{$this->reportCardId} not found or not completed.");
            return;
        }

        // Guard: pastikan ada report_data
        if (empty($reportCard->report_data)) {
            Log::warning("ReportCardAiAnalysis: ReportCard #{$this->reportCardId} has no report_data.");
            $reportCard->update(['ai_analysis_status' => 'failed']);
            return;
        }

        try {
            // Update status jadi processing
            $reportCard->update(['ai_analysis_status' => 'processing']);

            // Panggil AI Service
            $aiService = new AIService();
            $analysis = $aiService->generateReportCardAnalysis([
                'participant_name' => $reportCard->user->name,
                'report_data'      => $reportCard->report_data,
            ]);

            if ($analysis) {
                $reportCard->update([
                    'ai_analysis'        => $analysis,
                    'ai_analysis_status' => 'completed',
                ]);
                Log::info("ReportCardAiAnalysis: ReportCard #{$this->reportCardId} analysis completed.");
            } else {
                $reportCard->update(['ai_analysis_status' => 'failed']);
                Log::error("ReportCardAiAnalysis: ReportCard #{$this->reportCardId} AI returned null.");
            }

        } catch (\Exception $e) {
            Log::error("ReportCardAiAnalysis: ReportCard #{$this->reportCardId} failed: " . $e->getMessage());
            $reportCard->update(['ai_analysis_status' => 'failed']);
        }
    }
}
```

---

### TAHAP 5: Tambah Route dan Controller Method

**Tujuan:** Menambahkan 2 endpoint baru:
1. `POST /admin/report-cards/{id}/generate-ai-analysis` → Trigger generate analisis AI
2. `GET /admin/report-cards/{id}/ai-analysis-status` → Polling status analisis AI

#### 5A. Tambah Route

**File:** `routes/web.php`

Cari blok route yang berisi route report-cards (sekitar baris 77-78). Tambahkan 2 route baru **tepat di bawah** baris `Route::get('/admin/report-cards/{id}/view', ...)`:

```php
// Route yang sudah ada (JANGAN HAPUS):
Route::get('/admin/report-cards/{id}/view', [\App\Http\Controllers\Admin\ParticipantController::class, 'viewReport'])->name('admin.report-cards.view');

// ↓↓↓ TAMBAH 2 ROUTE BARU DI BAWAH INI ↓↓↓
Route::post('/admin/report-cards/{id}/generate-ai-analysis', [\App\Http\Controllers\Admin\ParticipantController::class, 'generateAiAnalysis'])->name('admin.report-cards.generate-ai-analysis');
Route::get('/admin/report-cards/{id}/ai-analysis-status', [\App\Http\Controllers\Admin\ParticipantController::class, 'aiAnalysisStatus'])->name('admin.report-cards.ai-analysis-status');
```

#### 5B. Tambah Method di Controller

**File:** `app/Http/Controllers/Admin/ParticipantController.php`

Pertama, tambahkan import di bagian atas file (setelah `use App\Jobs\GenerateReportCardJob;`):

```php
use App\Jobs\GenerateReportCardAiAnalysisJob;
```

Lalu tambahkan 2 method baru di akhir class (sebelum closing `}` terakhir, setelah method `viewReport()`):

```php
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
```

---

### TAHAP 6: Update View — Tambah Section Analisis AI di Bawah Raport

**Tujuan:** Menambahkan section analisis AI di **bagian paling bawah** halaman view raport.

**File:** `resources/views/admin/participants/report.blade.php`

**Ganti SELURUH isi file** menjadi seperti di bawah ini. Perhatikan bahwa yang berubah hanya bagian setelah `@endif` terakhir dari loop `@foreach` — di situ ditambahkan section baru untuk analisis AI:

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

{{-- ============================================================ --}}
{{-- SECTION BARU: Analisis AI (di bawah raport) --}}
{{-- ============================================================ --}}
<div class="glass animate-fade-in" style="padding: 32px; margin-bottom: 24px;" id="aiAnalysisSection">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h3 style="font-family: 'Outfit', sans-serif; margin-bottom: 4px;">
                <i class="fas fa-robot" style="color: #8b5cf6;"></i> Analisis AI
            </h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">
                Analisis otomatis berdasarkan data raport di atas menggunakan AI.
            </p>
        </div>

        {{-- Tombol Generate hanya muncul jika belum ada analisis --}}
        @if($reportCard->ai_analysis_status !== 'completed')
            <button class="btn-primary" id="btnGenerateAi" onclick="generateAiAnalysis()" style="background: linear-gradient(135deg, #8b5cf6, #6366f1);">
                <i class="fas fa-magic"></i> Generate Analisis AI
            </button>
        @else
            <button class="btn-primary" id="btnRegenerateAi" onclick="generateAiAnalysis(true)" style="background: transparent; border: 1px solid var(--glass-border); color: var(--text-secondary);">
                <i class="fas fa-redo"></i> Re-generate
            </button>
        @endif
    </div>

    {{-- State 1: Loading / Processing --}}
    <div id="aiLoading" style="display: none; text-align: center; padding: 40px;">
        <div style="width: 50px; height: 50px; border: 3px solid rgba(139, 92, 246, 0.3); border-top-color: #8b5cf6; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 16px;"></div>
        <p style="color: var(--text-secondary); font-size: 0.95rem;">Sedang menganalisis raport dengan AI...</p>
        <p style="color: var(--text-secondary); font-size: 0.8rem;">Proses ini membutuhkan waktu 10-30 detik.</p>
    </div>

    {{-- State 2: Belum ada analisis --}}
    <div id="aiEmpty" style="{{ $reportCard->ai_analysis_status === 'completed' ? 'display: none;' : '' }} text-align: center; padding: 40px;">
        <div style="width: 60px; height: 60px; background: rgba(139, 92, 246, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
            <i class="fas fa-robot" style="font-size: 1.5rem; color: #8b5cf6;"></i>
        </div>
        <p style="color: var(--text-secondary);">Belum ada analisis AI. Klik tombol <strong>"Generate Analisis AI"</strong> untuk memulai.</p>
    </div>

    {{-- State 3: Hasil analisis AI --}}
    <div id="aiResult" style="{{ $reportCard->ai_analysis_status !== 'completed' ? 'display: none;' : '' }}">
        @if($reportCard->ai_analysis_status === 'completed' && $reportCard->ai_analysis)
            @php $ai = $reportCard->ai_analysis; @endphp

            {{-- Ringkasan --}}
            @if(!empty($ai['ringkasan']))
            <div style="margin-bottom: 24px; padding: 20px; background: rgba(59, 130, 246, 0.05); border-left: 4px solid #3b82f6; border-radius: 0 12px 12px 0;">
                <h4 style="margin: 0 0 12px 0; color: #3b82f6; font-family: 'Outfit', sans-serif;">
                    <i class="fas fa-chart-line"></i> Ringkasan
                </h4>
                <p style="color: var(--text-primary); line-height: 1.7; margin: 0; white-space: pre-line;">{{ $ai['ringkasan'] }}</p>
            </div>
            @endif

            {{-- Kelebihan --}}
            @if(!empty($ai['kelebihan']))
            <div style="margin-bottom: 24px; padding: 20px; background: rgba(16, 185, 129, 0.05); border-left: 4px solid #10b981; border-radius: 0 12px 12px 0;">
                <h4 style="margin: 0 0 12px 0; color: #10b981; font-family: 'Outfit', sans-serif;">
                    <i class="fas fa-star"></i> Kelebihan
                </h4>
                <p style="color: var(--text-primary); line-height: 1.7; margin: 0; white-space: pre-line;">{{ $ai['kelebihan'] }}</p>
            </div>
            @endif

            {{-- Kekurangan --}}
            @if(!empty($ai['kekurangan']))
            <div style="margin-bottom: 24px; padding: 20px; background: rgba(239, 68, 68, 0.05); border-left: 4px solid #ef4444; border-radius: 0 12px 12px 0;">
                <h4 style="margin: 0 0 12px 0; color: #ef4444; font-family: 'Outfit', sans-serif;">
                    <i class="fas fa-exclamation-triangle"></i> Kekurangan
                </h4>
                <p style="color: var(--text-primary); line-height: 1.7; margin: 0; white-space: pre-line;">{{ $ai['kekurangan'] }}</p>
            </div>
            @endif

            {{-- Rekomendasi --}}
            @if(!empty($ai['rekomendasi']))
            <div style="margin-bottom: 0; padding: 20px; background: rgba(139, 92, 246, 0.05); border-left: 4px solid #8b5cf6; border-radius: 0 12px 12px 0;">
                <h4 style="margin: 0 0 12px 0; color: #8b5cf6; font-family: 'Outfit', sans-serif;">
                    <i class="fas fa-lightbulb"></i> Rekomendasi
                </h4>
                <p style="color: var(--text-primary); line-height: 1.7; margin: 0; white-space: pre-line;">{{ $ai['rekomendasi'] }}</p>
            </div>
            @endif
        @endif
    </div>

    {{-- State 4: Gagal --}}
    <div id="aiFailed" style="display: none; text-align: center; padding: 40px;">
        <div style="width: 60px; height: 60px; background: rgba(239, 68, 68, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
            <i class="fas fa-times" style="font-size: 1.5rem; color: #ef4444;"></i>
        </div>
        <p style="color: #ef4444; font-weight: 600;">Gagal menghasilkan analisis AI.</p>
        <p style="color: var(--text-secondary); font-size: 0.85rem;">Silakan coba lagi dengan menekan tombol "Generate Analisis AI".</p>
    </div>
</div>

<style>
    @keyframes spin { to { transform: rotate(360deg); } }
</style>
@endsection

@push('scripts')
<script>
    const REPORT_CARD_ID = {{ $reportCard->id }};
    let aiPollingInterval = null;

    /**
     * Fungsi utama untuk memulai generate analisis AI.
     * @param {boolean} isRegenerate - true jika user klik "Re-generate"
     */
    function generateAiAnalysis(isRegenerate = false) {
        // Konfirmasi jika re-generate
        if (isRegenerate) {
            if (!confirm('Yakin ingin men-generate ulang analisis AI? Hasil sebelumnya akan ditimpa.')) {
                return;
            }
        }

        // Tampilkan state loading
        showAiState('loading');

        // Kirim POST request ke backend
        fetch(`/admin/report-cards/${REPORT_CARD_ID}/generate-ai-analysis`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    || document.querySelector('input[name="_token"]')?.value,
                'Accept': 'application/json'
            },
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                if (data.data.ai_analysis_status === 'completed' && data.data.ai_analysis) {
                    // Sudah selesai (mungkin dari cache)
                    renderAiResult(data.data.ai_analysis);
                    showAiState('result');
                } else {
                    // Mulai polling
                    startAiPolling();
                }
            } else {
                showToast(data.message || 'Gagal memulai analisis AI.', 'error');
                showAiState('empty');
            }
        })
        .catch(err => {
            console.error('Error generating AI analysis:', err);
            showToast('Terjadi kesalahan sistem.', 'error');
            showAiState('empty');
        });
    }

    /**
     * Polling status analisis AI setiap 3 detik.
     */
    function startAiPolling() {
        // Clear polling sebelumnya jika ada
        if (aiPollingInterval) clearInterval(aiPollingInterval);

        aiPollingInterval = setInterval(() => {
            fetch(`/admin/report-cards/${REPORT_CARD_ID}/ai-analysis-status`, {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                const status = data.data.ai_analysis_status;

                if (status === 'completed') {
                    clearInterval(aiPollingInterval);
                    aiPollingInterval = null;
                    renderAiResult(data.data.ai_analysis);
                    showAiState('result');
                    showToast('Analisis AI berhasil digenerate!');
                } else if (status === 'failed') {
                    clearInterval(aiPollingInterval);
                    aiPollingInterval = null;
                    showAiState('failed');
                    showToast('Gagal menghasilkan analisis AI.', 'error');
                }
                // Jika masih 'processing', polling lanjut
            })
            .catch(() => {
                clearInterval(aiPollingInterval);
                aiPollingInterval = null;
                showAiState('failed');
            });
        }, 3000); // Poll setiap 3 detik
    }

    /**
     * Render hasil analisis AI ke dalam DOM.
     * @param {object} analysis - Object dengan keys: ringkasan, kelebihan, kekurangan, rekomendasi
     */
    function renderAiResult(analysis) {
        if (!analysis) return;

        const sections = [
            { key: 'ringkasan',   icon: 'fa-chart-line',           color: '#3b82f6', bgColor: 'rgba(59, 130, 246, 0.05)',  label: 'Ringkasan' },
            { key: 'kelebihan',   icon: 'fa-star',                 color: '#10b981', bgColor: 'rgba(16, 185, 129, 0.05)',  label: 'Kelebihan' },
            { key: 'kekurangan',  icon: 'fa-exclamation-triangle', color: '#ef4444', bgColor: 'rgba(239, 68, 68, 0.05)',   label: 'Kekurangan' },
            { key: 'rekomendasi', icon: 'fa-lightbulb',            color: '#8b5cf6', bgColor: 'rgba(139, 92, 246, 0.05)',  label: 'Rekomendasi' },
        ];

        let html = '';
        sections.forEach((section, index) => {
            const content = analysis[section.key];
            if (content) {
                const isLast = index === sections.length - 1;
                html += `
                    <div style="margin-bottom: ${isLast ? '0' : '24px'}; padding: 20px; background: ${section.bgColor}; border-left: 4px solid ${section.color}; border-radius: 0 12px 12px 0;">
                        <h4 style="margin: 0 0 12px 0; color: ${section.color}; font-family: 'Outfit', sans-serif;">
                            <i class="fas ${section.icon}"></i> ${section.label}
                        </h4>
                        <p style="color: var(--text-primary); line-height: 1.7; margin: 0; white-space: pre-line;">${escapeHtml(content)}</p>
                    </div>
                `;
            }
        });

        document.getElementById('aiResult').innerHTML = html;

        // Update tombol: ganti Generate jadi Re-generate
        const btnGenerate = document.getElementById('btnGenerateAi');
        if (btnGenerate) {
            btnGenerate.outerHTML = `
                <button class="btn-primary" id="btnRegenerateAi" onclick="generateAiAnalysis(true)" style="background: transparent; border: 1px solid var(--glass-border); color: var(--text-secondary);">
                    <i class="fas fa-redo"></i> Re-generate
                </button>
            `;
        }
    }

    /**
     * Tampilkan state tertentu, sembunyikan yang lain.
     * @param {string} state - 'loading' | 'empty' | 'result' | 'failed'
     */
    function showAiState(state) {
        document.getElementById('aiLoading').style.display = state === 'loading' ? 'block' : 'none';
        document.getElementById('aiEmpty').style.display   = state === 'empty'   ? 'block' : 'none';
        document.getElementById('aiResult').style.display   = state === 'result'  ? 'block' : 'none';
        document.getElementById('aiFailed').style.display   = state === 'failed'  ? 'block' : 'none';
    }

    /**
     * Escape HTML untuk mencegah XSS.
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }
</script>
@endpush
```

---

### TAHAP 7: Testing

Lakukan testing dengan urutan berikut:

#### 7A. Pastikan Migration Berhasil

```bash
php artisan migrate:status
```

Pastikan migration baru (`add_ai_analysis_to_report_cards_table`) sudah ter-migrate.

#### 7B. Pastikan Queue Worker Berjalan

```bash
php artisan queue:work --tries=3
```

Job analisis AI akan berjalan di background melalui queue. Pastikan queue worker aktif.

#### 7C. Test Flow Lengkap

1. Buka halaman `/admin/participants`
2. Klik icon "Cetak Raport" (ikon hijau) pada salah satu peserta
3. Pilih sesi ujian, klik "Generate Raport"
4. Setelah raport selesai, klik "Lihat Raport" → Ini akan membuka halaman `/admin/report-cards/{id}/view`
5. Scroll ke bawah, akan ada section "Analisis AI"
6. Klik tombol **"Generate Analisis AI"**
7. Tunggu loading spinner (10-30 detik)
8. Setelah selesai, akan muncul 4 section: Ringkasan, Kelebihan, Kekurangan, Rekomendasi
9. Test tombol **"Re-generate"** untuk memastikan analisis bisa digenerate ulang

#### 7D. Test Error Handling

1. Matikan queue worker, klik "Generate Analisis AI"
2. Pastikan status tetap "processing" dan tidak crash
3. Coba set `OPENAI_API_KEY` ke value salah, pastikan status berubah ke "failed" dan UI menampilkan pesan error

---

## Checklist Akhir

Sebelum PR / merge, pastikan:

- [ ] Migration baru sudah dibuat dan berhasil dijalankan
- [ ] Model `ReportCard` sudah diupdate (`$fillable` dan `$casts`)
- [ ] Method `generateReportCardAnalysis()` dan `buildReportCardPrompt()` sudah ditambahkan di `AIService`
- [ ] Job `GenerateReportCardAiAnalysisJob` sudah dibuat
- [ ] 2 Route baru sudah ditambahkan di `web.php`
- [ ] 2 Method baru (`generateAiAnalysis`, `aiAnalysisStatus`) sudah ditambahkan di `ParticipantController`
- [ ] View `report.blade.php` sudah diupdate dengan section Analisis AI + JavaScript polling
- [ ] Queue worker berjalan dan job berhasil dieksekusi
- [ ] Tidak ada error di `storage/logs/laravel.log`
- [ ] Tombol Generate dan Re-generate berfungsi
- [ ] XSS prevention sudah diterapkan (`escapeHtml()` di JavaScript)

---

## Daftar File yang Diubah / Dibuat

| Aksi | File |
|---|---|
| **BUAT BARU** | `database/migrations/xxxx_add_ai_analysis_to_report_cards_table.php` |
| **BUAT BARU** | `app/Jobs/GenerateReportCardAiAnalysisJob.php` |
| **EDIT** | `app/Models/ReportCard.php` (tambah `$fillable` dan `$casts`) |
| **EDIT** | `app/Services/AIService.php` (tambah 2 method baru) |
| **EDIT** | `routes/web.php` (tambah 2 route baru) |
| **EDIT** | `app/Http/Controllers/Admin/ParticipantController.php` (tambah 2 method + 1 import) |
| **EDIT** | `resources/views/admin/participants/report.blade.php` (tambah section AI + JS) |
