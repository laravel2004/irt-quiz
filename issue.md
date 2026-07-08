# Planning: Tambah Detail Nilai per Mata Pelajaran di Halaman Hasil & Laporan

## Ringkasan Kebutuhan

Pada dua halaman berikut:

1. **Halaman Hasil** → `http://127.0.0.1:8000/dashboard/result/{registrationId}`
2. **Halaman Laporan** → `http://127.0.0.1:8000/dashboard/review/{registrationId}`

Perlu ditambahkan **section Detail Nilai per Mata Pelajaran** di bawah section total skor yang sudah ada.

Detail nilai ini menampilkan breakdown skor untuk setiap mata pelajaran (category), sehingga peserta bisa melihat performa per bidang pelajaran, bukan hanya total keseluruhan.

Dokumen ini berisi tahapan implementasi detail agar bisa dikerjakan oleh junior programmer atau AI model yang lebih murah secara aman dan bertahap.

---

## Tujuan Implementasi

- Menampilkan tabel/card detail nilai per mata pelajaran di bawah section total skor.
- Setiap mata pelajaran menampilkan: nama, skor raw, skor IRT, jumlah benar, jumlah salah, jumlah kosong.
- Data diambil dari tabel `exam_category_results` yang **sudah ada** di database.
- Tidak perlu membuat migration baru.
- Tidak perlu mengubah logika perhitungan skor.
- Hanya perlu menambah eager loading di controller dan section HTML di view.

---

## Lokasi Halaman

### Halaman Hasil (Result)

```text
Route:   GET /dashboard/result/{registrationId}
Name:    participant.result
```

### Halaman Laporan (Review)

```text
Route:   GET /dashboard/review/{registrationId}
Name:    participant.review
```

---

## File yang Akan Diubah

Hanya **3 file** yang perlu diubah:

| No | File | Jenis Perubahan |
|----|------|-----------------|
| 1 | `app/Http/Controllers/Participant/DashboardController.php` | Tambah eager loading |
| 2 | `resources/views/participant/result.blade.php` | Tambah section detail nilai |
| 3 | `resources/views/participant/review.blade.php` | Tambah section detail nilai |

File yang **TIDAK PERLU** diubah:

- `routes/web.php` — route sudah ada
- Database migration — tabel `exam_category_results` sudah ada
- `app/Models/ExamResult.php` — relasi `categoryResults()` sudah ada
- `app/Models/ExamCategoryResult.php` — model sudah ada
- `app/Services/AssessmentService.php` — perhitungan sudah menyimpan data per category

---

## Pemahaman Struktur Data (Penting Dibaca Dulu!)

### Tabel dan Model yang Sudah Ada

Sebelum coding, pahami data flow berikut:

```
ExamSessionParticipant (peserta ujian)
    └── result (hasOne → ExamResult)
            └── categoryResults (hasMany → ExamCategoryResult)
                    └── category (belongsTo → Category)
```

### Tabel `exam_category_results`

Tabel ini **sudah ada** dan sudah diisi otomatis oleh `AssessmentService::calculateIRT()`.

Kolom yang tersedia:

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint | Primary key |
| `exam_result_id` | bigint (FK) | Merujuk ke `exam_results.id` |
| `category_id` | bigint (FK) | Merujuk ke `categories.id` (mata pelajaran) |
| `total_correct` | integer | Jumlah jawaban benar untuk mata pelajaran ini |
| `total_incorrect` | integer | Jumlah jawaban salah untuk mata pelajaran ini |
| `total_blank` | integer | Jumlah soal yang tidak dijawab |
| `score` | float | Skor raw (sudah di-scale sesuai `max_score_raw`) |
| `irt_score` | float | Skor IRT (sudah di-scale sesuai `max_score_irt`) |

### Model `ExamResult` (file: `app/Models/ExamResult.php`)

Sudah memiliki relasi:

```php
public function categoryResults() { return $this->hasMany(ExamCategoryResult::class); }
```

### Model `ExamCategoryResult` (file: `app/Models/ExamCategoryResult.php`)

Sudah memiliki relasi:

```php
public function category() { return $this->belongsTo(Category::class); }
```

### Tabel `exam_session_categories`

Tabel ini menyimpan konfigurasi skor maksimal per mata pelajaran per sesi ujian.

Kolom yang relevan:

| Kolom | Keterangan |
|-------|------------|
| `category_id` | Mata pelajaran |
| `max_score_raw` | Skor raw maksimal untuk mata pelajaran ini |
| `max_score_irt` | Skor IRT maksimal untuk mata pelajaran ini |

Data ini bisa diakses melalui `$registration->examSession->sessionCategories`.

---

## Tahapan Implementasi

### Tahap 1: Update Controller — Method `showResult()`

**File:** `app/Http/Controllers/Participant/DashboardController.php`

**Method yang diubah:** `showResult()`

**Apa yang perlu dilakukan:**
Tambahkan eager loading untuk `result.categoryResults.category` dan `examSession.sessionCategories.category` agar data detail nilai per mata pelajaran tersedia di view tanpa query N+1.

**Kode saat ini (baris 102-119):**

```php
public function showResult($registrationId)
{
    $registration = ExamSessionParticipant::where('user_id', auth()->id())
        ->with(['examSession.sessionCategories', 'result'])
        ->findOrFail($registrationId);

    if (!$registration->result) {
        $assessmentService = new \App\Services\AssessmentService();
        $assessmentService->calculateIRT($registration->exam_session_id);
        $registration->load(['examSession.sessionCategories', 'result']);
    }

    if (!$registration->result) {
        return redirect()->route('participant.dashboard')->with('error', 'Hasil belum tersedia.');
    }

    return view('participant.result', compact('registration'));
}
```

**Yang perlu diubah:**

Ganti bagian `->with(...)` dan `->load(...)` untuk menambahkan eager loading `result.categoryResults.category` dan `examSession.sessionCategories.category`.

**Kode setelah diubah:**

```php
public function showResult($registrationId)
{
    $registration = ExamSessionParticipant::where('user_id', auth()->id())
        ->with([
            'examSession.sessionCategories.category',
            'result.categoryResults.category'
        ])
        ->findOrFail($registrationId);

    if (!$registration->result) {
        $assessmentService = new \App\Services\AssessmentService();
        $assessmentService->calculateIRT($registration->exam_session_id);
        $registration->load([
            'examSession.sessionCategories.category',
            'result.categoryResults.category'
        ]);
    }

    if (!$registration->result) {
        return redirect()->route('participant.dashboard')->with('error', 'Hasil belum tersedia.');
    }

    return view('participant.result', compact('registration'));
}
```

**Penjelasan perubahan:**
- `examSession.sessionCategories` → berubah menjadi `examSession.sessionCategories.category` agar nama mata pelajaran bisa diakses dari session category.
- Ditambahkan `result.categoryResults.category` agar data skor per mata pelajaran beserta nama category-nya langsung tersedia.

**Checklist tahap ini:**
- [ ] Buka file `app/Http/Controllers/Participant/DashboardController.php`
- [ ] Cari method `showResult()` (sekitar baris 102)
- [ ] Ubah eager loading sesuai contoh di atas
- [ ] Pastikan ada 2 tempat yang diubah: satu di `->with(...)` dan satu di `->load(...)`
- [ ] Jangan ubah logika lain di method ini

---

### Tahap 2: Update Controller — Method `showReview()`

**File:** `app/Http/Controllers/Participant/DashboardController.php`

**Method yang diubah:** `showReview()`

**Apa yang perlu dilakukan:**
Sama seperti Tahap 1, tambahkan eager loading `result.categoryResults.category`.

**Kode saat ini (baris 125-127):**

```php
$registration = ExamSessionParticipant::where('user_id', $user->id)
    ->with(['examSession.sessionCategories', 'questions.category', 'questions.subCategory', 'userAnswers', 'result'])
    ->findOrFail($registrationId);
```

**Yang perlu diubah:**

Tambahkan `result.categoryResults.category` ke dalam array `with()`, dan ubah `examSession.sessionCategories` menjadi `examSession.sessionCategories.category`.

**Kode setelah diubah:**

```php
$registration = ExamSessionParticipant::where('user_id', $user->id)
    ->with([
        'examSession.sessionCategories.category',
        'questions.category',
        'questions.subCategory',
        'userAnswers',
        'result.categoryResults.category'
    ])
    ->findOrFail($registrationId);
```

**Checklist tahap ini:**
- [ ] Cari method `showReview()` (sekitar baris 121)
- [ ] Ubah array `with()` sesuai contoh di atas
- [ ] Pastikan semua eager loading yang sudah ada tetap ada (jangan menghapus `questions.category`, `questions.subCategory`, `userAnswers`)
- [ ] Jangan ubah kode lain di method ini

---

### Tahap 3: Tambah Section Detail Nilai di `result.blade.php`

**File:** `resources/views/participant/result.blade.php`

**Apa yang perlu dilakukan:**
Tambahkan section baru berupa tabel/card yang menampilkan skor per mata pelajaran. Section ini diletakkan **di bawah stats-grid** (setelah baris yang menampilkan BENAR/SALAH/KOSONG) dan **sebelum section tombol** (sebelum `margin-top: 48px`).

**Lokasi yang tepat untuk menyisipkan kode baru:**

Cari kode berikut di view (sekitar baris 56-58):

```html
        </div>  <!-- tutup stats-grid -->

        <div style="margin-top: 48px; padding-top: 32px; ...">
```

Sisipkan section baru **di antara** closing div stats-grid dan opening div tombol.

**Kode yang perlu ditambahkan:**

```blade
        {{-- DETAIL NILAI PER MATA PELAJARAN --}}
        @if($registration->result && $registration->result->categoryResults && $registration->result->categoryResults->count() > 0)
        <div style="margin-top: 32px; padding-top: 32px; border-top: 1px solid var(--glass-border);">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
                <div style="width: 36px; height: 36px; background: rgba(var(--accent-rgb), 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-list-check" style="font-size: 1rem; color: var(--accent);"></i>
                </div>
                <h3 style="font-family: 'Outfit', sans-serif; margin: 0; font-size: 1.1rem; color: #0f172a;">Detail Nilai per Mata Pelajaran</h3>
            </div>

            <div style="display: grid; grid-template-columns: 1fr; gap: 12px;">
                @foreach($registration->result->categoryResults as $catResult)
                    @php
                        $catName = $catResult->category->name ?? 'Tidak Diketahui';

                        // Cari max score dari session categories
                        $sessionCat = $registration->examSession->sessionCategories
                            ->where('category_id', $catResult->category_id)
                            ->first();
                        $maxRawCat = $sessionCat->max_score_raw ?? 0;
                        $maxIrtCat = $sessionCat->max_score_irt ?? 0;
                    @endphp
                    <div class="glass" style="padding: 20px; border-radius: 16px; background: #f8fafc;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <span style="font-family: 'Outfit', sans-serif; font-weight: 600; font-size: 1rem; color: #0f172a;">{{ $catName }}</span>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 12px;">
                            <div style="background: #ffffff; padding: 12px; border-radius: 10px; text-align: center;">
                                <div style="font-size: 0.7rem; color: #475569; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px;">Skor Raw</div>
                                <div style="font-size: 1.5rem; font-weight: 700; font-family: 'Outfit', sans-serif; color: #0f172a;">{{ number_format($catResult->score, 1) }}</div>
                                <div style="font-size: 0.7rem; color: #94a3b8;">/ {{ $maxRawCat }}</div>
                            </div>
                            <div style="background: rgba(var(--accent-rgb), 0.05); padding: 12px; border-radius: 10px; text-align: center;">
                                <div style="font-size: 0.7rem; color: var(--accent); margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px;">Skor IRT</div>
                                <div style="font-size: 1.5rem; font-weight: 700; font-family: 'Outfit', sans-serif; color: var(--accent);">{{ round($catResult->irt_score) }}</div>
                                <div style="font-size: 0.7rem; color: #94a3b8;">/ {{ $maxIrtCat }}</div>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">
                            <div style="text-align: center; padding: 8px; background: rgba(16, 185, 129, 0.08); border-radius: 8px;">
                                <div style="font-size: 0.65rem; color: #10b981; margin-bottom: 2px;">BENAR</div>
                                <div style="font-size: 1rem; font-weight: 600; color: #10b981;">{{ $catResult->total_correct }}</div>
                            </div>
                            <div style="text-align: center; padding: 8px; background: rgba(239, 68, 68, 0.08); border-radius: 8px;">
                                <div style="font-size: 0.65rem; color: #ef4444; margin-bottom: 2px;">SALAH</div>
                                <div style="font-size: 1rem; font-weight: 600; color: #ef4444;">{{ $catResult->total_incorrect }}</div>
                            </div>
                            <div style="text-align: center; padding: 8px; background: rgba(71, 85, 105, 0.08); border-radius: 8px;">
                                <div style="font-size: 0.65rem; color: #475569; margin-bottom: 2px;">KOSONG</div>
                                <div style="font-size: 1rem; font-weight: 600; color: #0f172a;">{{ $catResult->total_blank }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
```

**Penjelasan kode:**
- `$registration->result->categoryResults` — mengambil semua data skor per category dari tabel `exam_category_results`
- `$catResult->category->name` — mengambil nama mata pelajaran dari tabel `categories`
- `$sessionCat` — mencari konfigurasi skor maksimal dari `exam_session_categories` untuk menampilkan "/ max"
- Style mengikuti design system yang sudah ada (glass, Outfit font, warna #10b981 untuk benar, #ef4444 untuk salah)

**Checklist tahap ini:**
- [ ] Buka file `resources/views/participant/result.blade.php`
- [ ] Cari closing div dari `stats-grid` (sekitar baris 56)
- [ ] Sisipkan kode baru SETELAH closing div `stats-grid` dan SEBELUM div `margin-top: 48px`
- [ ] Jangan menghapus atau mengubah kode yang sudah ada
- [ ] Pastikan semua tag HTML berpasangan (buka-tutup)

---

### Tahap 4: Tambah Section Detail Nilai di `review.blade.php`

**File:** `resources/views/participant/review.blade.php`

**Apa yang perlu dilakukan:**
Sama seperti Tahap 3, tambahkan section detail nilai per mata pelajaran. Di halaman review, letakkan **di bawah section SCORE OVERVIEW** (setelah div `glass animate-fade-in` yang menampilkan Total Skor Mentah) dan **sebelum section AI ANALYSIS**.

**Lokasi yang tepat untuk menyisipkan kode baru:**

Cari kode berikut di view (sekitar baris 64-67):

```html
    </div>  <!-- tutup SCORE OVERVIEW -->

    <!-- AI ANALYSIS -->
```

Sisipkan section baru **di antara** closing SCORE OVERVIEW dan opening AI ANALYSIS.

**Kode yang perlu ditambahkan:**

```blade
    {{-- DETAIL NILAI PER MATA PELAJARAN --}}
    @if($registration->result && $registration->result->categoryResults && $registration->result->categoryResults->count() > 0)
    <div class="glass animate-fade-in" style="padding: 32px; border-radius: 24px; margin-bottom: 32px;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px;">
            <div style="width: 40px; height: 40px; background: rgba(var(--accent-rgb), 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-list-check" style="font-size: 1.1rem; color: var(--accent);"></i>
            </div>
            <h3 style="font-family: 'Outfit', sans-serif; margin: 0; color: #0f172a;">Detail Nilai per Mata Pelajaran</h3>
        </div>

        <div style="display: grid; grid-template-columns: 1fr; gap: 12px;">
            @foreach($registration->result->categoryResults as $catResult)
                @php
                    $catName = $catResult->category->name ?? 'Tidak Diketahui';

                    // Cari max score dari session categories
                    $sessionCat = $registration->examSession->sessionCategories
                        ->where('category_id', $catResult->category_id)
                        ->first();
                    $maxRawCat = $sessionCat->max_score_raw ?? 0;
                    $maxIrtCat = $sessionCat->max_score_irt ?? 0;
                @endphp
                <div style="padding: 20px; border-radius: 16px; background: #f8fafc; border: 1px solid #e2e8f0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <span style="font-family: 'Outfit', sans-serif; font-weight: 600; font-size: 1rem; color: #0f172a;">{{ $catName }}</span>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 12px;">
                        <div style="background: #ffffff; padding: 12px; border-radius: 10px; text-align: center;">
                            <div style="font-size: 0.7rem; color: #475569; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px;">Skor Raw</div>
                            <div style="font-size: 1.5rem; font-weight: 700; font-family: 'Outfit', sans-serif; color: #0f172a;">{{ number_format($catResult->score, 1) }}</div>
                            <div style="font-size: 0.7rem; color: #94a3b8;">/ {{ $maxRawCat }}</div>
                        </div>
                        <div style="background: rgba(var(--accent-rgb), 0.05); padding: 12px; border-radius: 10px; text-align: center;">
                            <div style="font-size: 0.7rem; color: var(--accent); margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px;">Skor IRT</div>
                            <div style="font-size: 1.5rem; font-weight: 700; font-family: 'Outfit', sans-serif; color: var(--accent);">{{ round($catResult->irt_score) }}</div>
                            <div style="font-size: 0.7rem; color: #94a3b8;">/ {{ $maxIrtCat }}</div>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">
                        <div style="text-align: center; padding: 8px; background: rgba(16, 185, 129, 0.08); border-radius: 8px;">
                            <div style="font-size: 0.65rem; color: #10b981; margin-bottom: 2px;">BENAR</div>
                            <div style="font-size: 1rem; font-weight: 600; color: #10b981;">{{ $catResult->total_correct }}</div>
                        </div>
                        <div style="text-align: center; padding: 8px; background: rgba(239, 68, 68, 0.08); border-radius: 8px;">
                            <div style="font-size: 0.65rem; color: #ef4444; margin-bottom: 2px;">SALAH</div>
                            <div style="font-size: 1rem; font-weight: 600; color: #ef4444;">{{ $catResult->total_incorrect }}</div>
                        </div>
                        <div style="text-align: center; padding: 8px; background: rgba(71, 85, 105, 0.08); border-radius: 8px;">
                            <div style="font-size: 0.65rem; color: #475569; margin-bottom: 2px;">KOSONG</div>
                            <div style="font-size: 1rem; font-weight: 600; color: #0f172a;">{{ $catResult->total_blank }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif
```

**Checklist tahap ini:**
- [ ] Buka file `resources/views/participant/review.blade.php`
- [ ] Cari closing div dari SCORE OVERVIEW (sekitar baris 64)
- [ ] Sisipkan kode baru SETELAH closing SCORE OVERVIEW dan SEBELUM comment `<!-- AI ANALYSIS -->`
- [ ] Jangan menghapus atau mengubah kode yang sudah ada
- [ ] Pastikan semua tag HTML berpasangan

---

## Validasi Manual

### Skenario Testing

Setelah implementasi, lakukan testing manual di browser.

#### 1. Halaman Hasil (`/dashboard/result/{id}`)

- [ ] Buka halaman hasil untuk registrasi yang sudah selesai
- [ ] Pastikan section total skor (Raw + IRT) masih tampil normal
- [ ] Pastikan section BENAR/SALAH/KOSONG masih tampil normal
- [ ] Pastikan section baru "Detail Nilai per Mata Pelajaran" muncul di bawah stats
- [ ] Pastikan setiap mata pelajaran menampilkan: nama, skor raw, skor IRT, benar, salah, kosong
- [ ] Pastikan skor raw per mata pelajaran menampilkan "/ maks" (misal: 95.5 / 150)
- [ ] Pastikan skor IRT per mata pelajaran menampilkan "/ maks" (misal: 287 / 400)
- [ ] Pastikan section AI Analysis masih tampil normal
- [ ] Pastikan tombol "Kunci Jawaban" dan "Download Pembahasan" masih berfungsi

#### 2. Halaman Laporan (`/dashboard/review/{id}`)

- [ ] Buka halaman laporan untuk registrasi yang sudah selesai
- [ ] Pastikan section total skor mentah masih tampil normal
- [ ] Pastikan section baru "Detail Nilai per Mata Pelajaran" muncul setelah total skor dan sebelum AI Analysis
- [ ] Pastikan setiap mata pelajaran menampilkan data yang benar
- [ ] Pastikan section AI Analysis masih tampil normal
- [ ] Pastikan grafik statistik jawaban masih berfungsi
- [ ] Pastikan section "Pilih Rincian Pembahasan per Mata Pelajaran" masih tampil dan bisa diklik

#### 3. Edge Cases

- [ ] Buka halaman untuk registrasi yang belum memiliki `result` → harus redirect, bukan error
- [ ] Buka halaman untuk registrasi yang memiliki `result` tapi belum ada `categoryResults` → section detail nilai tidak muncul (bukan error)
- [ ] Pastikan responsive di mobile: card mata pelajaran harus stack vertical dengan baik

#### 4. Validasi Data

- [ ] Total skor raw keseluruhan ≈ jumlah skor raw per mata pelajaran
- [ ] Total skor IRT keseluruhan ≈ jumlah skor IRT per mata pelajaran
- [ ] Total benar/salah/kosong keseluruhan = jumlah benar/salah/kosong per mata pelajaran

---

## Perintah Cek yang Disarankan

### Verifikasi Syntax PHP

```bash
php -l app/Http/Controllers/Participant/DashboardController.php
```

### Jalankan Server Lokal

```bash
php artisan serve
```

Lalu buka di browser:

```text
http://127.0.0.1:8000/dashboard/result/{id}
http://127.0.0.1:8000/dashboard/review/{id}
```

Ganti `{id}` dengan ID registrasi yang valid.

### Clear View Cache

Jika tampilan tidak berubah setelah edit:

```bash
php artisan view:clear
```

---

## Referensi Cepat: Model dan Relasi

Ini adalah diagram relasi yang perlu dipahami:

```
ExamSessionParticipant
├── examSession → ExamSession
│   └── sessionCategories → ExamSessionCategory[] (punya max_score_raw, max_score_irt)
│       └── category → Category (punya name)
├── result → ExamResult (punya score, irt_score total)
│   └── categoryResults → ExamCategoryResult[] (punya score, irt_score per category)
│       └── category → Category (punya name)
├── questions → QuestionBank[]
└── userAnswers → UserAnswer[]
```

### Cara Akses Data di View

```php
// Total skor
$registration->result->score           // skor raw total
$registration->result->irt_score       // skor IRT total

// Detail per mata pelajaran
$registration->result->categoryResults // collection of ExamCategoryResult

// Tiap item dalam categoryResults:
$catResult->category->name             // nama mata pelajaran
$catResult->score                      // skor raw mata pelajaran ini
$catResult->irt_score                  // skor IRT mata pelajaran ini
$catResult->total_correct              // jumlah benar
$catResult->total_incorrect            // jumlah salah
$catResult->total_blank                // jumlah kosong

// Max skor per mata pelajaran (dari session config)
$registration->examSession->sessionCategories  // collection
// Cari yang category_id-nya cocok:
$sessionCat = $registration->examSession->sessionCategories
    ->where('category_id', $catResult->category_id)->first();
$sessionCat->max_score_raw             // max skor raw
$sessionCat->max_score_irt             // max skor IRT
```

---

## Acceptance Criteria

Fitur dianggap selesai jika semua poin berikut terpenuhi:

- [ ] Di halaman Hasil (`/dashboard/result/{id}`) ada section "Detail Nilai per Mata Pelajaran"
- [ ] Di halaman Laporan (`/dashboard/review/{id}`) ada section "Detail Nilai per Mata Pelajaran"
- [ ] Setiap mata pelajaran menampilkan: nama, skor raw (/ max), skor IRT (/ max), benar, salah, kosong
- [ ] Section muncul di bawah total skor dan di atas section lain (AI Analysis / tombol)
- [ ] Jika data `categoryResults` kosong, section tidak muncul (bukan error)
- [ ] Semua fitur yang sudah ada tetap berfungsi normal
- [ ] Layout responsive di mobile
- [ ] Tidak ada migration baru
- [ ] Tidak ada perubahan route
- [ ] Tidak ada error di production log

---

## Catatan untuk Junior Programmer atau AI Model

- Fokus pekerjaan hanya pada menambahkan section detail nilai. Jangan mengubah fitur lain.
- Data sudah tersedia di database. Tidak perlu membuat query baru atau logika perhitungan baru.
- Hanya perlu menambah eager loading di controller dan HTML di view.
- Jangan menghapus atau memodifikasi kode existing kecuali bagian eager loading.
- Gunakan style inline yang konsisten dengan style yang sudah ada di halaman tersebut.
- Gunakan icon FontAwesome (`fa-list-check`) yang sudah dipakai di project ini.
- Gunakan font `Outfit` untuk heading/angka sesuai design system yang ada.
- Pastikan class `glass`, `animate-fade-in`, dan CSS variable `--accent`, `--accent-rgb`, `--glass-border` sudah ada di project (sudah ada, jangan buat ulang).
- Jika ragu, lihat cara section AI Analysis atau section SCORE OVERVIEW menggunakan style — ikuti pola yang sama.

---

## Urutan Kerja Singkat

1. Baca dan pahami section "Pemahaman Struktur Data" di atas.
2. Update `showResult()` di controller — tambah eager loading.
3. Update `showReview()` di controller — tambah eager loading.
4. Tambahkan section HTML di `result.blade.php` — di bawah stats-grid.
5. Tambahkan section HTML di `review.blade.php` — di bawah SCORE OVERVIEW.
6. Clear view cache: `php artisan view:clear`.
7. Jalankan `php artisan serve`.
8. Buka kedua halaman di browser dan validasi semua skenario testing.
9. Pastikan tidak ada error di console browser atau laravel log.

---

## Estimasi Waktu

- Junior Programmer: 30–60 menit
- AI Model: 5–10 menit

Estimasi ini sudah termasuk waktu testing manual.
