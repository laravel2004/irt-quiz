# Planning Implementasi Grafik Nilai Sesi Ujian di Dashboard User

## Ringkasan Kebutuhan

Pada dashboard user sudah ada section card sesi ujian yang menampilkan informasi tentang sesi ujian yang sudah dilakukan oleh user. Informasi tersebut mencakup nama sesi ujian, tanggal pelaksanaan, dan nilai yang diperoleh.

Fitur baru yang perlu ditambahkan adalah grafik nilai di dashboard user. Grafik ini harus diletakkan di atas section card sesi ujian. Grafik akan menampilkan nilai dari setiap sesi ujian yang sudah dilakukan oleh user dalam bentuk visual, misalnya grafik garis atau grafik batang.

Dokumen ini dibuat sebagai panduan implementasi yang cukup detail agar bisa dikerjakan oleh junior programmer atau AI model yang lebih murah tanpa perlu banyak asumsi tambahan.

## Tujuan Implementasi

- Membuat dashboard user lebih informatif dan mudah dipahami.
- Membantu user melihat perkembangan nilai dari sesi ujian yang sudah dilakukan.
- Memastikan data sesi ujian tetap ditampilkan jelas melalui card yang sudah ada.
- Menambahkan visualisasi nilai tanpa merusak tampilan atau alur dashboard yang sudah berjalan.

## Ruang Lingkup Pekerjaan

Yang perlu dikerjakan:

1. Mengecek implementasi dashboard user yang sudah ada.
2. Mengecek data sesi ujian yang sudah dilakukan oleh user.
3. Memastikan card sesi ujian menampilkan informasi dengan jelas.
4. Menyiapkan data nilai untuk kebutuhan grafik.
5. Menambahkan grafik nilai di atas section card sesi ujian.
6. Menangani kondisi ketika user belum memiliki riwayat sesi ujian.
7. Melakukan testing manual untuk memastikan data dan tampilan benar.

Yang tidak termasuk dalam pekerjaan ini:

- Mengubah sistem penilaian ujian.
- Mengubah alur pengerjaan ujian.
- Mengubah fitur admin untuk membuat sesi ujian.
- Mengubah struktur database kecuali benar-benar diperlukan.
- Menambahkan fitur filter kompleks seperti filter per bulan atau per kategori, kecuali sudah diminta kemudian.

## Kondisi Saat Ini yang Harus Diperhatikan

Pada dashboard user terdapat section card sesi ujian. Section ini menampilkan informasi sesi ujian yang sudah dilakukan oleh user, seperti:

- Nama sesi ujian.
- Tanggal pelaksanaan sesi ujian.
- Nilai yang diperoleh user.

Pastikan section ini tetap ada dan tidak dihapus. Jika tampilannya masih kurang jelas, rapikan agar user mudah memahami informasi yang ditampilkan.

Contoh tampilan informasi pada card yang diharapkan:

- Nama sesi ujian terlihat sebagai judul utama card.
- Tanggal pelaksanaan terlihat jelas, misalnya `21 Juni 2026` atau format tanggal lain yang konsisten dengan aplikasi.
- Nilai terlihat menonjol, misalnya `Nilai: 85`.
- Jika ada status ujian, tampilkan secara ringkas, misalnya `Selesai`.

## Fitur Baru yang Akan Ditambahkan

Tambahkan section grafik nilai pada dashboard user.

Posisi grafik:

- Diletakkan di atas section card sesi ujian.
- Muncul setelah header atau ringkasan dashboard, jika ada.
- Muncul sebelum daftar card sesi ujian.

Isi grafik:

- Grafik menampilkan nilai dari setiap sesi ujian yang sudah dilakukan user.
- Setiap titik atau batang mewakili satu sesi ujian.
- Label grafik menggunakan nama sesi ujian atau tanggal sesi ujian.
- Nilai pada grafik menggunakan nilai yang diperoleh user.

Jenis grafik yang boleh digunakan:

- Grafik garis, cocok untuk menampilkan perkembangan nilai dari waktu ke waktu.
- Grafik batang, cocok untuk membandingkan nilai antar sesi ujian.

Rekomendasi awal:

- Gunakan grafik garis jika urutan waktu sesi ujian jelas.
- Gunakan grafik batang jika nama sesi ujian lebih penting untuk dibandingkan.
- Jika belum ada library grafik di project, gunakan library yang ringan dan mudah, misalnya Chart.js.

## Tahapan Implementasi

### 1. Pahami Struktur Project

Langkah pertama adalah memahami struktur project sebelum mengubah kode.

Yang harus dicek:

- Framework yang digunakan project.
- Lokasi file route untuk dashboard user.
- Lokasi controller atau handler yang mengirim data ke dashboard user.
- Lokasi file view dashboard user.
- Lokasi model yang berhubungan dengan user, sesi ujian, hasil ujian, atau nilai ujian.

Petunjuk umum untuk project Laravel:

- Cek route di folder `routes`.
- Cek controller di folder `app/Http/Controllers`.
- Cek model di folder `app/Models`.
- Cek view di folder `resources/views`.
- Cek asset frontend di folder `resources/js`, `resources/css`, atau file layout Blade.

Output dari tahap ini:

- Mengetahui file dashboard user yang harus diubah.
- Mengetahui sumber data sesi ujian dan nilai.
- Mengetahui apakah project sudah memakai library grafik atau belum.

### 2. Identifikasi Data Sesi Ujian User

Cari bagaimana dashboard user saat ini mengambil data sesi ujian.

Hal yang perlu dicari:

- Nama model untuk sesi ujian.
- Nama model untuk hasil ujian atau attempt ujian.
- Relasi antara user dan sesi ujian.
- Kolom yang menyimpan nilai user.
- Kolom tanggal pelaksanaan atau tanggal selesai ujian.

Contoh kemungkinan nama data:

- `ExamSession`
- `QuizSession`
- `TestSession`
- `ExamResult`
- `QuizResult`
- `Attempt`
- `UserExam`

Jangan langsung membuat model atau tabel baru jika data sebenarnya sudah tersedia. Gunakan data yang sudah dipakai oleh card sesi ujian.

Output dari tahap ini:

- Data nama sesi ujian bisa diambil.
- Data tanggal pelaksanaan bisa diambil.
- Data nilai user bisa diambil.
- Data hanya milik user yang sedang login.

### 3. Pastikan Card Sesi Ujian Tetap Jelas

Sebelum menambahkan grafik, pastikan section card sesi ujian tetap menampilkan data utama dengan jelas.

Checklist card sesi ujian:

- Nama sesi ujian tampil jelas.
- Tanggal pelaksanaan tampil jelas.
- Nilai user tampil jelas.
- Layout card rapi di desktop dan mobile.
- Jika tidak ada data, tampilkan empty state yang mudah dipahami.

Contoh empty state:

`Belum ada sesi ujian yang sudah diselesaikan.`

Jika card saat ini sudah cukup baik, jangan ubah terlalu banyak. Fokus hanya pada perapian kecil jika memang diperlukan.

Output dari tahap ini:

- Card sesi ujian tetap berfungsi.
- Informasi penting tidak hilang setelah grafik ditambahkan.

### 4. Siapkan Data untuk Grafik di Backend

Data grafik sebaiknya disiapkan dari controller atau backend agar view hanya fokus menampilkan.

Format data yang dibutuhkan grafik:

- Label sesi ujian.
- Nilai setiap sesi ujian.
- Tanggal sesi ujian jika diperlukan untuk sorting atau label tambahan.

Contoh struktur data yang bisa dikirim ke view:

```php
$scoreChartData = [
    'labels' => ['Sesi 1', 'Sesi 2', 'Sesi 3'],
    'scores' => [75, 82, 90],
];
```

Atau jika ingin lebih detail:

```php
$scoreChartData = [
    'labels' => $completedSessions->pluck('session_name'),
    'scores' => $completedSessions->pluck('score'),
    'dates' => $completedSessions->pluck('completed_at'),
];
```

Hal penting:

- Urutkan data berdasarkan tanggal pelaksanaan dari yang paling lama ke paling baru.
- Pastikan hanya sesi ujian milik user yang sedang login.
- Pastikan hanya sesi ujian yang sudah memiliki nilai yang masuk grafik.
- Jika nilai null, jangan masukkan ke grafik kecuali memang ada aturan khusus.

Output dari tahap ini:

- View dashboard menerima data grafik siap pakai.
- Data grafik sudah sesuai dengan user login.
- Data grafik sudah terurut dengan benar.

### 5. Tentukan Cara Render Grafik

Cek apakah project sudah memakai library grafik.

Jika sudah ada library grafik:

- Gunakan library yang sudah ada.
- Ikuti pola komponen atau file JavaScript yang sudah tersedia.
- Jangan menambahkan library baru jika tidak perlu.

Jika belum ada library grafik:

- Gunakan Chart.js karena sederhana dan umum digunakan.
- Tambahkan dependency melalui package manager jika project menggunakan npm.
- Alternatif sederhana: gunakan CDN hanya jika pola project memang mengizinkan CDN.

Rekomendasi untuk project modern:

- Jika project memakai Vite, install Chart.js via npm.
- Import Chart.js di file JavaScript dashboard atau file app utama.
- Hindari script inline terlalu panjang di Blade jika project sudah punya asset pipeline.

Output dari tahap ini:

- Diputuskan apakah memakai library grafik yang sudah ada atau Chart.js.
- Tidak ada duplikasi library grafik.

### 6. Tambahkan Section Grafik di View Dashboard

Tambahkan section grafik di file view dashboard user.

Posisi section:

- Di atas section card sesi ujian.
- Masih berada di dalam layout dashboard user.
- Tidak mengganggu heading atau menu dashboard.

Contoh struktur tampilan:

```html
<section class="mb-6">
    <div class="card">
        <div class="card-header">
            <h2>Grafik Nilai Sesi Ujian</h2>
            <p>Perkembangan nilai dari sesi ujian yang sudah kamu selesaikan.</p>
        </div>
        <div class="card-body">
            <canvas id="scoreChart"></canvas>
        </div>
    </div>
</section>
```

Catatan:

- Sesuaikan class CSS dengan style yang sudah digunakan project.
- Jangan membuat style baru berlebihan jika sudah ada komponen card.
- Gunakan bahasa yang konsisten dengan aplikasi.

Output dari tahap ini:

- Section grafik muncul di dashboard user.
- Posisi grafik berada sebelum card sesi ujian.

### 7. Kirim Data dari Backend ke JavaScript

Data dari backend perlu dikirim ke JavaScript dengan aman.

Jika menggunakan Blade Laravel, bisa gunakan `@json`.

Contoh:

```html
<script>
    window.scoreChartData = @json($scoreChartData);
</script>
```

Atau langsung di script inisialisasi grafik:

```js
const scoreChartData = @json($scoreChartData);
```

Hal penting:

- Jangan membangun JSON manual dengan string concatenation.
- Gunakan helper resmi seperti `@json` agar escaping aman.
- Pastikan jika data kosong, JavaScript tetap tidak error.

Output dari tahap ini:

- JavaScript menerima `labels` dan `scores`.
- Tidak ada error ketika data kosong.

### 8. Implementasikan Grafik

Buat inisialisasi grafik setelah element canvas tersedia.

Contoh konfigurasi Chart.js grafik garis:

```js
const chartElement = document.getElementById('scoreChart');

if (chartElement && window.scoreChartData) {
    new Chart(chartElement, {
        type: 'line',
        data: {
            labels: window.scoreChartData.labels,
            datasets: [{
                label: 'Nilai',
                data: window.scoreChartData.scores,
                borderWidth: 2,
                tension: 0.3,
            }],
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    suggestedMax: 100,
                },
            },
        },
    });
}
```

Jika menggunakan grafik batang, ubah `type` menjadi `bar`.

Checklist implementasi grafik:

- Grafik muncul jika data tersedia.
- Label sesuai dengan sesi ujian.
- Nilai sesuai dengan nilai pada card.
- Skala nilai mudah dipahami.
- Grafik responsive di layar kecil.
- Tidak ada error JavaScript di console.

Output dari tahap ini:

- Grafik nilai berhasil tampil di dashboard user.
- Grafik menampilkan data yang sama dengan data sesi ujian user.

### 9. Tangani Kondisi Data Kosong

Jika user belum pernah menyelesaikan sesi ujian, jangan tampilkan grafik kosong yang membingungkan.

Opsi tampilan:

- Tampilkan card informasi kosong.
- Sembunyikan canvas grafik.
- Tampilkan pesan singkat.

Contoh pesan:

`Grafik nilai akan muncul setelah kamu menyelesaikan sesi ujian.`

Hal penting:

- Dashboard tidak boleh error ketika data kosong.
- Section card sesi ujian juga harus memiliki empty state yang jelas.
- Jangan menampilkan grafik dengan label kosong dan nilai kosong tanpa penjelasan.

Output dari tahap ini:

- User baru tetap mendapat tampilan dashboard yang rapi.
- Tidak ada error backend atau frontend ketika data belum ada.

### 10. Perhatikan Urutan dan Konsistensi Data

Pastikan urutan data pada grafik sama dengan logika yang mudah dipahami user.

Rekomendasi:

- Grafik diurutkan dari sesi paling lama ke sesi paling baru.
- Card sesi ujian boleh diurutkan dari yang terbaru ke yang paling lama jika itu pola dashboard saat ini.
- Jika urutan grafik dan card berbeda, pastikan tetap masuk akal.

Contoh:

- Grafik: Januari, Februari, Maret.
- Card: Maret, Februari, Januari.

Keduanya masih wajar karena grafik menunjukkan perkembangan waktu, sedangkan card menampilkan riwayat terbaru lebih dulu.

Output dari tahap ini:

- Grafik mudah dibaca sebagai perkembangan nilai.
- Card tetap nyaman digunakan sebagai daftar riwayat sesi ujian.

### 11. Validasi Akses Data User

Pastikan data yang tampil hanya milik user yang sedang login.

Checklist keamanan:

- Query menggunakan `auth()->id()` atau mekanisme user login yang sudah dipakai project.
- User A tidak bisa melihat nilai milik User B.
- Jika ada route API untuk mengambil data grafik, route tersebut harus dilindungi middleware auth.
- Jangan mengirim data user lain ke frontend.

Output dari tahap ini:

- Data grafik aman dan sesuai user login.
- Data card sesi ujian juga tetap aman.

### 12. Rapikan UI dan Responsiveness

Pastikan tampilan grafik nyaman dilihat di desktop dan mobile.

Checklist UI:

- Section grafik punya judul yang jelas.
- Ada deskripsi singkat di bawah judul.
- Tinggi grafik tidak terlalu kecil.
- Grafik tidak overflow di layar mobile.
- Warna grafik kontras dan tetap sesuai tema aplikasi.
- Jarak antara grafik dan card sesi ujian cukup nyaman.

Contoh judul section:

`Grafik Nilai Sesi Ujian`

Contoh deskripsi:

`Lihat perkembangan nilai dari setiap sesi ujian yang sudah kamu selesaikan.`

Output dari tahap ini:

- Dashboard terlihat rapi.
- Grafik mudah dipahami oleh user.

### 13. Testing Manual

Lakukan testing manual minimal dengan beberapa kondisi data.

Skenario testing:

1. User belum punya sesi ujian selesai.
   - Dashboard tidak error.
   - Grafik tidak tampil kosong membingungkan.
   - Pesan empty state muncul.

2. User punya satu sesi ujian selesai.
   - Card menampilkan nama sesi, tanggal, dan nilai.
   - Grafik menampilkan satu data nilai.

3. User punya beberapa sesi ujian selesai.
   - Card menampilkan semua data sesuai aturan dashboard.
   - Grafik menampilkan semua nilai.
   - Urutan grafik sesuai tanggal pelaksanaan.

4. Ada sesi ujian tanpa nilai.
   - Sesi tersebut tidak masuk grafik, kecuali aturan aplikasi menyatakan lain.
   - Dashboard tetap tidak error.

5. Login sebagai user berbeda.
   - Data grafik berubah sesuai user yang login.
   - Tidak ada data user lain yang tampil.

Output dari tahap ini:

- Fitur sudah aman untuk diuji lebih lanjut.
- Bug umum pada data kosong dan data user lain sudah dicegah.

## Rekomendasi Detail Teknis

### Backend

- Ambil data dari sumber yang sama dengan card sesi ujian jika memungkinkan.
- Buat variable khusus untuk grafik, misalnya `$scoreChartData`.
- Urutkan data grafik berdasarkan tanggal pelaksanaan atau tanggal selesai ujian ascending.
- Filter data agar hanya mengambil sesi ujian yang sudah selesai dan memiliki nilai.

Contoh pseudocode:

```php
$completedSessions = $user->examSessions()
    ->whereNotNull('score')
    ->orderBy('completed_at')
    ->get();

$scoreChartData = [
    'labels' => $completedSessions->pluck('name'),
    'scores' => $completedSessions->pluck('score'),
];
```

Catatan:

- Sesuaikan nama relasi, kolom, dan model dengan struktur project sebenarnya.
- Jangan copy mentah pseudocode jika nama model berbeda.

### Frontend

- Tambahkan container grafik di view dashboard user.
- Gunakan `canvas` jika memakai Chart.js.
- Inisialisasi grafik hanya jika element canvas ditemukan.
- Gunakan data dari backend melalui JSON yang aman.
- Tambahkan empty state jika data grafik kosong.

### UI Copy

Gunakan teks sederhana dan mudah dipahami:

- Judul: `Grafik Nilai Sesi Ujian`
- Deskripsi: `Lihat perkembangan nilai dari setiap sesi ujian yang sudah kamu selesaikan.`
- Empty state: `Grafik nilai akan muncul setelah kamu menyelesaikan sesi ujian.`

## Acceptance Criteria

Fitur dianggap selesai jika semua poin berikut terpenuhi:

- Dashboard user tetap menampilkan section card sesi ujian.
- Card sesi ujian menampilkan nama sesi ujian, tanggal pelaksanaan, dan nilai dengan jelas.
- Section grafik nilai tampil di atas section card sesi ujian.
- Grafik menampilkan nilai dari sesi ujian yang sudah dilakukan user.
- Data grafik sesuai dengan data user yang sedang login.
- Data grafik tidak menampilkan sesi ujian user lain.
- Dashboard tidak error ketika user belum punya sesi ujian selesai.
- Ada empty state yang jelas ketika data grafik kosong.
- Tampilan grafik responsive di desktop dan mobile.
- Tidak ada error JavaScript di console browser.

## Estimasi Urutan Kerja yang Disarankan

1. Cari file dashboard user dan controller terkait.
2. Pahami query data card sesi ujian yang sudah ada.
3. Pastikan data card berisi nama sesi, tanggal, dan nilai.
4. Buat data `$scoreChartData` dari hasil sesi ujian user.
5. Kirim `$scoreChartData` ke view dashboard.
6. Tambahkan section grafik di atas section card sesi ujian.
7. Integrasikan Chart.js atau library grafik yang sudah tersedia.
8. Tambahkan empty state untuk data kosong.
9. Rapikan tampilan desktop dan mobile.
10. Test manual dengan user tanpa data, satu data, banyak data, dan user berbeda.

## Catatan untuk Junior Programmer atau AI Model

- Jangan mengubah fitur lain di luar dashboard user kecuali diperlukan.
- Jangan membuat tabel baru sebelum memastikan data nilai memang belum tersedia.
- Jangan menampilkan semua data ujian dari semua user.
- Selalu filter data berdasarkan user yang sedang login.
- Jika bingung memilih grafik, gunakan grafik garis terlebih dahulu.
- Jika project sudah punya komponen card atau style khusus, ikuti style yang sudah ada.
- Jika ada perbedaan nama model atau kolom, sesuaikan dengan kode project yang sebenarnya.
- Setelah implementasi, bandingkan nilai di grafik dengan nilai di card untuk memastikan datanya sama.
