# 🖼️ ISSUE: Fitur Upload Gambar di Konten Soal & Pembahasan

**Prioritas:** Medium  
**Estimasi:** 4-6 jam  
**Halaman terkait:** `http://localhost:8000/admin/sub-categories/{id}` → Tombol "Buat Bank Soal"

---

## 📋 Deskripsi

Saat ini, form bank soal menggunakan **TinyMCE** sebagai rich text editor untuk field **Konten Soal** (`question_text`) dan **Pembahasan** (`explanation`). Editor ini sudah support formatting teks (bold, italic, list, dll) dan rumus matematika.

**Yang diinginkan:** Tambahkan kemampuan **upload gambar langsung** di dalam editor TinyMCE untuk kedua field tersebut (Konten Soal dan Pembahasan), sehingga admin bisa menyisipkan gambar di tengah-tengah teks soal maupun pembahasan.

> ⚠️ **PENTING:** Ini BERBEDA dengan field "Gambar Soal (Opsional)" yang sudah ada. Field itu adalah gambar terpisah (satu file upload). Yang diminta di sini adalah kemampuan menyisipkan gambar **di dalam** konten teks editor (inline image).

---

## 🔍 Hasil Analisis Codebase

### A. Kondisi Database (Migration)

**Kolom yang sudah ada di tabel `question_banks`:**

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `question_text` | `text` | Konten soal — sudah menyimpan HTML dari TinyMCE |
| `question_image` | `string, nullable` | Gambar terpisah (bukan inline) — sudah ada |
| `explanation` | `text, nullable` | Pembahasan — sudah menyimpan HTML dari TinyMCE |

✅ **Kesimpulan Database:** Kolom `question_text` dan `explanation` bertipe `TEXT` dan sudah menyimpan konten HTML. Gambar inline akan disimpan sebagai tag `<img src="...">` di dalam HTML. **TIDAK PERLU menambah kolom baru atau membuat migration baru.** Tipe `TEXT` di MySQL bisa menampung hingga ~65KB, yang cukup untuk HTML dengan URL gambar.

### B. Kondisi TinyMCE Saat Ini

**File:** `resources/views/admin/questions/_modal_scripts.blade.php` (baris 11-83)

```javascript
// Konfigurasi TinyMCE saat ini:
tinymce.init({
    selector: '#qText',                    // Konten Soal
    plugins: 'lists link code table charmap', // ❌ Belum ada plugin 'image'
    toolbar: '... | charmap code | mathBtn',  // ❌ Belum ada tombol 'image'
});

tinymce.init({
    selector: '#explanationText',           // Pembahasan
    plugins: 'lists link code table charmap', // ❌ Belum ada plugin 'image'
    toolbar: '... | charmap code | mathBtn',  // ❌ Belum ada tombol 'image'
});
```

### C. Kondisi Backend

**File:** `app/Http/Controllers/Admin/QuestionBankController.php`

- Method `store()` (baris 53-87): Sudah handle upload `question_image` sebagai file terpisah
- Method `update()` (baris 96-136): Sama, sudah handle upload file terpisah
- **Belum ada endpoint** untuk meng-handle upload gambar inline dari TinyMCE

### D. Kondisi Storage

- Storage symlink sudah aktif: `public/storage` → `storage/app/public`
- Disk `public` sudah dikonfigurasi di `config/filesystems.php`
- Gambar `question_image` disimpan ke folder `questions/` di disk `public`

### E. Tampilan Student-Side

- **Exam view** (`resources/views/exam/main.blade.php`): Render `question_text` via `innerHTML` (baris 487) — ✅ sudah support tag `<img>`
- **Review view** (`resources/views/participant/review_category.blade.php`): Render via `{!! $question->question_text !!}` (baris 128) dan `{!! $question->explanation !!}` (baris 218) — ✅ sudah support tag `<img>`

---

## 📝 Tahapan Implementasi

### Tahap 1: Buat Endpoint Upload Gambar di Backend

**Tujuan:** Buat API endpoint baru yang menerima file gambar dari TinyMCE dan mengembalikan URL gambar yang bisa diakses publik.

**File yang diubah:** `app/Http/Controllers/Admin/QuestionBankController.php`

**Yang harus dilakukan:**

1. Tambahkan method baru `uploadImage` di `QuestionBankController`:

```php
/**
 * Handle image upload from TinyMCE editor.
 * Menerima file gambar, simpan ke storage, return URL.
 */
public function uploadImage(Request $request)
{
    $validator = Validator::make($request->all(), [
        'file' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // max 5MB
    ]);

    if ($validator->fails()) {
        return response()->json([
            'error' => $validator->errors()->first()
        ], 422);
    }

    $path = $request->file('file')->store('questions/inline', 'public');

    return response()->json([
        'location' => asset('storage/' . $path)
    ]);
}
```

**Penjelasan kode:**
- `$request->file('file')` → ambil file yang diupload dari TinyMCE
- `->store('questions/inline', 'public')` → simpan ke folder `storage/app/public/questions/inline/`
- `asset('storage/' . $path)` → buat URL publik yang bisa diakses browser
- TinyMCE mengharapkan response JSON dengan key `location` yang berisi URL gambar

> 💡 **Catatan:** Folder `questions/inline/` dipakai agar terpisah dari gambar `question_image` yang ada di `questions/`.

---

### Tahap 2: Tambah Route untuk Endpoint Upload

**File yang diubah:** `routes/web.php`

**Yang harus dilakukan:**

Cari blok route admin yang di-protect oleh `SuperAdminMiddleware` (sekitar baris 64-68), lalu tambahkan route baru **di dalam** blok tersebut:

```php
Route::post('/admin/questions/upload-image', [
    \App\Http\Controllers\Admin\QuestionBankController::class, 'uploadImage'
])->name('admin.questions.upload-image');
```

> ⚠️ **PENTING:** Route ini **HARUS** ditaruh **SEBELUM** baris `Route::resource('/admin/questions', ...)` (baris 68). Kalau ditaruh setelahnya, route `resource` akan menangkap path `upload-image` sebagai parameter `{question}` dan error 404.

**Lokasi yang benar (di antara baris 67 dan 68):**

```php
Route::get('/admin/questions/kode-soal', ...)->name('admin.questions.kode-soal');
// ↓↓↓ TAMBAHKAN DI SINI ↓↓↓
Route::post('/admin/questions/upload-image', [\App\Http\Controllers\Admin\QuestionBankController::class, 'uploadImage'])->name('admin.questions.upload-image');
// ↑↑↑ SEBELUM RESOURCE ↑↑↑
Route::resource('/admin/questions', ...)->names('admin.questions');
```

---

### Tahap 3: Update Konfigurasi TinyMCE di Frontend

**File yang diubah:** `resources/views/admin/questions/_modal_scripts.blade.php`

**Yang harus dilakukan:**

Ubah kedua inisialisasi TinyMCE (untuk `#qText` dan `#explanationText`) dengan menambahkan:
1. Plugin `image` ke daftar plugins
2. Tombol `image` ke toolbar
3. Konfigurasi `images_upload_handler` sebagai custom uploader

#### 3a. Ubah konfigurasi TinyMCE untuk `#qText` (Konten Soal)

**Cari baris ini** (sekitar baris 23-52):

```javascript
tinymce.init({
    selector: '#qText',
    height: 250,
    menubar: false,
    skin: 'oxide',
    content_css: false,
    plugins: 'lists link code table charmap',
    toolbar: 'undo redo | blocks | bold italic underline strikethrough | bullist numlist | link table | charmap code | mathBtn',
```

**Ubah menjadi:**

```javascript
tinymce.init({
    selector: '#qText',
    height: 250,
    menubar: false,
    skin: 'oxide',
    content_css: false,
    plugins: 'lists link code table charmap image',
    toolbar: 'undo redo | blocks | bold italic underline strikethrough | bullist numlist | link table | image | charmap code | mathBtn',
    // Konfigurasi upload gambar
    automatic_uploads: true,
    images_reuse_filename: false,
    file_picker_types: 'image',
    images_upload_handler: function(blobInfo) {
        return new Promise(function(resolve, reject) {
            var formData = new FormData();
            formData.append('file', blobInfo.blob(), blobInfo.filename());

            fetch('{{ route("admin.questions.upload-image") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json'
                }
            })
            .then(function(response) {
                if (!response.ok) {
                    return response.json().then(function(json) {
                        reject('Upload gagal: ' + (json.error || response.statusText));
                    });
                }
                return response.json();
            })
            .then(function(json) {
                if (json && json.location) {
                    resolve(json.location);
                } else {
                    reject('Response tidak valid dari server');
                }
            })
            .catch(function(error) {
                reject('Upload error: ' + error.message);
            });
        });
    },
```

#### 3b. Ubah konfigurasi TinyMCE untuk `#explanationText` (Pembahasan)

**Lakukan hal yang PERSIS sama** seperti 3a, tapi untuk blok `tinymce.init` yang kedua (selector `#explanationText`, sekitar baris 54-83).

Perubahan yang sama:
- `plugins: 'lists link code table charmap image'` ← tambah `image`
- `toolbar: '... | image | charmap code | mathBtn'` ← tambah `image`
- Tambahkan seluruh blok `automatic_uploads`, `images_upload_handler`, dll (copy-paste yang sama)

---

### Tahap 4: Tambah Styling untuk Gambar Inline

**File yang diubah:** `resources/views/admin/questions/_modal_scripts.blade.php`

**Yang harus dilakukan:**

Di kedua konfigurasi TinyMCE, update `content_style` agar gambar yang dimasukkan ke editor terlihat rapi:

**Cari:**
```javascript
content_style: `
    body { 
        font-family: 'Inter', sans-serif; 
        font-size: 14px; 
        color: #0f172a; 
        background: #ffffff;
        padding: 12px;
    }
    p { margin: 0 0 10px; }
`,
```

**Ubah menjadi:**
```javascript
content_style: `
    body { 
        font-family: 'Inter', sans-serif; 
        font-size: 14px; 
        color: #0f172a; 
        background: #ffffff;
        padding: 12px;
    }
    p { margin: 0 0 10px; }
    img { max-width: 100%; height: auto; border-radius: 8px; margin: 8px 0; }
`,
```

> 💡 Ini agar gambar yang disisipkan di editor tidak meluber keluar area editor.

---

### Tahap 5: Pastikan Gambar Inline Tampil di Sisi Student & Preview

**File yang perlu dicek (BIASANYA tidak perlu diubah, tapi perlu ditambah CSS):**

Karena konten `question_text` dan `explanation` sudah di-render sebagai raw HTML (`{!! !!}` atau `innerHTML`), gambar inline (`<img src="...">`) akan otomatis tampil. **Tapi** perlu pastikan styling-nya:

#### 5a. Review view — `resources/views/participant/review_category.blade.php`

**Cek baris 16-18** — sudah ada styling untuk `img` di `.review-explanation`:
```css
.review-explanation img {
    max-width: 100%;
    height: auto;
    ...
}
```

**Tambahkan** styling yang sama untuk konten soal jika belum ada. Cari class yang membungkus `question_text` dan tambahkan:

```css
.review-question-content img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 8px 0;
}
```

#### 5b. Exam view — `resources/views/exam/main.blade.php`

**Cari elemen dengan id `questionText`** (baris 487), lalu pastikan ada CSS:

```css
#questionText img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 8px 0;
}
```

#### 5c. Preview modal (Admin) — `resources/views/admin/sub_categories/show.blade.php`

**Cari** `#previewText` dan `#previewExplanation`, tambahkan:

```css
#previewText img,
#previewExplanation img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 8px 0;
}
```

---

## ✅ Checklist Verifikasi

Setelah semua perubahan selesai, lakukan tes berikut:

### Test 1: Upload gambar di Konten Soal
- [ ] Buka `http://localhost:8000/admin/sub-categories/1`
- [ ] Klik "Buat Bank Soal"
- [ ] Di editor "Konten Soal", klik ikon gambar (🖼️) di toolbar
- [ ] Upload file gambar (JPG/PNG, max 5MB)
- [ ] Gambar harus muncul di dalam editor
- [ ] Simpan soal → cek apakah berhasil tanpa error

### Test 2: Upload gambar di Pembahasan
- [ ] Sama seperti Test 1, tapi di editor "Pembahasan"
- [ ] Upload gambar di tengah-tengah teks pembahasan
- [ ] Simpan dan pastikan berhasil

### Test 3: Preview soal (Admin)
- [ ] Klik ikon mata 👁️ di daftar soal
- [ ] Gambar inline di konten soal harus tampil
- [ ] Gambar inline di pembahasan harus tampil

### Test 4: Tampilan di sisi siswa
- [ ] Buka ujian yang berisi soal tersebut
- [ ] Gambar inline di soal harus tampil saat mengerjakan
- [ ] Gambar inline di pembahasan harus tampil saat review jawaban

### Test 5: Edit soal
- [ ] Edit soal yang sudah ada gambar inline
- [ ] Gambar harus tetap muncul di editor
- [ ] Bisa tambah/hapus gambar, lalu simpan

### Test 6: Validasi file
- [ ] Coba upload file non-gambar (PDF, docx) → harus ditolak
- [ ] Coba upload file > 5MB → harus ditolak

---

## 📁 Ringkasan File yang Diubah

| No | File | Aksi | Keterangan |
|----|------|------|------------|
| 1 | `app/Http/Controllers/Admin/QuestionBankController.php` | **EDIT** | Tambah method `uploadImage()` |
| 2 | `routes/web.php` | **EDIT** | Tambah route `POST /admin/questions/upload-image` |
| 3 | `resources/views/admin/questions/_modal_scripts.blade.php` | **EDIT** | Update 2 konfigurasi TinyMCE (tambah plugin & handler) |
| 4 | `resources/views/participant/review_category.blade.php` | **EDIT** | Tambah CSS untuk `img` di konten soal (jika belum ada) |
| 5 | `resources/views/exam/main.blade.php` | **EDIT** | Tambah CSS untuk `img` di `#questionText` |
| 6 | `resources/views/admin/sub_categories/show.blade.php` | **EDIT** | Tambah CSS untuk `img` di preview modal |

**File yang TIDAK perlu diubah:**
- ❌ Migration — tidak perlu kolom baru
- ❌ Model `QuestionBank` — `$fillable` sudah mencakup `question_text` dan `explanation`
- ❌ Service & Repository — tidak ada logika yang perlu berubah
- ❌ `config/filesystems.php` — konfigurasi storage sudah benar

---

## ⚠️ Catatan Penting

1. **CSRF Token:** TinyMCE upload handler HARUS menyertakan CSRF token di header. Tanpa ini, Laravel akan return 419 (Session Expired).

2. **Urutan Route:** Route `upload-image` HARUS sebelum `Route::resource('questions')`. Ini karena resource route punya wildcard `{question}` yang akan menangkap `upload-image` sebagai ID.

3. **Max File Size:** Selain validasi di Laravel (5MB), pastikan `php.ini` mengizinkan upload besar:
   - `upload_max_filesize = 10M`
   - `post_max_size = 12M`

4. **Storage Link:** Sudah aktif (`php artisan storage:link`), jadi gambar yang diupload langsung bisa diakses via URL.

5. **Keamanan:** Gambar di-serve langsung sebagai static file via symlink. Tidak ada masalah keamanan selama validasi tipe file (`mimes:jpeg,png,jpg,gif,webp`) diterapkan di backend.
