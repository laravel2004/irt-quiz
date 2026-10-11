# Rencana Implementasi: Filter Mata Pelajaran pada Halaman Import Bank Soal

Dokumen ini disusun sebagai panduan teknis langkah-demi-langkah bagi **Junior Programmer** atau **Model AI (Cost-effective/Budget AI)** untuk mengimplementasikan fitur filter mata pelajaran pada halaman import bank soal ke modul Quiz.

---

## 1. Ringkasan Fitur & Spesifikasi

### Konteks & URL Target
- **URL Halaman:** `http://127.0.0.1:8000/admin/quizzes/import/bank-soal`
- **Route Name:** `admin.quizzes.import-view` (GET) & `admin.quizzes.import-process` (POST)
- **Controller:** `App\Http\Controllers\Admin\QuizController`
- **Blade View:** `resources/views/admin/quizzes/import.blade.php`

### Masalah Saat Ini
Saat ini, halaman import bank soal hanya memiliki input pencarian teks (`search`). Administrator belum bisa menyaring soal berdasarkan mata pelajaran tertentu, sehingga semua soal dari seluruh mata pelajaran bercampur menjadi satu.

### Kebutuhan yang Diinginkan
1. Terdapat dropdown filter **Mata Pelajaran** di bagian atas (sejajar dengan search bar).
2. Opsi default adalah **"Semua Mata Pelajaran"**.
3. Ketika pengguna memilih salah satu mata pelajaran (misal: "Matematika"), halaman otomatis me-refresh dan **hanya menampilkan soal-soal yang sesuai dengan mata pelajaran terpilih**.
4. Filter mata pelajaran dapat dikombinasikan dengan pencarian teks (`search`).
5. Filter tidak boleh hilang ketika admin berpindah ke halaman pagination berikutnya (page 2, 3, dst).
6. Terdapat tombol **Reset** yang muncul saat filter aktif untuk mengembalikan daftar soal ke kondisi awal.

---

## 2. Struktur Data & Relasi Terkait

Untuk pemahaman developer/AI:
- **Tabel `categories`** (Model: `App\Models\Category`): Menyimpan data Mata Pelajaran (kolom: `id`, `name`, `slug`). *Catatan: Di aplikasi ini, entitas "Mata Pelajaran" menggunakan tabel `categories`.*
- **Tabel `question_banks`** (Model: `App\Models\QuestionBank`): Menyimpan bank soal, memiliki foreign key `category_id` yang berelasi ke `categories.id` (`belongsTo(Category::class)`).

---

## 3. File yang Terlibat

| No | File | Jenis Perubahan | Deskripsi |
|---|---|---|---|
| 1 | `app/Http/Controllers/Admin/QuizController.php` | Backend Logic | Menambahkan import model `Category`, menangkap query parameter `category_id`, memfilter query soal, mengambil daftar mata pelajaran, dan mengirim data ke view dengan `withQueryString()`. |
| 2 | `resources/views/admin/quizzes/import.blade.php` | UI / Blade View | Menambahkan elemen `<select name="category_id">` dengan event `onchange="this.form.submit()"` serta memperbarui kondisi tombol reset. |

---

## 4. Langkah-demi-Langkah Implementasi

---

### Tahap 1: Modifikasi Backend Controller (`app/Http/Controllers/Admin/QuizController.php`)

#### 1.1. Import Model `Category`
Buka file `app/Http/Controllers/Admin/QuizController.php`.
Periksa bagian atas file (use statements). Model `Category` belum di-import.
Tambahkan:
```php
use App\Models\Category;
```

#### 1.2. Update Method `importBankSoalView(Request $request)`
Cari method `importBankSoalView` (sekitar baris 162).

**Kode Sebelum Perubahan:**
```php
    public function importBankSoalView(Request $request)
    {
        $search = $request->query('search');
        $query = QuestionBank::query();

        if ($search) {
            $query->where('question_text', 'like', "%{$search}%");
        }

        $questions = $query->paginate(20);
        return view('admin.quizzes.import', compact('questions', 'search'));
    }
```

**Kode Sesudah Perubahan (Ganti dengan kode ini):**
```php
    public function importBankSoalView(Request $request)
    {
        $search = $request->query('search');
        $categoryId = $request->query('category_id');

        // Eager load relasi category untuk menghindari masalah N+1 query
        $query = QuestionBank::with('category');

        // Filter berdasarkan pencarian teks soal jika diisi
        if ($search) {
            $query->where('question_text', 'like', "%{$search}%");
        }

        // Filter berdasarkan mata pelajaran (category_id) jika dipilih
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        // Urutkan soal terbaru dan pertahankan query string di pagination
        $questions = $query->orderBy('id', 'desc')->paginate(20)->withQueryString();

        // Ambil seluruh data mata pelajaran untuk dropdown filter
        $categories = Category::orderBy('name', 'asc')->get();

        return view('admin.quizzes.import', compact('questions', 'categories', 'search', 'categoryId'));
    }
```

> **Poin Kritis untuk Diperhatikan:**
> - `with('category')`: Sangat penting agar tidak terjadi N+1 database query saat menampilkan nama mata pelajaran di tabel.
> - `->withQueryString()`: Wajib ditambahkan pada `$query->paginate(20)` agar saat user mengklik halaman 2 atau 3 di pagination, filter `category_id` dan `search` tetap terbawa di URL.

---

### Tahap 2: Modifikasi Blade View (`resources/views/admin/quizzes/import.blade.php`)

Buka file `resources/views/admin/quizzes/import.blade.php`.
Cari blok form filter pencarian (sekitar baris 30 sampai 43).

**Kode Sebelum Perubahan:**
```html
    <form method="GET" action="{{ route('admin.quizzes.import-view') }}" style="display: flex; gap: 12px; align-items: center; margin-bottom: 24px; width: 100%; flex-wrap: wrap;">
        <div style="display: flex; gap: 12px; flex: 1; min-width: 300px;">
            <div style="position: relative; flex: 1;">
                <i class="fas fa-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                <input type="text" name="search" id="searchInput" class="form-input" placeholder="Cari soal (tekan enter)..." value="{{ $search ?? '' }}" style="width: 100%; padding-left: 44px; margin-bottom: 0;">
            </div>
        </div>

        @if(isset($search) && $search)
            <a href="{{ route('admin.quizzes.import-view') }}" class="btn-primary" style="background: #ef4444; white-space: nowrap; flex-shrink: 0; text-decoration: none;">
                <i class="fas fa-times"></i> Reset
            </a>
        @endif
    </form>
```

**Kode Sesudah Perubahan (Ganti dengan kode ini):**
```html
    <form method="GET" action="{{ route('admin.quizzes.import-view') }}" style="display: flex; gap: 12px; align-items: center; margin-bottom: 24px; width: 100%; flex-wrap: wrap;">
        <div style="display: flex; gap: 12px; flex: 1; min-width: 300px; flex-wrap: wrap;">
            <!-- Dropdown Filter Mata Pelajaran -->
            <select name="category_id" class="form-input" onchange="this.form.submit()" style="flex: 1; margin-bottom: 0; color: #000; min-width: 180px;">
                <option value="">Semua Mata Pelajaran</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ (string)($categoryId ?? '') === (string)$cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>

            <!-- Input Pencarian Soal -->
            <div style="position: relative; flex: 2; min-width: 200px;">
                <i class="fas fa-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                <input type="text" name="search" id="searchInput" class="form-input" placeholder="Cari soal (tekan enter)..." value="{{ $search ?? '' }}" style="width: 100%; padding-left: 44px; margin-bottom: 0;">
            </div>
        </div>

        <!-- Tombol Reset Filter (Tampil jika search atau category_id terisi) -->
        @if(($search ?? null) || ($categoryId ?? null))
            <a href="{{ route('admin.quizzes.import-view') }}" class="btn-primary" style="background: #ef4444; white-space: nowrap; flex-shrink: 0; text-decoration: none;">
                <i class="fas fa-times"></i> Reset
            </a>
        @endif
    </form>
```

> **Poin Kritis untuk Diperhatikan:**
> - `onchange="this.form.submit()"`: Memungkinkan form langsung disubmit secara otomatis ketika pengguna memilih salah satu opsi dari dropdown mata pelajaran tanpa perlu menekan tombol submit tambahan.
> - `(string)($categoryId ?? '') === (string)$cat->id`: Casting ke string memastikan perbandingan nilai ID aman dan akurat antara integer dan string dari parameter URL.
> - **Jangan menyentuh form import di bawahnya!** Halaman ini memiliki form kedua yaitu `<form action="{{ route('admin.quizzes.import-process') }}" method="POST">`. Pastikan kedua form tetap terpisah dan tidak bersarang (*nested forms*).

---

## 5. Jebakan yang Sering Terjadi (Common Pitfalls)

1. **Lupa Menambahkan `use App\Models\Category;` di Controller:**
   Jika terlewat, PHP akan memunculkan error `Class "App\Http\Controllers\Admin\Category" not found`.
2. **Lupa `withQueryString()` pada Pagination:**
   Jika hanya memanggil `$query->paginate(20)`, saat user memilih mata pelajaran "IPA" lalu mengklik halaman 2 pada pagination, parameter `?category_id=...` akan hilang dan tabel kembali menampilkan semua mata pelajaran.
3. **Menggabungkan Form Filter dan Form Submit Import:**
   Form filter menggunakan method `GET` ke route `admin.quizzes.import-view`, sedangkan form import menggunakan method `POST` ke route `admin.quizzes.import-process`. Jangan sampai tag form saling tumpang tindih.
4. **N+1 Problem:**
   Pastikan menggunakan `QuestionBank::with('category')` agar Laravel tidak melakukan query select category berulang-ulang untuk setiap baris soal.

---

## 6. Skenario Pengujian (Testing & Verification Checklist)

Setelah implementasi selesai, jalankan checklist pengujian berikut:

- [ ] **Test 1: Akses Halaman Awal**
  - Akses `http://127.0.0.1:8000/admin/quizzes/import/bank-soal` di browser.
  - Verifikasi dropdown filter "Mata Pelajaran" muncul di samping search bar.
  - Verifikasi opsi default bertuliskan "Semua Mata Pelajaran".
  - Verifikasi seluruh soal dari berbagai mata pelajaran tampil.
  - Verifikasi tombol "Reset" tidak muncul di awal.

- [ ] **Test 2: Filter Berdasarkan Mata Pelajaran**
  - Pilih salah satu mata pelajaran dari dropdown (misal: "Matematika").
  - Verifikasi halaman otomatis reload.
  - Verifikasi nilai dropdown tetap terpilih pada mata pelajaran tersebut.
  - Verifikasi URL berubah menjadi `.../bank-soal?category_id=X`.
  - Verifikasi badge mata pelajaran pada semua baris di tabel hanya menampilkan mata pelajaran yang dipilih.
  - Verifikasi tombol "Reset" berwarna merah muncul.

- [ ] **Test 3: Kombinasi Filter Mata Pelajaran + Pencarian (Search)**
  - Dengan filter mata pelajaran masih terpilih, ketik kata kunci pada kotak search lalu tekan Enter.
  - Verifikasi URL menjadi `.../bank-soal?category_id=X&search=kata_kunci`.
  - Verifikasi hasil yang tampil hanya soal yang memenuhi KEDUA kriteria (mata pelajaran sesuai DAN teks soal mengandung kata kunci).

- [ ] **Test 4: Navigasi Pagination**
  - Pilih mata pelajaran yang memiliki lebih dari 20 soal.
  - Klik halaman 2 pada nomor pagination di bawah tabel.
  - Verifikasi URL menjadi `.../bank-soal?category_id=X&page=2`.
  - Verifikasi soal yang tampil di halaman 2 tetap terfilter sesuai mata pelajaran yang dipilih.

- [ ] **Test 5: Tombol Reset**
  - Klik tombol "Reset".
  - Verifikasi URL kembali bersih tanpa parameter query (`/bank-soal`).
  - Verifikasi dropdown kembali ke "Semua Mata Pelajaran" dan kotak search kosong.
  - Verifikasi semua soal dari semua mata pelajaran tampil kembali.

- [ ] **Test 6: Proses Import Tetap Berfungsi Normal**
  - Centang beberapa soal pada hasil yang sudah terfilter.
  - Pilih Jenjang Tujuan (misal: "SMA").
  - Klik tombol **"Import Soal Terpilih"**.
  - Verifikasi proses import berhasil dan di-redirect ke halaman daftar kuis dengan pesan sukses.

---

## 7. Definition of Done (DoD)

Pekerjaan dianggap selesai jika:
1. Tidak ada syntax error atau exception 500 saat mengakses halaman atau memfilter.
2. Dropdown filter menampilkan seluruh mata pelajaran dari tabel `categories`.
3. Filter berfungsi menyaring soal hanya untuk mata pelajaran yang dipilih.
4. Pagination dan search tetap sinkron bersama filter mata pelajaran.
5. Fitur import soal tetap berjalan dengan sukses tanpa regresi.
