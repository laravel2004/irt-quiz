# Task: Pemisahan Fitur "Preview Soal" dan "Preview Pembahasan"

## Deskripsi Singkat
Saat ini pada halaman detail sesi ujian (URL: `/admin/sessions/{id}`), terdapat satu tombol aksi bernama "Preview Soal" yang mengarahkan ke halaman berisi soal, indikator jawaban benar/salah, dan pembahasan. 
Tugas ini adalah mengubah tombol tersebut menjadi "Preview Pembahasan", dan membuat tombol "Preview Soal" baru yang halamannya hanya menampilkan soal murni (tanpa jawaban yang benar dan tanpa pembahasan).

## Langkah-langkah Implementasi

Lakukan langkah-langkah di bawah ini secara berurutan:

### 1. Menambahkan Route Baru
Buka file `routes/web.php`.
Cari baris route untuk preview-questions:
```php
Route::get('/admin/sessions/{id}/preview-questions', [\App\Http\Controllers\Admin\ExamSessionController::class, 'previewQuestions'])->name('admin.sessions.preview-questions');
```
Tambahkan route baru di bawahnya untuk halaman yang murni soal:
```php
Route::get('/admin/sessions/{id}/preview-questions-only', [\App\Http\Controllers\Admin\ExamSessionController::class, 'previewQuestionsOnly'])->name('admin.sessions.preview-questions-only');
```

### 2. Mengupdate Controller
Buka file `app/Http/Controllers/Admin/ExamSessionController.php`.
Cari method `previewQuestions`. Buat duplikat dari method tersebut di bawahnya persis dengan nama `previewQuestionsOnly`:
```php
public function previewQuestionsOnly(Request $request, $id)
{
    $session = ExamSession::with(['sessionCategories.category', 'questions.category'])->findOrFail($id);
    
    if ($request->boolean('regenerate') || $session->questions()->count() == 0) {
        $this->sessionService->generateSessionQuestions($id);
        $session->load('questions.category');
    }

    // Perhatikan perbedaan pada nama view yang direturn
    return view('admin.sessions.preview-only', compact('session'));
}
```

### 3. Modifikasi Tampilan Halaman Detail Sesi
Buka file `resources/views/admin/sessions/show.blade.php`.
Cari tombol "Preview Soal" (biasanya di bagian atas atau di dalam div khusus actions). Kode aslinya kurang lebih seperti ini:
```html
<a href="{{ route('admin.sessions.preview-questions', $session->id) }}" class="btn-primary" style="...">
    <i class="fas fa-eye"></i> Preview Soal
</a>
```
Ubah teks "Preview Soal" pada tombol tersebut menjadi "Preview Pembahasan" (ubah juga icon-nya menjadi `fa-book-open`).
Lalu, buat satu tombol tambahan tepat di sebelahnya untuk "Preview Soal" murni, mengarah ke route yang baru. Contoh hasilnya:
```html
<a href="{{ route('admin.sessions.preview-questions', $session->id) }}" class="btn-primary" style="height: 32px; font-size: 0.75rem; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; background: rgba(var(--accent-rgb), 0.1); color: var(--accent); border: 1px solid var(--accent);">
    <i class="fas fa-book-open"></i> Preview Pembahasan
</a>
<a href="{{ route('admin.sessions.preview-questions-only', $session->id) }}" class="btn-primary" style="height: 32px; font-size: 0.75rem; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; background: rgba(var(--accent-rgb), 0.1); color: var(--accent); border: 1px solid var(--accent);">
    <i class="fas fa-eye"></i> Preview Soal
</a>
```

### 4. Membuat View Khusus "Preview Soal" (Tanpa Jawaban & Pembahasan)
Duplikat file `resources/views/admin/sessions/preview.blade.php` dan simpan dengan nama `resources/views/admin/sessions/preview-only.blade.php`.
Buka file `preview-only.blade.php` yang baru saja dibuat, lalu lakukan pembersihan kode agar **hanya menampilkan soal dan pilihan ganda yang netral**:

- Hapus seluruh blok kode pembahasan soal yang ada di bagian bawah (Cari blok `@if($question->explanation) ... @endif`).
- Hapus warna latar hijau/merah dan icon check/silang yang menandakan jawaban benar/salah pada pilihan ganda. 
- Di dalam pengecekan `@if($question->type === 'multiple_benar_salah')`:
  - Ubah bagian yang me-render icon centang atau strip (kode yang mengecek `$isBenar`) menjadi statis saja (tanpa menandai mana yang benar).
- Di dalam blok `@else` (tipe multiple choice):
  - Ubah class CSS / style background yang bergantung pada `$isCorrect` menjadi warna netral untuk semua kondisi (misal `background: rgba(255,255,255,0.03)`).
  - Ubah styling border `border: 1px solid {{ $isCorrect ? ... }}` menjadi `border: 1px solid rgba(255,255,255,0.05)`.
  - Hapus tag icon `<i class="fas fa-check-circle" style="margin-left: 8px;"></i>` dan icon checkbox tercentang yang menandai jawaban benar.
  - Pastikan warna teks opsinya seragam dan tidak ada yang berwarna hijau (`#10b981`).
- Ubah Header Title `@section('title', ...)` dan tulisan judul di dalam content agar lebih merepresentasikan bahwa ini adalah "Preview Soal" murni.

### Kriteria Selesai (Acceptance Criteria)
1. Di halaman `/admin/sessions/{id}` terdapat dua tombol aksi: "Preview Pembahasan" dan "Preview Soal".
2. Menekan tombol "Preview Pembahasan" memunculkan soal lengkap dengan tanda mana yang benar dan kotak pembahasan (sama seperti perilaku sistem sebelumnya).
3. Menekan tombol "Preview Soal" menampilkan layout yang persis namun bersih dari semua indikasi kunci jawaban maupun kotak pembahasan, benar-benar seperti tampilan saat peserta akan mengerjakan soal.
