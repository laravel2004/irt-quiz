# Issue: Menampilkan Modal "Buat Bank Soal" Tanpa Redirect di Halaman Sub-Kategori

## Deskripsi Fitur
Saat ini, ketika pengguna menekan tombol **"Buat Bank Soal"** di halaman detail Sub-Category (`/admin/sub-categories/{id}`), pengguna dialihkan (redirect) ke halaman daftar pertanyaan (`/admin/questions`). Pengguna menginginkan agar **form untuk menambah soal (modal)** langsung muncul di halaman yang sama tanpa harus berpindah halaman.

Dokumen ini berisi panduan tahap demi tahap untuk mengimplementasikan fitur tersebut agar bisa dieksekusi dengan mudah oleh junior programmer.

---

## Tahapan Implementasi Secara Detail

### 1. Ekstraksi Modal dan JavaScript dari `questions/index.blade.php`
Halaman `resources/views/admin/questions/index.blade.php` saat ini menampung HTML untuk modal "Tambah Soal Baru" (dengan ID `#questionModal`) beserta script JavaScript yang sangat besar untuk menangani logika pembuatan soal (TinyMCE, editor matematika, opsi jawaban, dll). 
Karena modal ini sekarang perlu digunakan di halaman Sub-Category, kita harus memisahkannya (refactor) menjadi file komponen/partial view.

**Langkah yang dilakukan:**
1. **Buat file `resources/views/admin/questions/_modal_form.blade.php`**
   - Pindahkan elemen HTML `<div class="modal-overlay" id="questionModal"> ... </div>` beserta elemen modal lainnya (seperti `#previewModal`, `#mathEditorModal`) dari `index.blade.php` ke dalam file partial ini.
2. **Buat file `resources/views/admin/questions/_modal_scripts.blade.php`**
   - Pindahkan seluruh logika JavaScript yang berkaitan dengan modal soal (termasuk fungsi `openQuestionModal`, `closeQuestionModal`, form submit event listener, script inisialisasi teks editor, dll) ke file partial ini.
3. **Include kembali ke `index.blade.php`**
   - Di file `resources/views/admin/questions/index.blade.php` yang asli, tambahkan kode berikut untuk memanggil file yang sudah dipisah:
     ```blade
     @include('admin.questions._modal_form')
     
     @push('scripts')
         @include('admin.questions._modal_scripts')
     @endpush
     ```

### 2. Modifikasi Fungsi `openQuestionModal` di JavaScript
Di dalam script yang baru saja diekstrak, fungsi `openQuestionModal` biasanya hanya menerima parameter `mode` (`create` atau `edit`). Kita perlu menambahkan dukungan parameter kategori agar dropdown kategori otomatis terisi saat modal dibuka dari halaman spesifik.

**Langkah yang dilakukan:**
1. Temukan fungsi `openQuestionModal`.
2. Ubah parameternya menjadi:
   ```javascript
   function openQuestionModal(m = 'create', defaultCategoryId = null, defaultSubCategoryId = null) {
       mode = m;
       document.getElementById('modalTitle').innerText = mode === 'create' ? 'Tambah Soal Baru' : 'Edit Soal';
       
       if (mode === 'create') {
           document.getElementById('questionId').value = '';
           questionForm.reset();
           // ... (kode reset bawaan lainnya)
           
           // TAMBAHKAN LOGIKA INI:
           if (defaultCategoryId) {
               const categorySelect = document.querySelector('select[name="category_id"]');
               if (categorySelect) {
                   categorySelect.value = defaultCategoryId;
                   
                   // Jika ada script existing yang meload subkategori saat kategori diubah,
                   // panggil trigger event change atau panggil fungsinya langsung.
                   // Contoh: 
                   // loadSubCategories(defaultCategoryId).then(() => {
                   //     document.querySelector('select[name="sub_category_id"]').value = defaultSubCategoryId;
                   // });
               }
           }
       }
       // ... (sisa kode)
       document.getElementById('questionModal').classList.add('active');
   }
   ```

### 3. Integrasikan ke Halaman `sub_categories/show.blade.php`
Sekarang partial view sudah siap. Kita perlu menempatkan modal tersebut di halaman detail sub-kategori dan mengubah aksi tombol.

**Langkah yang dilakukan:**
1. Buka file `resources/views/admin/sub_categories/show.blade.php`.
2. Scroll ke bagian paling bawah file (sebelum `@endsection` jika ada), lalu tambahkan include untuk HTML modal:
   ```blade
   @include('admin.questions._modal_form')
   ```
3. Di dalam block `@push('scripts')`, tambahkan include untuk script JS:
   ```blade
   @include('admin.questions._modal_scripts')
   ```
4. Cari tombol **"Buat Bank Soal"**. Saat ini tombol tersebut berbentuk link `<a>`:
   ```html
   <a href="{{ route('admin.questions.index', ['action' => 'create', 'category_id' => $subCategory->category_id, 'sub_category_id' => $subCategory->id]) }}" class="btn-primary" style="text-decoration: none;">
       <i class="fas fa-plus"></i> Buat Bank Soal
   </a>
   ```
5. **Ubah menjadi `<button>`** agar tidak melakukan redirect, melainkan memanggil fungsi JS pembuka modal:
   ```html
   <button type="button" class="btn-primary" onclick="openQuestionModal('create', {{ $subCategory->category_id }}, {{ $subCategory->id }})">
       <i class="fas fa-plus"></i> Buat Bank Soal
   </button>
   ```

### 4. Verifikasi Proses Simpan (Submit Form)
- Proses `submit` form biasanya menggunakan Fetch API / AJAX di dalam file Javascript yang dipindah.
- Ketika respons server adalah _success_, Javascript biasanya akan menampilkan notifikasi sukses dan melakukan _reload_ halaman dengan `window.location.reload()`.
- Hal ini **sudah benar dan tidak perlu diubah secara drastis**. Saat pengguna menyimpan form pada halaman `sub_categories/show`, eksekusi `window.location.reload()` akan me-refresh kembali halaman _show_ (tanpa pindah ke route lain), dan soal baru tersebut akan muncul di tabel daftar soal Sub Pelajaran.

---
**Catatan untuk Junior Programmer:**  
Fokuslah pada merapikan pemisahan file terlebih dahulu. Pastikan modal tidak bentrok jika dipanggil, dan perhatikan jalur (path) dari aset-aset gambar/variabel lokal yang mungkin error saat diekstrak ke partial view.
