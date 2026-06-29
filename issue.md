# Planning Implementasi Filter Mata Pelajaran di Menu Bank Soal

## Ringkasan Kebutuhan

Pada halaman admin Bank Soal `http://127.0.0.1:8000/admin/questions`, perlu ditambahkan fitur filter berdasarkan mata pelajaran.

Saat ini halaman Bank Soal sudah menampilkan kolom `MATA PELAJARAN`, tetapi belum ada filter khusus untuk memilih mata pelajaran tertentu. Fitur yang perlu dibuat adalah dropdown filter agar admin bisa melihat daftar soal berdasarkan mata pelajaran yang dipilih.

Dokumen ini berisi tahapan implementasi detail agar bisa dikerjakan oleh junior programmer atau AI model yang lebih murah secara aman dan bertahap.

## Tujuan Implementasi

- Menambahkan filter `Mata Pelajaran` pada halaman Bank Soal admin.
- Filter mengambil data dari tabel/model `Category` yang sudah digunakan sebagai mata pelajaran.
- Saat admin memilih mata pelajaran, tabel hanya menampilkan soal dari mata pelajaran tersebut.
- Filter harus tetap kompatibel dengan pagination.
- Filter harus mempertahankan nilai yang dipilih setelah halaman reload atau pindah pagination.
- Tidak mengubah fitur tambah, edit, preview, hapus, dan search existing kecuali memang diperlukan untuk integrasi filter.

## Lokasi Halaman

Halaman target:

```text
/admin/questions
```

Route terkait:

```php
Route::resource('/admin/questions', \App\Http\Controllers\Admin\QuestionBankController::class)->names('admin.questions');
```

File utama yang perlu diperhatikan:

- `routes/web.php`
- `app/Http/Controllers/Admin/QuestionBankController.php`
- `app/Services/QuestionBankService.php`
- `app/Repositories/QuestionBankRepository.php`
- `app/Models/QuestionBank.php`
- `app/Models/Category.php`
- `resources/views/admin/questions/index.blade.php`

## Kondisi Kode Saat Ini

### Controller

Di `QuestionBankController@index`, data soal saat ini diambil dengan:

```php
$questions = $this->questionService->getPaginated(10);
$categories = Category::all();
```

Kemudian dikirim ke view:

```php
return view('admin.questions.index', compact('questions', 'categories'));
```

Artinya:

- Data kategori/mata pelajaran sudah tersedia di view sebagai `$categories`.
- Namun query soal belum membaca parameter filter dari request.

### Repository

Di `QuestionBankRepository`, method `paginate()` saat ini mengambil semua soal:

```php
return $this->model->with(['category', 'subCategory'])->latest()->paginate($perPage);
```

Artinya:

- Query sudah eager load relasi `category` dan `subCategory`.
- Belum ada kondisi `where('category_id', ...)`.

### View

Di `resources/views/admin/questions/index.blade.php`, sudah ada area search dan tombol tambah soal. Filter mata pelajaran paling cocok ditambahkan di area header ini, dekat input pencarian.

Kolom tabel sudah menampilkan mata pelajaran lewat:

```php
{{ $question->category->name }}
```

Artinya data relasi mata pelajaran sudah dipakai dan tidak perlu dibuat dari nol.

## Definisi Fitur yang Diinginkan

Tambahkan dropdown filter dengan pilihan:

- `Semua Mata Pelajaran`
- daftar mata pelajaran dari `$categories`

Contoh parameter URL:

```text
/admin/questions?category_id=3
```

Jika memilih `Semua Mata Pelajaran`, URL kembali ke:

```text
/admin/questions
```

Jika user sedang di halaman pagination, link pagination harus tetap membawa filter:

```text
/admin/questions?category_id=3&page=2
```

## Tahapan Implementasi

### 1. Pahami Relasi Data

Sebelum coding, pastikan struktur data berikut sudah benar:

- `QuestionBank` memiliki kolom `category_id`.
- `QuestionBank` memiliki relasi `category()` ke model `Category`.
- `Category` memiliki field nama, kemungkinan `name`.
- View Bank Soal sudah menerima `$categories` dari controller.

Checklist tahap ini:

- Buka `app/Models/QuestionBank.php`.
- Pastikan ada relasi `category()`.
- Buka `app/Models/Category.php`.
- Pastikan nama mata pelajaran bisa ditampilkan dengan `$category->name`.
- Jangan membuat tabel atau migration baru karena data mata pelajaran sudah ada.

### 2. Tentukan Cara Filter

Gunakan server-side filter, bukan hanya JavaScript client-side.

Alasannya:

- Data Bank Soal memakai pagination.
- Jika filter hanya dilakukan di JavaScript, yang terfilter hanya data pada halaman pagination saat ini.
- Server-side filter lebih benar karena query database langsung dibatasi berdasarkan `category_id`.

Format request yang disarankan:

```text
GET /admin/questions?category_id=ID_KATEGORI
```

Contoh:

```text
GET /admin/questions?category_id=2
```

### 3. Update Repository agar Mendukung Filter

File yang perlu diubah:

```text
app/Repositories/QuestionBankRepository.php
```

Saat ini method `paginate()` hanya menerima `$perPage`.

Ubah agar bisa menerima parameter filter opsional. Contoh pendekatan:

```php
public function paginate(int $perPage = 10, array $filters = [])
{
    $query = $this->model->with(['category', 'subCategory'])->latest();

    if (!empty($filters['category_id'])) {
        $query->where('category_id', $filters['category_id']);
    }

    return $query->paginate($perPage);
}
```

Catatan penting:

- Jangan menghapus eager load `with(['category', 'subCategory'])`.
- Jangan mengubah urutan `latest()` kecuali ada kebutuhan lain.
- Gunakan filter hanya jika `category_id` tidak kosong.
- Jangan langsung memasukkan semua request ke query tanpa validasi.

### 4. Update Service bila Diperlukan

File yang perlu dicek:

```text
app/Services/QuestionBankService.php
```

Saat ini `QuestionBankService` mewarisi behavior dari `BaseService`. Perlu dicek apakah `getPaginated(10)` bisa meneruskan filter atau tidak.

Ada dua opsi implementasi.

#### Opsi A: Tambahkan Method Khusus di Service

Ini opsi yang disarankan karena jelas dan aman untuk junior programmer.

Tambahkan method seperti:

```php
public function getPaginatedWithFilters(int $perPage = 10, array $filters = [])
{
    return $this->repository->paginate($perPage, $filters);
}
```

Lalu controller memanggil method ini.

Kelebihan:

- Tidak mengganggu method `getPaginated()` yang mungkin dipakai fitur lain.
- Perubahan lebih eksplisit.

#### Opsi B: Ubah BaseService

Jangan pilih opsi ini kecuali benar-benar paham dampaknya.

Mengubah `BaseService` bisa memengaruhi fitur lain yang memakai service/repository lain.

### 5. Update Controller untuk Membaca Filter

File yang perlu diubah:

```text
app/Http/Controllers/Admin/QuestionBankController.php
```

Di method `index(Request $request)`, ambil parameter `category_id` dari query string.

Contoh implementasi:

```php
public function index(Request $request)
{
    $filters = [
        'category_id' => $request->query('category_id'),
    ];

    $questions = $this->questionService->getPaginatedWithFilters(10, $filters);
    $categories = Category::all();

    if ($request->ajax()) {
        return $this->successResponse($questions);
    }

    return view('admin.questions.index', compact('questions', 'categories', 'filters'));
}
```

Hal yang perlu diperhatikan:

- Kirim `$filters` ke view agar dropdown bisa menandai pilihan aktif.
- Jika `category_id` kosong, tampilkan semua soal.
- Sebaiknya validasi ringan dilakukan agar `category_id` hanya dipakai jika ada di tabel `categories`.

Rekomendasi validasi ringan:

```php
$categoryId = $request->query('category_id');

if ($categoryId && !Category::whereKey($categoryId)->exists()) {
    $categoryId = null;
}
```

Dengan begitu, jika user membuka URL `/admin/questions?category_id=999999`, aplikasi tidak error dan bisa kembali menampilkan semua data.

### 6. Tambahkan Dropdown Filter di View

File yang perlu diubah:

```text
resources/views/admin/questions/index.blade.php
```

Tambahkan `<select>` di area header, dekat input search `Cari soal...`.

Saat ini ada struktur kurang lebih seperti:

```html
<div class="flex-stack-mobile" style="display: flex; gap: 16px;">
    <div style="position: relative; width: 300px;">
        <input type="text" id="searchInput" ...>
    </div>
    <button class="btn-primary" onclick="openQuestionModal('create')">...</button>
</div>
```

Tambahkan dropdown sebelum search atau sesudah search. Contoh:

```blade
<form method="GET" action="{{ route('admin.questions.index') }}" style="display: flex; gap: 12px; align-items: center; margin: 0;">
    <select name="category_id" class="form-input" onchange="this.form.submit()" style="width: 220px; margin-bottom: 0;">
        <option value="">Semua Mata Pelajaran</option>
        @foreach($categories as $category)
            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                {{ $category->name }}
            </option>
        @endforeach
    </select>
</form>
```

Catatan:

- Gunakan method `GET` agar filter muncul di URL.
- Gunakan `onchange="this.form.submit()"` agar user tidak perlu klik tombol filter.
- Pastikan style tidak merusak layout mobile.
- Jangan memberi `id="searchInput"` ke dropdown karena ID tersebut sudah dipakai untuk pencarian soal.

### 7. Pastikan Search Existing Tetap Berjalan

Di view saat ini ada input:

```html
<input type="text" id="searchInput" class="form-input" placeholder="Cari soal...">
```

Kemungkinan ada JavaScript yang melakukan search client-side berdasarkan isi tabel.

Setelah menambah filter:

- Jangan menghapus `id="searchInput"`.
- Jangan mengubah struktur tabel secara besar-besaran.
- Pastikan search tetap bisa menyaring baris yang sedang tampil.
- Filter mata pelajaran bekerja dari server, search bekerja di halaman hasil saat ini.

Catatan untuk implementer:

- Search client-side dan filter server-side boleh berjalan berdampingan.
- Jika search diketik setelah filter mata pelajaran dipilih, search hanya mencari di data soal yang sudah difilter pada halaman tersebut.

### 8. Preserve Filter di Pagination

Ini bagian penting.

Jika pagination tidak diupdate, saat user klik halaman 2 filter bisa hilang.

Di view saat ini kemungkinan pagination seperti:

```blade
{{ $questions->links() }}
```

Ubah menjadi:

```blade
{{ $questions->appends(request()->query())->links() }}
```

Tujuannya:

- Semua query string aktif, termasuk `category_id`, tetap ikut di link pagination.
- Contoh link menjadi `/admin/questions?category_id=3&page=2`.

Checklist:

- Pilih mata pelajaran.
- Klik page 2.
- Pastikan dropdown masih memilih mata pelajaran yang sama.
- Pastikan data masih terfilter.

### 9. Tambahkan Tombol Reset Opsional

Tombol reset tidak wajib, tapi disarankan agar UX lebih jelas.

Jika ingin ditambahkan, letakkan dekat dropdown filter.

Contoh:

```blade
@if(request('category_id'))
    <a href="{{ route('admin.questions.index') }}" class="btn-secondary" style="text-decoration: none; display: inline-flex; align-items: center;">
        Reset
    </a>
@endif
```

Catatan:

- Pastikan class `btn-secondary` memang ada. Jika tidak ada, gunakan style sederhana yang konsisten dengan UI existing.
- Reset harus menghapus query `category_id`.

### 10. Tampilkan State Kosong yang Jelas

Saat filter dipilih tapi tidak ada soal, pesan kosong saat ini kemungkinan:

```text
Belum ada soal.
```

Boleh ditingkatkan agar lebih informatif:

```blade
@if(request('category_id'))
    Tidak ada soal untuk mata pelajaran yang dipilih.
@else
    Belum ada soal.
@endif
```

Tujuannya agar admin tahu bahwa data kosong karena filter, bukan karena Bank Soal benar-benar kosong.

### 11. Validasi Manual

Setelah implementasi, lakukan testing manual di browser.

Skenario yang wajib diuji:

1. Buka `/admin/questions` tanpa filter.
   - Semua soal tampil.
   - Dropdown menampilkan `Semua Mata Pelajaran`.

2. Pilih salah satu mata pelajaran.
   - URL berubah menjadi `/admin/questions?category_id=...`.
   - Tabel hanya menampilkan soal dari mata pelajaran tersebut.
   - Dropdown tetap memilih mata pelajaran yang dipilih.

3. Klik pagination setelah filter aktif.
   - Query `category_id` tetap ada di URL.
   - Data tetap terfilter.
   - Dropdown tetap sesuai.

4. Pilih `Semua Mata Pelajaran`.
   - Filter hilang atau `category_id` kosong.
   - Semua soal tampil kembali.

5. Gunakan search setelah filter aktif.
   - Search tetap berjalan pada data yang sedang tampil.
   - Tidak ada error JavaScript di console.

6. Klik Tambah Soal.
   - Modal tambah soal tetap terbuka.
   - Dropdown dalam modal tambah soal tetap berjalan.
   - Fitur sub kategori dan kode soal tidak rusak.

7. Klik Edit, Preview, dan Delete.
   - Semua action tetap berjalan seperti sebelumnya.

### 12. Testing dengan Data Tidak Valid

Uji URL manual:

```text
/admin/questions?category_id=999999
/admin/questions?category_id=abc
```

Expected result:

- Aplikasi tidak error.
- Halaman tetap bisa dibuka.
- Filter invalid diabaikan atau kembali ke semua soal.

Jika terjadi error SQL atau exception, perbaiki validasi controller.

### 13. Perintah Cek yang Disarankan

Gunakan perintah berikut untuk mencari lokasi kode terkait:

```bash
rg "QuestionBankController|admin.questions|questions->links|searchInput|Category::all" app resources routes
```

Jika project menggunakan Laravel Pint atau test suite, jalankan sesuai setup project. Minimal cek syntax PHP:

```bash
php -l app/Http/Controllers/Admin/QuestionBankController.php
php -l app/Repositories/QuestionBankRepository.php
php -l app/Services/QuestionBankService.php
```

Jika ingin menjalankan server lokal:

```bash
php artisan serve
```

Lalu buka:

```text
http://127.0.0.1:8000/admin/questions
```

## Rekomendasi Bentuk Perubahan Kode

### Repository

Target perubahan:

```text
app/Repositories/QuestionBankRepository.php
```

Ubah method `paginate()` agar menerima `$filters`.

Contoh final yang diharapkan:

```php
public function paginate(int $perPage = 10, array $filters = [])
{
    $query = $this->model->with(['category', 'subCategory'])->latest();

    if (!empty($filters['category_id'])) {
        $query->where('category_id', $filters['category_id']);
    }

    return $query->paginate($perPage);
}
```

### Service

Target perubahan:

```text
app/Services/QuestionBankService.php
```

Tambahkan method khusus:

```php
public function getPaginatedWithFilters(int $perPage = 10, array $filters = [])
{
    return $this->repository->paginate($perPage, $filters);
}
```

### Controller

Target perubahan:

```text
app/Http/Controllers/Admin/QuestionBankController.php
```

Update method `index()` agar membaca `category_id`.

Contoh:

```php
public function index(Request $request)
{
    $categoryId = $request->query('category_id');

    if ($categoryId && !Category::whereKey($categoryId)->exists()) {
        $categoryId = null;
    }

    $filters = [
        'category_id' => $categoryId,
    ];

    $questions = $this->questionService->getPaginatedWithFilters(10, $filters);
    $categories = Category::all();

    if ($request->ajax()) {
        return $this->successResponse($questions);
    }

    return view('admin.questions.index', compact('questions', 'categories', 'filters'));
}
```

### View

Target perubahan:

```text
resources/views/admin/questions/index.blade.php
```

Tambahkan dropdown filter di area header.

Contoh:

```blade
<form method="GET" action="{{ route('admin.questions.index') }}" style="display: flex; gap: 12px; align-items: center; margin: 0;">
    <select name="category_id" class="form-input" onchange="this.form.submit()" style="width: 220px; margin-bottom: 0;">
        <option value="">Semua Mata Pelajaran</option>
        @foreach($categories as $category)
            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                {{ $category->name }}
            </option>
        @endforeach
    </select>
</form>
```

Update pagination:

```blade
{{ $questions->appends(request()->query())->links() }}
```

## Acceptance Criteria

Fitur dianggap selesai jika semua poin berikut terpenuhi:

- Di halaman `/admin/questions` ada dropdown filter mata pelajaran.
- Dropdown berisi opsi `Semua Mata Pelajaran` dan daftar mata pelajaran dari database.
- Saat memilih mata pelajaran, URL membawa query `category_id`.
- Tabel hanya menampilkan soal dari mata pelajaran yang dipilih.
- Saat memilih `Semua Mata Pelajaran`, semua soal tampil kembali.
- Pagination tetap mempertahankan filter aktif.
- Dropdown tetap menampilkan pilihan aktif setelah reload dan pagination.
- Search existing tetap berfungsi.
- Fitur tambah soal tetap berfungsi.
- Fitur edit soal tetap berfungsi.
- Fitur preview soal tetap berfungsi.
- Fitur hapus soal tetap berfungsi.
- URL dengan `category_id` invalid tidak menyebabkan error.
- Tidak ada perubahan database atau migration baru.
- Tidak ada perubahan route baru yang tidak diperlukan.

## Catatan untuk Junior Programmer atau AI Model

- Fokus pekerjaan hanya pada filter mata pelajaran di Bank Soal.
- Jangan mengubah fitur lain di halaman Bank Soal.
- Jangan menghapus JavaScript existing karena halaman ini punya banyak logic modal, TinyMCE, Math editor, sub kategori, kode soal, preview, edit, dan delete.
- Jangan mengubah nama route `admin.questions.index`.
- Jangan membuat migration baru karena data mata pelajaran sudah berasal dari model `Category`.
- Jangan melakukan filter hanya dengan JavaScript karena data tabel memakai pagination.
- Pastikan perubahan query dilakukan di repository/service agar struktur project tetap konsisten.
- Jika ragu, buat perubahan kecil dulu: repository, service, controller, view, lalu test manual.

## Estimasi File yang Akan Berubah

Kemungkinan hanya 4 file berikut yang perlu diubah:

- `app/Repositories/QuestionBankRepository.php`
- `app/Services/QuestionBankService.php`
- `app/Http/Controllers/Admin/QuestionBankController.php`
- `resources/views/admin/questions/index.blade.php`

Tidak perlu mengubah:

- `routes/web.php`
- database migration
- model `QuestionBank`
- model `Category`

## Risiko yang Perlu Dihindari

- Filter hilang saat klik pagination.
- Dropdown filter merusak layout header di mobile.
- Search client-side berhenti bekerja karena struktur HTML diubah terlalu banyak.
- Modal tambah/edit soal rusak karena ID elemen existing berubah.
- Query error saat `category_id` invalid.
- Mengubah `BaseService` atau `BaseRepository` sehingga fitur lain ikut terdampak.

## Urutan Kerja Singkat

1. Baca `QuestionBankController@index`.
2. Baca `QuestionBankRepository::paginate()`.
3. Tambahkan filter `category_id` di repository.
4. Tambahkan method service khusus untuk filter.
5. Update controller agar membaca query `category_id`.
6. Kirim `$filters` ke view jika diperlukan.
7. Tambahkan dropdown filter di view.
8. Update pagination dengan `appends(request()->query())`.
9. Test manual semua skenario.
10. Pastikan fitur CRUD soal tetap normal.
