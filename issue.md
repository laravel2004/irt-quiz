# Issue: Pembatasan Jumlah Peserta yang Sedang Mengerjakan Ujian (Concurrent User Limit)

## Deskripsi Fitur

Fitur ini terdiri dari **dua bagian**:
1. **Pembatasan concurrent user** — membatasi jumlah peserta yang sedang aktif mengerjakan ujian pada waktu bersamaan (global, bukan per-sesi). Jika slot penuh, peserta baru akan mendapat notifikasi untuk menunggu.
2. **Tampilan di dashboard admin** — menampilkan jumlah peserta yang sedang aktif mengerjakan ujian secara real-time di halaman dashboard admin.

---

## Analisis Sistem yang Ada

### Alur Ujian Saat Ini (Penting Dipahami)

```
1. Peserta klik "Mulai Ujian" di dashboard
   → GET /exam/{code}/terms   [ExamController::terms]
   → Peserta setujui terms
   → POST /exam/{code}/agree  [ExamController::agreeTerms]

2. agreeTerms → set started_at di tabel exam_session_participants
   → redirect ke /exam/{code}/categories

3. Peserta kerjakan tiap mapel
   → GET/POST /exam/{code}/category/{id}

4. Selesai semua mapel → POST /exam/{code}/finish  [ExamController::finishSession]
   → set finished_at di tabel exam_session_participants
```

### Definisi "Sedang Mengerjakan Ujian"

Peserta dianggap **aktif** apabila pada tabel `exam_session_participants`:
- `started_at` IS NOT NULL (sudah mulai)
- `finished_at` IS NULL (belum selesai)
- Sesi ujian masih aktif (`is_active = true`) dan belum melewati `end_date + end_time`

---

## Rencana Implementasi

### Tahap 1: Konfigurasi Environment Variable

**File yang diubah:** `.env` dan `config/exam.php` (file baru)

**Langkah:**

1. Tambahkan variable baru di file `.env`:
   ```
   EXAM_CONCURRENT_LIMIT=30
   ```

2. Buat file baru `config/exam.php`:
   ```php
   <?php
   return [
       'concurrent_limit' => env('EXAM_CONCURRENT_LIMIT', 30),
   ];
   ```
   Cara akses di kode: `config('exam.concurrent_limit')`

---

### Tahap 2: Buat Helper Method untuk Cek Jumlah Peserta Aktif

**File:** `app/Http/Controllers/ExamController.php`

Tambahkan method private berikut di dalam class `ExamController` (boleh taruh setelah method `getParticipant`):

```php
private function countActiveParticipants(): int
{
    return \App\Models\ExamSessionParticipant::whereNotNull('started_at')
        ->whereNull('finished_at')
        ->whereHas('examSession', function ($q) {
            // Hanya hitung peserta di sesi yang masih aktif dan belum berakhir
            $q->where('is_active', true)
              ->whereRaw("CONCAT(end_date, ' ', end_time) > ?", [now()]);
        })
        ->count();
}
```

> Query ini menghitung semua peserta dari **semua sesi** yang sudah started tapi belum finished, dan sesinya masih aktif serta belum melewati batas waktu.

---

### Tahap 3: Tambahkan Pengecekan Limit di `agreeTerms`

**File:** `app/Http/Controllers/ExamController.php`
**Method:** `agreeTerms` (sekitar line 142)

Tambahkan pengecekan **setelah** `$participant = $this->getParticipant($code)` dan **sebelum** baris generate questions / set `started_at`:

```php
public function agreeTerms(Request $request, $code)
{
    $request->validate([
        'agree_terms' => 'accepted',
    ], [
        'agree_terms.accepted' => 'Anda harus menyetujui syarat dan ketentuan sebelum memulai ujian.',
    ]);

    $participant = $this->getParticipant($code);
    if (!$participant) return redirect()->route('participant.dashboard');

    $session = $participant->examSession;

    // === TAMBAHKAN BLOK INI ===
    // Cek limit hanya untuk peserta yang BELUM pernah started
    // (Peserta yang lanjut dari sesi sebelumnya tidak kena limit)
    if (!$participant->started_at) {
        $limit = config('exam.concurrent_limit', 30);
        $activeCount = $this->countActiveParticipants();

        if ($activeCount >= $limit) {
            return back()->with('exam_full', true);
        }
    }
    // === AKHIR BLOK TAMBAHAN ===

    // Generate questions if not exist
    if ($session->questions()->count() == 0) {
        $this->sessionService->generateSessionQuestions($session->id);
    }

    if ($participant->questions()->count() == 0) {
        $this->generateParticipantQuestions($participant);
    }

    // Mark started if not already
    if (!$participant->started_at) {
        $participant->update(['started_at' => now()]);
    }

    return redirect()->route('exam.categories', $session->code);
}
```

> CATATAN: Pengecekan `!$participant->started_at` memastikan peserta yang sudah pernah mulai (lanjut mengerjakan) tidak terkena blokir limit meski slot penuh.

---

### Tahap 4: Tampilkan Modal "Ujian Penuh" di Halaman Terms

**File:** `resources/views/exam/terms.blade.php`

Tambahkan kode berikut di dalam `@section('content')` atau `@push('scripts')`:

**A. Tambahkan modal HTML:**

```html
{{-- Modal Ujian Penuh --}}
@if(session('exam_full'))
<div id="examFullModal" style="
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.6);
    display: flex; align-items: center; justify-content: center;
    z-index: 9999;
">
    <div style="
        background: white; border-radius: 16px;
        padding: 40px; max-width: 440px; width: 90%;
        text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    ">
        <div style="font-size: 3rem; margin-bottom: 16px;">⏳</div>
        <h3 style="margin-bottom: 12px; font-size: 1.4rem; color: #1e293b;">Sesi Ujian Penuh</h3>
        <p style="color: #64748b; line-height: 1.6; margin-bottom: 28px;">
            Saat ini terlalu banyak peserta yang sedang mengerjakan ujian secara bersamaan.
            Mohon tunggu hingga ada peserta yang menyelesaikan ujian, lalu coba lagi.
        </p>
        <button
            id="btnRetry"
            onclick="document.getElementById('agreeForm').submit()"
            style="
                background: #3b82f6; color: white;
                border: none; border-radius: 10px;
                padding: 12px 32px; font-size: 1rem;
                cursor: pointer; font-weight: 600; margin-right: 8px;
            "
        >
            Coba Lagi
        </button>
    </div>
</div>
@endif
```

> Catatan: Ganti `agreeForm` dengan id yang sesuai di form terms-nya.

**B. Tambahkan script auto-polling (di `@push('scripts')`):**

```html
@if(session('exam_full'))
<script>
    // Polling setiap 15 detik untuk cek apakah slot sudah tersedia
    let pollingInterval = setInterval(function() {
        fetch('/exam/check-capacity')
            .then(res => res.json())
            .then(data => {
                if (!data.is_full) {
                    clearInterval(pollingInterval);
                    // Update teks modal jika slot tersedia
                    document.querySelector('#examFullModal p').textContent =
                        'Slot sudah tersedia! Silakan klik Coba Lagi untuk mulai ujian.';
                }
            })
            .catch(() => {}); // abaikan error network
    }, 15000);
</script>
@endif
```

---

### Tahap 5: Tambah API Endpoint untuk Polling Kapasitas

**File:** `routes/web.php`

Tambahkan route baru di dalam grup `middleware(['auth'])`:

```php
Route::get('/exam/check-capacity', [\App\Http\Controllers\ExamController::class, 'checkCapacity'])
    ->name('exam.check-capacity');
```

**File:** `app/Http/Controllers/ExamController.php`

Tambahkan method public baru:

```php
public function checkCapacity()
{
    $limit = config('exam.concurrent_limit', 30);
    $activeCount = $this->countActiveParticipants();

    return response()->json([
        'is_full'      => $activeCount >= $limit,
        'active_count' => $activeCount,
        'limit'        => $limit,
    ]);
}
```

---

## Fitur Tambahan: Tampilkan Jumlah Peserta Aktif di Dashboard Admin

### Konteks

Admin perlu bisa memantau berapa peserta yang **sedang mengerjakan ujian saat ini** langsung dari halaman dashboard (`/admin`). Ini berguna agar admin tahu kapan mendekati limit dan dapat mengambil tindakan jika perlu.

Dashboard admin saat ini ada di:
- **Controller:** `app/Http/Controllers/Admin/DashboardController.php` — method `index()`
- **View:** `resources/views/admin/dashboard.blade.php`

### Perubahan yang Diperlukan

**A. Edit `app/Http/Controllers/Admin/DashboardController.php`**

Tambahkan variabel `$activeExamCount` dan `$examLimit` di method `index()`:

```php
public function index()
{
    $totalParticipants = \App\Models\User::whereIn('role', ['basic', 'premium'])->count();
    $totalQuestions = QuestionBank::count();
    $totalCategories = Category::count();
    $activeSessionsCount = ExamSession::where('is_active', true)->count();
    $avgScore = ExamResult::avg('irt_score') ?? 0;

    // === TAMBAHKAN INI ===
    // Hitung peserta yang sedang aktif mengerjakan ujian (global)
    $activeExamCount = ExamSessionParticipant::whereNotNull('started_at')
        ->whereNull('finished_at')
        ->whereHas('examSession', function ($q) {
            $q->where('is_active', true)
              ->whereRaw("CONCAT(end_date, ' ', end_time) > ?", [now()]);
        })
        ->count();
    $examLimit = config('exam.concurrent_limit', 30);
    // === AKHIR TAMBAHAN ===

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
        'recentSessions',
        'activeExamCount',  // tambahkan ini
        'examLimit'         // tambahkan ini
    ));
}
```

**B. Edit `resources/views/admin/dashboard.blade.php`**

Tambahkan kartu statistik baru di antara kartu-kartu yang sudah ada (cari bagian stats/card grid):

```html
<div class="stat-card" style="border-left: 4px solid {{ $activeExamCount >= $examLimit ? '#ef4444' : '#22c55e' }};">
    <div class="stat-icon" style="background: {{ $activeExamCount >= $examLimit ? 'rgba(239,68,68,0.1)' : 'rgba(34,197,94,0.1)' }};">
        <i class="fas fa-users" style="color: {{ $activeExamCount >= $examLimit ? '#ef4444' : '#22c55e' }};"></i>
    </div>
    <div class="stat-info">
        <div class="stat-number">{{ $activeExamCount }} / {{ $examLimit }}</div>
        <div class="stat-label">Sedang Mengerjakan Ujian</div>
    </div>
    @if($activeExamCount >= $examLimit)
    <div style="margin-top: 8px; font-size: 0.75rem; color: #ef4444; font-weight: 600;">
        ⚠️ Slot penuh — peserta baru tidak bisa masuk
    </div>
    @endif
</div>
```

> Kartu ini akan berwarna **merah** jika slot penuh, dan **hijau** jika masih ada kapasitas. Angka tampil sebagai `X / LIMIT` (contoh: `28 / 30`).

---

## Urutan Pengerjaan (Checklist)

**Bagian A — Concurrent Limit (Fitur Utama)**
- [ ] **Step 1** — Tambah `EXAM_CONCURRENT_LIMIT=30` di `.env`
- [ ] **Step 2** — Buat file `config/exam.php` dengan key `concurrent_limit`
- [ ] **Step 3** — Tambah method private `countActiveParticipants()` di `ExamController`
- [ ] **Step 4** — Edit method `agreeTerms()` di `ExamController`, tambah blok pengecekan limit
- [ ] **Step 5** — Edit view `resources/views/exam/terms.blade.php`, tambah modal HTML
- [ ] **Step 6** — Tambah route `GET /exam/check-capacity` di `routes/web.php`
- [ ] **Step 7** — Tambah method public `checkCapacity()` di `ExamController`
- [ ] **Step 8** — Tambah script polling di view `terms.blade.php`
- [ ] **Step 9** — Test: Set limit ke `0` di `.env`, coba mulai ujian, pastikan modal muncul
- [ ] **Step 10** — Test: Set limit ke `999`, pastikan bisa masuk ujian normal

**Bagian B — Tampilan di Dashboard Admin**
- [ ] **Step 11** — Edit `DashboardController::index()`, tambah variabel `$activeExamCount` dan `$examLimit`
- [ ] **Step 12** — Edit `resources/views/admin/dashboard.blade.php`, tambah kartu statistik baru
- [ ] **Step 13** — Test: Pastikan kartu tampil hijau saat slot tersedia, merah saat penuh

---

## File yang Perlu Diubah

| File | Jenis | Bagian |
|---|---|---|
| `.env` | Tambah env variable | A |
| `config/exam.php` | **[BARU]** Buat file config | A |
| `app/Http/Controllers/ExamController.php` | Tambah 2 method + edit `agreeTerms` | A |
| `resources/views/exam/terms.blade.php` | Tambah modal HTML + script | A |
| `routes/web.php` | Tambah 1 route | A |
| `app/Http/Controllers/Admin/DashboardController.php` | Tambah 2 variabel di `index()` | B |
| `resources/views/admin/dashboard.blade.php` | Tambah kartu statistik aktif | B |

---

## Catatan Penting untuk Implementor

1. **Pembatasan bersifat GLOBAL** — semua sesi yang aktif dihitung bersama, bukan per-sesi.
2. **Peserta yang lanjut (resume)** tidak terkena limit karena pengecekan hanya berlaku jika `started_at` masih null.
3. **Batas waktu sesi** ikut diperhitungkan — peserta di sesi yang sudah lewat `end_time` tidak ikut dihitung aktif.
4. Nilai default limit di `config/exam.php` adalah `30`. Ubah lewat `.env` tanpa perlu deploy ulang.
5. Pastikan nama `id` form di `terms.blade.php` sesuai saat mengganti `agreeForm` di script Coba Lagi.
