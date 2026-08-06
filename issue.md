# Issue: Implementasi Redis Caching untuk Optimasi Performa

## Deskripsi
Fitur ini bertujuan untuk mengoptimalkan performa aplikasi (mencegah *loading* lama dan server *down* saat *traffic* tinggi) dengan mengimplementasikan Redis caching pada dua area utama:
1. Pengambilan data soal (*get soal*) saat peserta memulai ujian di halaman pengerjaan soal.
2. Pengambilan data riwayat sesi dan hasil ujian di halaman dashboard peserta (`http://localhost/dashboard`).

## Target Pekerja
Dokumen ini disusun secara detail agar mudah diimplementasikan oleh **Junior Programmer** atau **AI Coder model ringan/murah**.

---

## Tahapan Implementasi Detail

### Bagian 1: Caching Pengambilan Soal Ujian (ExamController)
Saat ujian dimulai, mengambil data dari tabel soal cukup berat jika pesertanya ribuan. Kita akan *cache* daftar soal per peserta per mata pelajaran.

**File Target:** `app/Http/Controllers/ExamController.php`
**Fungsi Target:** `main($code, $categoryId)`

**Langkah-langkah Eksekusi:**
1. **Import Facade Cache:**
   Tambahkan kode ini di bagian atas (bawah `use Illuminate\Http\Request;`):
   ```php
   use Illuminate\Support\Facades\Cache;
   ```

2. **Ubah Query Pengambilan Soal:**
   Cari blok kode pengambilan soal ini (biasanya di bawah pengecekan `$status->finished_at`):
   ```php
   $questions = $participant->questions()
       ->where('category_id', $sessionCategory->category_id)
       ->with('category')
       ->get();
   ```

3. **Bungkus dengan fungsi Cache::remember:**
   Ganti blok kode di atas dengan kode berikut:
   ```php
   $cacheKey = "exam_questions_participant_{$participant->id}_category_{$sessionCategory->category_id}";
   
   // Cache akan disimpan selama durasi ujian mata pelajaran tersebut
   $questions = Cache::remember($cacheKey, now()->addMinutes((int) $sessionCategory->duration), function () use ($participant, $sessionCategory) {
       return $participant->questions()
           ->where('category_id', $sessionCategory->category_id)
           ->with('category')
           ->get();
   });
   ```

### Bagian 2: Caching Halaman Dashboard Peserta
Dashboard meload banyak relasi (`examSession`, `categories`, `result`), sehingga sangat butuh Redis.

**File Target:** `app/Http/Controllers/Participant/DashboardController.php`

**Langkah-langkah Eksekusi:**
1. **Import Facade Cache:**
   Tambahkan kode ini di bagian atas file:
   ```php
   use Illuminate\Support\Facades\Cache;
   ```

2. **Ubah Fungsi `index()`:**
   Cari query `$registrations` berikut:
   ```php
   $registrations = ExamSessionParticipant::where('user_id', $user->id)
       ->with([
           'examSession.sessionCategories.category',
           'examSession.sessionCategories.subCategories.subCategory',
           'result'
       ])
       ->orderBy('created_at', 'asc')
       ->get();
   ```

3. **Bungkus dengan fungsi Cache::remember:**
   Ubah menjadi:
   ```php
   $cacheKey = "dashboard_registrations_user_{$user->id}";
   
   // Cache data dashboard selama 60 menit
   $registrations = Cache::remember($cacheKey, now()->addMinutes(60), function () use ($user) {
       return ExamSessionParticipant::where('user_id', $user->id)
           ->with([
               'examSession.sessionCategories.category',
               'examSession.sessionCategories.subCategories.subCategory',
               'result'
           ])
           ->orderBy('created_at', 'asc')
           ->get();
   });
   ```

### Bagian 3: Clear Cache (Invalidasi Cache)
*Penting!* Jika peserta baru saja daftar sesi, atau baru saja menyelesaikan ujian, cache dashboard mereka **wajib dihapus** agar data terbaru langsung muncul.

**Langkah-langkah Eksekusi:**
1. **Di `DashboardController@joinSession`:**
   Tepat sebelum baris `return redirect()->route('exam.main',...);` tambahkan:
   ```php
   \Illuminate\Support\Facades\Cache::forget("dashboard_registrations_user_" . auth()->id());
   ```

2. **Di `DashboardController@retakeSession`:**
   Tepat sebelum baris `return redirect()->route('exam.terms',...);` tambahkan:
   ```php
   \Illuminate\Support\Facades\Cache::forget("dashboard_registrations_user_" . auth()->id());
   ```

3. **Di `ExamController@finishSession`:**
   Tepat sebelum baris `return response()->json(['status' => 'success', ...]);` (di paling bawah fungsi finishSession), tambahkan:
   ```php
   \Illuminate\Support\Facades\Cache::forget("dashboard_registrations_user_{$participant->user_id}");
   ```

---

## Kriteria Penerimaan Pekerjaan (Acceptance Criteria)
- [ ] Programmer sudah memastikan `.env` menggunakan `CACHE_STORE=redis`.
- [ ] Membuka halaman soal ujian bisa memuat instan (melalui Redis Cache).
- [ ] Membuka halaman dashboard jauh lebih cepat.
- [ ] Saat mencoba "Akhiri Ujian" (Selesai), halaman dashboard langsung menampilkan *score* (tidak memberikan data usang akibat nyangkut di cache).
