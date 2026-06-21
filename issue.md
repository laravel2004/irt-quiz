# Planning Implementasi Perubahan Tone UI ke Tema Putih Cerah

## Ringkasan Kebutuhan

Project saat ini memiliki banyak halaman dengan tone gelap. Beberapa komponen seperti layout utama, card, modal, tombol, badge, SweetAlert, grafik, dan section dashboard masih menggunakan warna gelap atau transparansi gelap.

Kebutuhan baru adalah mengubah tone visual seluruh project menjadi tema putih cerah dengan warna utama:

- Putih sebagai warna dasar halaman dan card.
- Biru sebagai warna utama untuk action, link, highlight, dan elemen informatif.
- Kuning sebagai warna aksen untuk premium, perhatian ringan, badge khusus, atau highlight tambahan.

Dokumen ini berisi planning detail agar implementasi bisa dikerjakan oleh junior programmer atau AI model yang lebih murah secara bertahap dan aman.

## Tujuan Implementasi

- Mengubah tampilan aplikasi dari dark tone menjadi light tone.
- Membuat semua halaman terlihat konsisten dengan palet putih, biru, dan kuning.
- Memastikan teks tetap mudah dibaca di background terang.
- Memastikan SweetAlert, modal, card, form, tabel, tombol, dan grafik mengikuti tema baru.
- Menghindari perubahan logic aplikasi. Fokus pekerjaan hanya pada UI dan styling.

## Ruang Lingkup

Yang perlu diperhatikan dan dikerjakan:

1. Semua page yang ada di project.
2. Layout utama aplikasi peserta dan admin.
3. Component seperti card, glass effect, button, badge, alert, modal, table, form, dan chart.
4. SweetAlert atau popup lain yang muncul dari JavaScript.
5. Inline style yang masih memakai warna gelap.
6. CSS variable atau global style yang menjadi dasar tema.
7. Responsiveness desktop dan mobile.

Yang tidak perlu dikerjakan:

- Mengubah database.
- Mengubah alur login, ujian, hasil, atau admin.
- Mengubah controller atau service kecuali ada data style yang hardcoded dari backend.
- Mengganti framework CSS.
- Membuat fitur dark mode toggle, kecuali diminta kemudian.

## Warna Tema yang Disarankan

Gunakan warna berikut sebagai panduan utama. Jika project sudah punya CSS variable, simpan warna ini sebagai variable global.

### Warna Dasar

- Background utama: `#f8fafc` atau `#ffffff`
- Background card: `#ffffff`
- Border lembut: `#e2e8f0`
- Text utama: `#0f172a`
- Text secondary: `#475569`
- Text muted: `#64748b`

### Warna Biru

- Primary blue: `#2563eb`
- Primary blue hover: `#1d4ed8`
- Blue soft background: `#dbeafe`
- Blue border: `#93c5fd`

### Warna Kuning

- Accent yellow: `#facc15`
- Yellow hover: `#eab308`
- Yellow soft background: `#fef9c3`
- Yellow border: `#fde047`
- Yellow text gelap: `#854d0e`

### Warna Status Tambahan

Tetap boleh menggunakan warna status berikut agar user mudah memahami kondisi:

- Success: `#16a34a`
- Danger: `#dc2626`
- Warning: `#f59e0b`
- Info: `#2563eb`

## Prinsip Desain yang Harus Diikuti

- Gunakan background putih atau sangat terang untuk halaman.
- Gunakan card putih dengan border tipis dan shadow lembut.
- Hindari warna teks putih di atas background putih.
- Hindari background hitam, navy gelap, atau transparansi gelap.
- Gunakan biru untuk tombol utama dan link penting.
- Gunakan kuning untuk badge premium, highlight, atau elemen aksen.
- Pastikan semua teks punya kontras yang cukup.
- Jangan mengubah struktur HTML besar-besaran jika tidak perlu.
- Prioritaskan perubahan di CSS variable/global style agar tidak perlu edit terlalu banyak file.

## Tahapan Implementasi

### 1. Audit Semua Halaman Project

Sebelum mengubah style, lakukan audit halaman yang ada di project.

Cari file view di folder:

- `resources/views`
- `resources/views/layouts`
- `resources/views/participant`
- `resources/views/admin`
- folder view lain jika ada

Hal yang perlu dicatat:

- Layout utama yang dipakai peserta.
- Layout utama yang dipakai admin.
- Halaman login/register.
- Dashboard peserta.
- Detail sesi ujian.
- Halaman ujian.
- Halaman hasil ujian.
- Halaman review.
- Halaman statistik.
- Halaman admin dashboard.
- Halaman admin sesi.
- Halaman admin peserta.
- Halaman kategori, subkategori, soal, atau page admin lain.

Output dari tahap ini:

- Daftar file view yang perlu dicek.
- Mengetahui file CSS global atau layout yang mengatur tema utama.
- Mengetahui halaman mana yang punya banyak inline style gelap.

### 2. Cari Sumber Style Global

Cari style utama yang mengatur warna aplikasi.

File yang perlu dicek:

- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/admin.blade.php`
- `resources/css/app.css`
- `resources/js/app.js`
- file CSS lain di `public` atau `resources`
- inline `<style>` di file Blade

Cari variable atau class seperti:

- `--bg-primary`
- `--bg-secondary`
- `--text-primary`
- `--text-secondary`
- `--glass-bg`
- `--glass-border`
- `.glass`
- `.btn-primary`
- `.badge`
- `.modal`
- `.card`

Output dari tahap ini:

- Menentukan apakah tema bisa diubah dari satu tempat global.
- Menentukan file mana yang menjadi prioritas pertama.

### 3. Ubah CSS Variable Global ke Tema Light

Jika project menggunakan CSS variable, ubah variable global terlebih dahulu.

Contoh target variable:

```css
:root {
    --bg-primary: #f8fafc;
    --bg-secondary: #ffffff;
    --text-primary: #0f172a;
    --text-secondary: #475569;
    --primary: #2563eb;
    --primary-hover: #1d4ed8;
    --accent: #facc15;
    --glass-bg: #ffffff;
    --glass-border: #e2e8f0;
}
```

Jika nama variable berbeda, sesuaikan dengan project sebenarnya.

Hal penting:

- Jangan membuat variable baru berlebihan jika variable lama bisa dipakai.
- Pastikan `body` menggunakan background terang.
- Pastikan teks default menggunakan warna gelap.
- Pastikan link dan tombol masih terlihat jelas.

Output dari tahap ini:

- Sebagian besar halaman otomatis berubah menjadi terang.
- Komponen yang memakai variable global ikut berubah.

### 4. Ubah Komponen Card dan Glass Effect

Project kemungkinan memakai class seperti `glass` untuk efek dark glassmorphism. Pada tema terang, efek ini perlu disesuaikan.

Rekomendasi style baru:

```css
.glass {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
}
```

Hindari:

```css
background: rgba(15, 23, 42, 0.8);
color: white;
border: 1px solid rgba(255,255,255,0.1);
```

Checklist:

- Card dashboard terlihat putih.
- Card admin terlihat putih.
- Border tidak terlalu tebal.
- Shadow lembut dan tidak berlebihan.
- Hover card tetap terasa tapi tidak gelap.

Output dari tahap ini:

- Semua card utama terlihat cocok dengan tema putih cerah.

### 5. Ubah Button, Link, dan Badge

Tombol utama sebaiknya menggunakan biru.

Rekomendasi tombol utama:

```css
.btn-primary {
    background: #2563eb;
    color: #ffffff;
    border: 1px solid #2563eb;
}

.btn-primary:hover {
    background: #1d4ed8;
    border-color: #1d4ed8;
}
```

Untuk tombol premium atau aksen:

```css
.btn-accent {
    background: #facc15;
    color: #0f172a;
    border: 1px solid #eab308;
}
```

Badge premium:

```css
.badge-premium {
    background: #fef9c3;
    color: #854d0e;
    border: 1px solid #fde047;
}
```

Checklist:

- Tombol primary biru dengan teks putih.
- Tombol danger tetap merah.
- Tombol success tetap hijau jika memang status berhasil.
- Badge premium kuning.
- Badge aktif bisa menggunakan biru atau hijau.
- Link tidak memakai warna abu gelap yang sulit dibaca.

Output dari tahap ini:

- Action utama di semua halaman terlihat konsisten.

### 6. Audit Inline Style Gelap di Blade

Banyak halaman mungkin memakai inline style seperti:

- `color: white`
- `background: rgba(15, 23, 42, ...)`
- `background: rgba(0,0,0,...)`
- `border: 1px solid rgba(255,255,255,...)`
- `color: #fff`
- `background: #0f172a`
- `background: #111827`

Gunakan search global untuk mencari warna gelap:

```bash
rg "color:\s*white|#fff|#0f172a|#111827|rgba\(0,0,0|rgba\(15, 23, 42|rgba\(255,255,255" resources/views resources/css public
```

Untuk setiap hasil:

- Ganti `color: white` menjadi `color: var(--text-primary)` jika background terang.
- Ganti background gelap menjadi putih atau biru soft.
- Ganti border putih transparan menjadi `#e2e8f0`.
- Jangan asal hapus style jika style tersebut masih dibutuhkan untuk status tertentu.

Output dari tahap ini:

- Inline style gelap utama sudah diganti.
- Teks tetap terbaca setelah background berubah terang.

### 7. Ubah SweetAlert ke Tema Light

Cari semua pemanggilan SweetAlert, biasanya menggunakan `Swal.fire`.

Search:

```bash
rg "Swal\.fire|sweetalert|SweetAlert" resources/views resources/js public
```

Jika ada konfigurasi seperti:

```js
background: 'rgba(15, 23, 42, 0.95)',
color: '#fff'
```

ubah menjadi:

```js
background: '#ffffff',
color: '#0f172a',
confirmButtonColor: '#2563eb',
cancelButtonColor: '#dc2626'
```

Untuk alert premium atau warning, gunakan kuning sebagai aksen:

```js
confirmButtonColor: '#facc15'
```

Tetapi pastikan teks tombol tetap terbaca. Jika tombol kuning, teks sebaiknya gelap.

Checklist SweetAlert:

- Background popup putih.
- Teks popup gelap.
- Tombol confirm dominan biru.
- Tombol cancel merah atau abu.
- Tidak ada popup dengan teks putih di background putih.
- Semua popup di halaman peserta dan admin sudah dicek.

Output dari tahap ini:

- SweetAlert konsisten dengan tema light.

### 8. Ubah Modal dan Overlay

Modal saat ini mungkin memakai overlay gelap. Overlay masih boleh gelap transparan agar fokus ke modal, tetapi isi modal harus putih.

Rekomendasi:

- Overlay: `rgba(15, 23, 42, 0.45)`
- Modal content: `#ffffff`
- Modal title: `#0f172a`
- Modal description: `#475569`
- Modal border: `#e2e8f0`

Jika modal header memakai gradient gelap, ubah ke biru atau kombinasi biru-kuning yang soft.

Output dari tahap ini:

- Modal terlihat terang dan tetap fokus.
- Konten modal mudah dibaca.

### 9. Ubah Form dan Input

Pastikan semua input terlihat jelas di tema putih.

Rekomendasi input:

```css
input,
select,
textarea {
    background: #ffffff;
    color: #0f172a;
    border: 1px solid #cbd5e1;
}

input:focus,
select:focus,
textarea:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}
```

Checklist:

- Placeholder tidak terlalu terang.
- Text input terlihat jelas.
- Select dropdown terlihat jelas.
- Textarea admin dan form soal tetap nyaman digunakan.
- Error validation tetap merah dan jelas.

Output dari tahap ini:

- Semua form bisa dipakai dengan nyaman di tema light.

### 10. Ubah Table dan Admin Page

Halaman admin biasanya banyak memakai tabel. Pastikan tabel mengikuti tema terang.

Rekomendasi:

- Header table: biru soft atau abu sangat terang.
- Row background: putih.
- Border row: `#e2e8f0`.
- Hover row: `#f1f5f9`.
- Text: `#0f172a`.

Checklist admin:

- Dashboard admin terbaca.
- Tabel sesi ujian terbaca.
- Tabel peserta terbaca.
- Tabel soal terbaca.
- Action edit/delete tetap jelas.
- Badge status tetap jelas.

Output dari tahap ini:

- Admin panel tidak lagi terlihat gelap.

### 11. Ubah Halaman Ujian

Halaman ujian sangat penting karena user harus fokus mengerjakan soal.

Checklist halaman ujian:

- Background utama putih atau `#f8fafc`.
- Container soal putih.
- Teks soal gelap dan jelas.
- Opsi jawaban mudah dibaca.
- Opsi terpilih menggunakan biru soft atau kuning soft.
- Tombol next/submit menggunakan biru.
- Timer tetap terlihat jelas.
- Warning waktu hampir habis tetap terlihat jelas.

Output dari tahap ini:

- Halaman ujian nyaman dipakai dan tidak membuat mata lelah.

### 12. Ubah Chart dan Grafik

Jika ada grafik, pastikan warna axis dan label cocok dengan tema terang.

Rekomendasi Chart.js:

```js
ticks: {
    color: '#475569'
},
grid: {
    color: 'rgba(148, 163, 184, 0.25)'
}
```

Dataset:

- Garis utama: biru `#2563eb`
- Fill biru soft: `rgba(37, 99, 235, 0.12)`
- Dataset aksen: kuning `#facc15`

Checklist:

- Label grafik terbaca.
- Grid tidak terlalu gelap.
- Legend tidak memakai warna putih.
- Tooltip tetap terbaca.

Output dari tahap ini:

- Grafik selaras dengan tema putih-biru-kuning.

### 13. Periksa Semua Page Secara Manual

Setelah perubahan CSS dan inline style, buka semua halaman utama secara manual.

Minimal halaman yang harus dicek:

- Login.
- Dashboard peserta.
- Detail sesi peserta.
- Halaman term.
- Halaman ujian.
- Halaman hasil.
- Halaman review.
- Statistik peserta.
- Dashboard admin.
- List sesi admin.
- Form buat/edit sesi.
- Halaman peserta admin.
- Halaman kategori dan soal.

Untuk setiap halaman, cek:

- Background sudah terang.
- Teks terbaca.
- Button terlihat jelas.
- Card tidak gelap.
- Modal tidak gelap.
- SweetAlert tidak gelap.
- Mobile layout tetap rapi.

Output dari tahap ini:

- Daftar halaman yang sudah OK.
- Daftar halaman yang masih perlu perbaikan kecil.

### 14. Testing Interaksi Popup dan Modal

Jangan hanya melihat halaman statis. Test juga interaksi.

Yang perlu dicoba:

- Logout confirmation jika ada.
- Retake confirmation.
- Delete confirmation di admin.
- Submit ujian confirmation.
- Modal analisis AI.
- Modal import/export jika ada.
- Alert error dan success.

Output dari tahap ini:

- Semua SweetAlert dan modal tampil dengan tema light.
- Tidak ada popup dengan warna dark lama.

### 15. Cleanup dan Konsistensi

Setelah semua halaman selesai, lakukan cleanup.

Checklist cleanup:

- Tidak ada style gelap yang tidak sengaja tertinggal.
- Tidak ada duplikasi CSS berlebihan.
- Variable warna digunakan konsisten.
- Inline style yang sulit dirawat dikurangi jika memungkinkan.
- Tidak ada perubahan logic bisnis.
- Tidak ada perubahan route atau database.

Search akhir yang disarankan:

```bash
rg "color:\s*white|#fff|#0f172a|#111827|#020617|rgba\(0,0,0|rgba\(15, 23, 42|background:\s*black" resources/views resources/css public
```

Catatan:

- Tidak semua hasil harus dihapus. Contohnya `color: white` masih boleh dipakai untuk teks di tombol biru.
- Evaluasi setiap hasil berdasarkan konteks.

## Urutan Kerja yang Disarankan

1. Audit semua file view dan CSS.
2. Ubah variable/global theme terlebih dahulu.
3. Ubah layout utama peserta dan admin.
4. Ubah komponen umum seperti `.glass`, `.btn-primary`, `.badge`, form, table.
5. Ubah inline style gelap di halaman peserta.
6. Ubah inline style gelap di halaman admin.
7. Ubah SweetAlert dan modal.
8. Ubah grafik dan chart.
9. Test manual semua halaman utama.
10. Cleanup style yang masih tidak konsisten.

## Acceptance Criteria

Fitur dianggap selesai jika semua poin berikut terpenuhi:

- Semua halaman utama tidak lagi menggunakan tone gelap sebagai tema dominan.
- Background halaman menggunakan putih atau warna sangat terang.
- Card dan container utama menggunakan putih dengan border/shadow lembut.
- Warna utama action adalah biru.
- Warna aksen utama adalah kuning.
- Badge premium menggunakan nuansa kuning.
- SweetAlert menggunakan background putih dan teks gelap.
- Modal content menggunakan background putih dan teks gelap.
- Form dan table terbaca jelas.
- Grafik menggunakan warna label yang cocok untuk background terang.
- Tidak ada teks putih di atas background putih.
- Tidak ada teks gelap di atas background gelap yang tersisa dari tema lama.
- Tampilan mobile tetap rapi.
- Tidak ada perubahan logic bisnis aplikasi.

## Catatan untuk Junior Programmer atau AI Model

- Kerjakan bertahap, jangan ubah semua file sekaligus tanpa validasi.
- Mulai dari style global agar perubahan besar bisa terjadi dari satu tempat.
- Setelah itu baru perbaiki inline style per halaman.
- Jangan hapus class yang dipakai JavaScript.
- Jangan ubah nama route, nama variable backend, atau logic controller.
- Kalau menemukan `color: white`, cek dulu background-nya. Jika background tombol biru, teks putih boleh tetap dipakai.
- Kalau menemukan background gelap untuk overlay modal, boleh tetap digunakan transparan, tetapi isi modal harus putih.
- Prioritaskan readability dan konsistensi dibanding efek visual yang terlalu ramai.
- Jika ragu memilih warna, gunakan putih untuk background, biru untuk action, kuning untuk premium/aksen.
