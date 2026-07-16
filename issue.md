# Implementasi Fitur Edit dan Hapus Soal pada Sub Kategori

**Tujuan**: Menambahkan tombol aksi "Edit" dan "Delete" untuk setiap soal di dalam daftar soal pada halaman detail Sub Kategori (contoh URL: `http://localhost:8000/admin/sub-categories/1`). 

**Target File yang akan dimodifikasi**:
`resources/views/admin/sub_categories/show.blade.php`

---

## Langkah-Langkah Implementasi

### 1. Buka File Target
Buka file `resources/views/admin/sub_categories/show.blade.php`.

### 2. Temukan Bagian Tabel List Soal
Cari baris kode yang me-render baris tabel untuk masing-masing soal. Biasanya berada di dalam blok kode `@forelse($subCategory->questions as $question)`.
Cari tag `<td>` yang menampung tombol aksi (saat ini hanya ada tombol Preview Soal). Kode saat ini terlihat seperti ini:

```html
<td style="text-align: center;">
    <button class="btn-icon" onclick="previewQuestion({{ $question->id }})" title="Preview Soal">
        <i class="fas fa-eye"></i>
    </button>
</td>
```

### 3. Tambahkan Tombol Edit
Di dalam tag `<td>` tersebut, tepat di sebelah tombol Preview, tambahkan link anchor untuk mengarahkan pengguna ke halaman Edit. Gunakan route `admin.questions.edit`.

```html
<!-- Tombol Edit -->
<a href="{{ route('admin.questions.edit', $question->id) }}" class="btn-icon" title="Edit Soal" style="color: #3b82f6;">
    <i class="fas fa-edit"></i>
</a>
```

### 4. Tambahkan Fungsi Hapus dengan Modal Konfirmasi
Karena sistem sudah memiliki fungsi global `customConfirm` di dalam layout utama (`layouts.admin.blade.php`), kita bisa langsung menggunakannya.
Tambahkan *hidden form* (form tersembunyi) dan tombol *trigger* untuk penghapusan di dalam tag `<td>` yang sama.

```html
<!-- Form Hapus (Tersembunyi) -->
<form id="delete-form-{{ $question->id }}" action="{{ route('admin.questions.destroy', $question->id) }}" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<!-- Tombol Hapus -->
<button class="btn-icon" onclick="customConfirm('Apakah Anda yakin ingin menghapus soal ini?', function() { document.getElementById('delete-form-{{ $question->id }}').submit(); })" title="Hapus Soal" style="color: #ef4444;">
    <i class="fas fa-trash"></i>
</button>
```

### 5. Hasil Akhir Kode Kolom Aksi
Pastikan kolom aksi (`<td>` aksi) sekarang memiliki ketiga tombol tersebut dan disusun dengan rapi. 
Gunakan `display: flex; justify-content: center; gap: 8px;` pada kontainer tombol jika diperlukan agar tombol terlihat rapi sejajar.

Contoh hasil akhir untuk `<td>` aksi tersebut:

```html
<td style="text-align: center;">
    <div style="display: flex; justify-content: center; gap: 8px;">
        <!-- Tombol Preview -->
        <button class="btn-icon" onclick="previewQuestion({{ $question->id }})" title="Preview Soal">
            <i class="fas fa-eye"></i>
        </button>

        <!-- Tombol Edit -->
        <a href="{{ route('admin.questions.edit', $question->id) }}" class="btn-icon" title="Edit Soal" style="color: #3b82f6;">
            <i class="fas fa-edit"></i>
        </a>

        <!-- Form dan Tombol Hapus -->
        <form id="delete-form-{{ $question->id }}" action="{{ route('admin.questions.destroy', $question->id) }}" method="POST" style="display: none;">
            @csrf
            @method('DELETE')
        </form>
        <button class="btn-icon" onclick="customConfirm('Apakah Anda yakin ingin menghapus soal ini?', function() { document.getElementById('delete-form-{{ $question->id }}').submit(); })" title="Hapus Soal" style="color: #ef4444;">
            <i class="fas fa-trash"></i>
        </button>
    </div>
</td>
```

### 6. Pengujian (Testing)
- Pastikan halaman tampil tanpa error.
- Coba klik tombol Edit dan pastikan sistem mengarahkan ke halaman formulir edit soal yang benar.
- Coba klik tombol Hapus, pastikan modal konfirmasi muncul.
- Uji opsi "Batal" di dalam modal (data tidak boleh terhapus).
- Uji opsi konfirmasi hapus, pastikan soal terhapus dan sistem me-refresh list data dengan benar.

---
**Catatan untuk Junior Programmer/AI**: 
Fungsi `customConfirm` sudah tertanam (built-in) di dalam file layout `resources/views/layouts/admin.blade.php`. Anda tidak perlu membuat ulang modal HTML/Javascript-nya. Cukup panggil fungsinya dengan pesan dan *callback function* untuk mengeksekusi *submit form* seperti contoh di atas.
