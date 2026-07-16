# Implementasi Tampilan Section Hasil Penilaian IRT

**Tujuan**: Menampilkan section "Hasil Penilaian IRT" secara permanen pada halaman detail Sesi Ujian (contoh URL: `http://localhost:8000/admin/sessions/3`), tanpa mempedulikan status sesi apakah sedang dibuka (aktif) atau ditutup (non-aktif).

**Target File yang akan dimodifikasi**:
`resources/views/admin/sessions/show.blade.php`

---

## Langkah-Langkah Implementasi

### 1. Buka File Target
Buka file `resources/views/admin/sessions/show.blade.php`.

### 2. Temukan Section Hasil Penilaian IRT
Cari blok kode HTML yang menampilkan "Hasil Penilaian IRT". Biasanya ditandai dengan komentar HTML `<!-- IRT Results Section -->`.

Saat ini, section tersebut dibungkus oleh sebuah kondisi Blade (if statement) yang mengecek apakah sesi sedang tidak aktif, sehingga section ini akan hilang (tersembunyi) jika sesi sedang dibuka/aktif. Kode kondisional tersebut terlihat seperti ini:

```html
@if(!$session->is_active)
<!-- IRT Results Section -->
<div class="glass animate-fade-in" style="padding: 32px; margin-top: 32px; border-top: 4px solid var(--accent);">
...
...
</div>
@endif
```

### 3. Hapus Kondisi Pengecekan Status Sesi
Tugas Anda adalah menghapus pembungkus kondisi tersebut agar tabel IRT selalu dirender di halaman.

- Hapus baris kode `@if(!$session->is_active)` yang berada tepat di atas komentar `<!-- IRT Results Section -->` (sekitar baris 336).
- Hapus juga tag penutupnya, yaitu baris `@endif` yang berada di bagian paling bawah section IRT tersebut, tepat sebelum deklarasi `@endsection` (sekitar baris 457).

### 4. Hasil Akhir yang Diharapkan
Setelah tag pembungkus kondisi dihapus, kode tersebut harus langsung merender elemen div IRT secara *default* tanpa *conditional statement*. 

```html
<!-- IRT Results Section -->
<div class="glass animate-fade-in" style="padding: 32px; margin-top: 32px; border-top: 4px solid var(--accent);">
    <div class="flex-stack-mobile" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; gap: 20px;">
        <div>
            <h3 style="font-family: 'Outfit', sans-serif;">Hasil Penilaian IRT</h3>
...
```

### 5. Pengujian (Testing)
- Buka browser dan arahkan ke halaman detail sesi, misalnya `http://localhost:8000/admin/sessions/3`.
- Pastikan section "Hasil Penilaian IRT" tampil di bagian paling bawah halaman.
- Ubah status sesi dengan menekan tombol **Buka Sesi / Tutup Sesi** yang ada di bagian kanan atas halaman.
- Pastikan tabel IRT beserta kelengkapannya tetap tampil di layar secara konsisten pada kedua status (saat *badge* menunjukkan "Aktif" maupun "Non-Aktif").
