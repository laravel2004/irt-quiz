# Issue: Pemulihan Jawaban Tryout dan Submit Tahan Gangguan Cloudflare

**Tipe:** Reliability + bug fix + performance investigation

**Prioritas:** Kritis

**Target implementer:** Junior programmer / AI model berbiaya rendah

**Status:** Siap diimplementasikan

> Kerjakan task secara berurutan. Jangan menghapus jawaban dari browser sebelum server memberikan konfirmasi sukses yang valid. Jangan mengubah kapasitas server atau setting Cloudflare sebelum penyebab error dibuktikan dengan data.

## 1. Ringkasan Masalah

Ketika banyak peserta mengerjakan tryout bersamaan, sebagian peserta mendapat halaman error Cloudflare/host error. Kejadian paling sering dilaporkan saat peserta mengumpulkan jawaban.

Jawaban sebenarnya sudah disimpan ke `localStorage` oleh halaman ujian, tetapi implementasi sekarang belum aman:

1. Draft memakai key per peserta, belum per mata pelajaran.
2. Respons HTTP error yang masih berbentuk JSON tetap dapat menyebabkan draft dihapus.
3. Respons HTML dari Cloudflare membuat parsing JSON gagal tanpa pesan dan modal loading tidak berhenti.
4. Tidak ada `catch`, retry terbatas, atau tombol mencoba kembali.
5. Saat halaman dibuka setelah waktu habis, backend langsung menandai mata pelajaran selesai. Draft lokal dapat kehilangan kesempatan untuk dikirim.
6. Endpoint submit belum memvalidasi bahwa kategori dan soal benar-benar milik sesi/peserta tersebut.
7. Perhitungan jawaban dilakukan di dalam transaksi database, sehingga transaksi terbuka lebih lama daripada yang diperlukan.

Issue ini memperbaiki kehilangan jawaban terlebih dahulu, lalu mengumpulkan bukti dan mengurangi kemungkinan error origin saat submit massal.

## 2. Tujuan

1. Setiap perubahan jawaban peserta tersimpan di browser dan otomatis dipulihkan setelah refresh, reconnect, atau respons Cloudflare gagal.
2. Draft hanya dihapus setelah endpoint submit mengembalikan HTTP sukses dan payload sukses yang valid.
3. Submit dapat dicoba ulang dengan aman tanpa membuat jawaban ganda.
4. Peserta mendapat pesan yang jelas bahwa jawaban masih aman di perangkat ketika submit gagal.
5. Waktu habis tidak menutup kesempatan pengiriman draft yang sudah ada di perangkat.
6. Endpoint submit lebih aman, idempotent untuk payload yang sama, dan memegang transaksi database sesingkat mungkin.
7. Penyebab host error dapat ditentukan dari Cloudflare, log origin, penggunaan resource, database, dan hasil load test.
8. Perubahan kapasitas/infrastruktur hanya dilakukan jika hasil pengukuran membuktikannya perlu.

## 3. Di Luar Scope

- Jangan menambah package JavaScript, service worker, IndexedDB, tabel, repository, service, atau abstraction baru.
- Jangan memindahkan penyimpanan jawaban utama ke queue. Server harus memastikan jawaban sudah tersimpan secara durable sebelum mengirim respons sukses.
- Jangan membuat autosave ke server setiap kali peserta memilih jawaban pada tahap ini. Hal tersebut menambah traffic dan baru dipertimbangkan jika load test membuktikan perlu.
- Jangan cache route POST submit melalui Cloudflare.
- Jangan menaikkan timeout Cloudflare/PHP sebagai solusi pertama. Request submit seharusnya cepat, bukan dibiarkan berjalan mendekati timeout.
- Jangan menyimpan CSRF token, cookie, data pribadi, isi soal, atau jawaban benar di `localStorage`.

## 4. Kondisi Kode Saat Ini

### Frontend

File utama: `resources/views/exam/main.blade.php`.

- Jawaban disimpan ke `exam_answers_{participantId}`.
- Penanda ragu-ragu disimpan ke `exam_doubtfuls_{participantId}`.
- Data dibaca langsung dengan `JSON.parse(...)`; JSON rusak dapat menghentikan seluruh script.
- `autoSubmit()` memanggil `fetch()` lalu langsung menjalankan `res.json()`.
- Setelah JSON berhasil diparse, kode menghapus LocalStorage dan redirect tanpa memeriksa `res.ok` atau `data.status`.
- Tidak ada `.catch()`.
- Submit bisa dipanggil dari tombol selesai, timer, dan deteksi perpindahan tab tanpa satu guard bersama.

### Backend

File utama: `app/Http/Controllers/ExamController.php`.

- `submitCategory()` memakai bulk `UserAnswer::upsert()`. Pertahankan pola ini.
- Unique index `(participant_id, question_bank_id)` sudah tersedia. Ini adalah dasar retry yang idempotent.
- Query soal dan kalkulasi skor masih dilakukan di dalam `DB::transaction()`.
- Soal yang dikirim belum dibatasi ke daftar soal peserta dan kategori yang sedang dikerjakan.
- `main()` langsung mengisi `finished_at` ketika waktu sudah habis, sebelum draft browser sempat dikirim.
- Bila `answers` kosong, closure transaksi keluar sebelum `finish_category` diproses.
- `finishSession()` sudah mengirim kalkulasi IRT ke queue. Pertahankan; jangan kembalikan kalkulasi berat ke request web.

### Infrastruktur

- Server production menyediakan `2 vCPU`, `2 GB RAM`, dan `30 GB storage` khusus untuk project ini.
- Production compose memakai satu container aplikasi dengan batas `1 CPU / 512 MB`.
- MySQL dibatasi `1 CPU / 600 MB`, `max-connections=100`.
- Redis dipakai untuk cache, session, dan queue.
- Worker queue terpisah sudah tersedia.
- `k6-exam.js` saat ini memakai satu akun hardcoded untuk 50 virtual user. Hasilnya tidak mewakili banyak peserta nyata dan menimbulkan contention buatan pada satu peserta.

## 5. Keputusan Implementasi yang Wajib Diikuti

### 5.1 Format penyimpanan browser

Gunakan satu key per attempt dan mata pelajaran:

```text
exam_draft_v1:{participant_id}:{exam_session_category_id}
```

Value:

```json
{
  "version": 1,
  "answers": {
    "101": "0",
    "102": ["1", "3"],
    "103": {"0": "benar", "1": "salah"}
  },
  "doubtfuls": {
    "102": true
  },
  "updated_at": "2026-09-26T10:00:00.000Z"
}
```

Aturan:

- Buat tiga fungsi biasa di script yang sama: `loadDraft()`, `saveDraft()`, dan `clearDraft()`. Jangan membuat class atau file util baru.
- `saveDraft()` dipanggil setelah jawaban atau status ragu-ragu berubah.
- Semua akses LocalStorage dibungkus `try/catch` agar mode private, quota penuh, atau JSON rusak tidak mematikan halaman ujian.
- Saat restore, ambil hanya ID soal yang ada pada variable `questions` halaman saat ini. Abaikan data lain.
- Jika key baru belum ada, baca dua key lama (`exam_answers_{participantId}` dan `exam_doubtfuls_{participantId}`), filter untuk kategori saat ini, lalu gunakan sebagai draft. Ini mencegah kehilangan jawaban peserta yang sedang ujian ketika deployment dilakukan.
- Key lama dan key baru baru boleh dihapus setelah submit sukses.
- Tidak perlu TTL atau pembersihan global dalam issue ini. Participant ID berbeda pada setiap attempt, dan key dibersihkan setelah sukses.

### 5.2 Definisi submit sukses

Frontend hanya boleh menganggap submit berhasil bila kedua kondisi ini benar:

1. `response.ok === true`.
2. JSON response memiliki `status === "success"`.

Contoh response sukses:

```json
{
  "status": "success",
  "saved_count": 40,
  "category_finished": true
}
```

Respons HTML Cloudflare, JSON error, HTTP `4xx`, HTTP `5xx`, timeout, dan network error semuanya dianggap gagal. Pada semua kegagalan tersebut draft harus tetap ada.

### 5.3 Retry

- Gunakan payload yang sama pada setiap retry.
- Cegah submit paralel dengan boolean `isSubmitting` dan disable tombol submit selama request berjalan.
- Maksimum 3 percobaan otomatis: percobaan awal, lalu retry setelah sekitar 2 detik dan 5 detik.
- Retry otomatis hanya untuk network error, HTTP `408`, `429`, `502`, `503`, `504`, dan Cloudflare `520` sampai `524`.
- Jangan retry otomatis untuk `401`, `403`, `419`, atau `422`.
- Setelah semua retry gagal, tampilkan tombol `Coba Lagi` dan teks: `Jawaban Anda masih tersimpan di perangkat ini.`
- Jangan redirect dan jangan menghapus draft ketika gagal.
- Untuk `419`, jelaskan bahwa sesi login perlu diperbarui, tetapi draft tetap aman. Setelah login/refresh, halaman harus dapat restore draft.

### 5.4 Idempotensi backend

- Pertahankan unique index dan `UserAnswer::upsert()`.
- Payload sama yang dikirim berkali-kali harus menghasilkan data akhir yang sama, bukan baris duplikat.
- Jika request pertama sudah commit tetapi responsnya hilang di Cloudflare, request retry harus tetap mendapat sukses.
- Jika status kategori sudah `finished_at`, endpoint harus mengembalikan sukses dengan `already_finished: true` tanpa mengubah jawaban lagi. Lakukan pemeriksaan ini sebelum menolak sesi yang baru saja ditutup admin.
- Tidak perlu tabel idempotency atau UUID request baru. Upsert dan update status yang idempotent sudah cukup untuk kasus ini.
- Di dalam transaksi, lock row status kategori dengan `lockForUpdate()`, periksa kembali `finished_at`, lalu lakukan upsert. Ini mencegah dua request final yang berbarengan saling menimpa.
- Tandai kategori selesai di transaksi yang sama dan hanya setelah upsert jawaban berhasil.
- Kategori dengan nol jawaban tetap dapat diselesaikan; `finish_category` tidak boleh dilewati hanya karena array jawaban kosong.

### 5.5 Waktu habis

- `GET ExamController::main()` tidak boleh langsung mengisi `finished_at` hanya karena `remainingSeconds <= 0`.
- Tetap render halaman dengan `remainingSeconds = 0` agar script dapat memuat draft dan langsung menjalankan submit final.
- Saat waktu nol, kunci kontrol jawaban di UI agar jawaban tidak dapat diubah, lalu submit draft yang sudah dipulihkan.
- Jika submit gagal, tampilkan retry; jangan menjalankan loop tanpa batas.
- Backend tetap menerima retry selama status kategori belum selesai. Perilaku ini setara dengan endpoint saat ini yang belum menerapkan penolakan berdasarkan deadline.
- Penegakan deadline yang tahan manipulasi membutuhkan autosave server-side dan merupakan issue terpisah. Jangan menambahkannya diam-diam di sini karena berisiko membuang jawaban valid saat koneksi bermasalah.

### 5.6 Validasi dan otorisasi submit

Sebelum scoring:

1. Pastikan participant ditemukan dari user login dan code sesi.
2. Pastikan sesi aktif.
3. Pastikan `categoryId` adalah `ExamSessionCategory` milik sesi participant.
4. Pastikan status kategori milik participant tersedia dan sudah dimulai.
5. Pastikan setiap question ID adalah soal yang ditugaskan ke participant dan berasal dari kategori tersebut.
6. Validasi `answers` berupa object/array dan `finish_category` berupa boolean.
7. Tolak question ID asing dengan HTTP `422`; jangan diam-diam menyimpannya.

Jangan percaya LocalStorage. Data browser dapat diedit manual, jadi validasi backend tetap wajib.

### 5.7 Batas tanggung jawab perbaikan Cloudflare

Tulisan `host error` belum cukup untuk menentukan satu penyebab. Catat kode dan Ray ID karena tindak lanjut berbeda:

| Gejala | Kemungkinan utama | Pemeriksaan pertama |
|---|---|---|
| `520` | Respons origin tidak valid/crash | Log aplikasi dan restart container |
| `521` | Origin menolak koneksi | Status web server, port, firewall |
| `522` | Koneksi ke origin timeout | Saturasi worker/connection/backlog |
| `523` | Origin tidak terjangkau | DNS/routing origin |
| `524` | Origin terhubung tetapi respons terlalu lama | Durasi route, query lambat, lock database |
| `502/504` | Origin/proxy gagal atau timeout | Log web server/PHP dan penggunaan resource |

Jangan menebak satu solusi untuk seluruh kode di atas.

### 5.8 Baseline konfigurasi server 2 vCPU / 2 GB

Kapasitas tersebut cukup sebagai titik awal untuk batas `EXAM_CONCURRENT_LIMIT=30`, selama p95 submit memenuhi target dan kalkulasi IRT tidak memonopoli database. Jangan menaikkan batas peserta sebelum load test Task 8 lulus.

#### Alokasi container awal

Gunakan batas berikut sebagai baseline. Jangan menjumlahkan CPU sebagai jaminan kapasitas; semua container tetap berebut dua core fisik yang sama.

| Service | CPU limit | Memory limit | Catatan |
|---|---:|---:|---|
| `app` | `1.0` | `512M` | Pertahankan; batasi thread dan memory PHP |
| `mysql` | `1.0` | `600M` | Pertahankan buffer pool `256M` |
| `redis` | `0.5` | `300M` | Pertahankan sampai `used_memory_peak` dan `evicted_keys` diukur |
| `queue` | `0.5` | `256M` | Pertahankan satu worker; uji `0.25` hanya bila monitoring membuktikan queue mengambil jatah web |

Total batas memory saat ini sekitar `1.67 GB`, menyisakan sekitar `380 MB` untuk OS dan Docker. Jangan menaikkan limit container sebelum memastikan host tidak swap/OOM pada beban target.

#### FrankenPHP dan PHP web

FrankenPHP menyarankan `num_threads × memory_limit < available_memory`. Konfigurasi sekarang mengizinkan `256M` per request pada container `512M`, sehingga dua request dapat memenuhi seluruh limit sebelum overhead Caddy/PHP/OPcache dihitung.

Tambahkan pada environment service `app`:

```yaml
FRANKENPHP_CONFIG: |
  num_threads 2
  max_threads 2
  max_wait_time 10s
```

Ubah konfigurasi PHP pada image production menjadi:

```ini
memory_limit = 128M
max_execution_time = 60
max_input_time = 60
opcache.memory_consumption = 96
```

Aturan:

- Jangan menaikkan `max_execution_time`; submit normal harus selesai jauh di bawah 60 detik.
- Jangan aktifkan FrankenPHP worker mode dalam issue ini. Worker mode memerlukan audit state/memory leak untuk aplikasi long-running.
- Jika load test membuktikan antrean thread menjadi bottleneck dan peak memory app masih aman, uji `max_threads 3` sebagai eksperimen terpisah. Jangan langsung menerapkannya.
- Referensi resmi: [FrankenPHP performance tuning](https://frankenphp.dev/docs/performance/) dan [FrankenPHP configuration](https://frankenphp.dev/docs/config/).

#### Queue worker

Worker sekarang memakai timeout default 60 detik dan `--tries=3`, sedangkan Redis `retry_after` bernilai 90 detik. Kalkulasi IRT yang melewati timeout dapat dibunuh dan diulang, sehingga beban database terjadi berkali-kali.

Gunakan satu worker dengan baseline:

```yaml
queue:
  command: >
    php -d memory_limit=192M artisan queue:work
    --sleep=1
    --tries=2
    --backoff=30
    --timeout=180
    --memory=192
    --max-jobs=20
  environment:
    APP_ENV: production
    APP_DEBUG: "false"
    REDIS_QUEUE_RETRY_AFTER: 240
```

Aturan:

- `REDIS_QUEUE_RETRY_AFTER` wajib lebih besar daripada `--timeout` agar job yang masih berjalan tidak diberikan ke worker lain sebagai job duplikat.
- Hanya jalankan satu queue worker pada server ini.
- `--max-jobs=20` membuat worker direstart berkala untuk membatasi pertumbuhan memory proses long-running.
- Bila IRT masih melewati 180 detik, jangan terus menaikkan timeout. Pecah/optimalkan query dan transaksi `AssessmentService`.
- Pastikan `APP_ENV=production` dan `APP_DEBUG=false` berlaku pada service `app` dan `queue`, bukan hanya pada web app.

#### MySQL

Pertahankan nilai berikut sebagai baseline:

```text
innodb_buffer_pool_size = 256M
innodb_log_buffer_size = 16M
performance_schema = OFF
```

Ukur `Threads_connected`, `Max_used_connections`, slow query, dan lock wait. Setelah pengukuran, turunkan `max_connections` dari `100` ke `50` bila peak tetap di bawah `40`. Jangan menaikkan buffer pool atau koneksi hanya karena submit lambat; MySQL juga memakai memory tambahan per connection. Referensi resmi: [MySQL memory usage](https://dev.mysql.com/doc/refman/8.0/en/memory-use.html).

#### Redis

- Jangan mengecilkan memory Redis sebelum menjalankan `INFO MEMORY` dan `INFO STATS`.
- Pantau `used_memory_peak`, `evicted_keys`, dan panjang queue.
- Nilai `evicted_keys > 0` berbahaya karena satu Redis dipakai untuk cache, session, dan queue. Jika terjadi, pisahkan cache dari session/queue atau tambah memory berdasarkan hasil ukur.
- Jangan menambah queue worker untuk mengejar antrean sebelum beban MySQL aman.

#### Environment production dan log

Gunakan nilai berikut pada server:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-production.example
LOG_CHANNEL=daily
LOG_LEVEL=warning
LOG_DAILY_DAYS=14
EXAM_CONCURRENT_LIMIT=30
```

Tambahkan rotasi log Docker pada setiap service yang banyak menulis log:

```yaml
logging:
  driver: json-file
  options:
    max-size: "10m"
    max-file: "3"
```

Storage `30 GB` cukup untuk aplikasi ini bila log dirotasi. Tetap pasang alert disk pada `80%`, simpan backup database harian, dan jangan mengandalkan satu-satunya backup di disk server yang sama.

#### Network dan secret

- Hapus publikasi port Redis `6379`; Redis hanya perlu dapat diakses dari network internal Compose.
- Hapus publikasi port MySQL bila tidak diperlukan. Jika diperlukan untuk administrasi dari host, bind ke `127.0.0.1`, bukan semua interface.
- Hapus fallback password database yang tertulis di `docker-compose.yml`; production harus gagal start bila secret tidak tersedia.
- Gunakan user MySQL khusus aplikasi, bukan `root`.
- Cloudflare harus bypass cache untuk `/exam/*`, `/login`, `/dashboard/*`, dan `/keep-alive`. Cache hanya asset statis yang aman.
- Jangan menambahkan rate limit agresif pada route submit tryout.

#### Optimasi saat deployment

Setelah container baru aktif dan migration selesai:

```bash
php artisan optimize
php artisan queue:restart
```

Verifikasi bahwa app, queue, MySQL, dan Redis sehat setelah perintah tersebut. Jangan menjalankan cache configuration saat environment production belum lengkap.

## 6. Alur Akhir yang Diharapkan

1. Peserta membuka mata pelajaran.
2. Browser membaca draft key baru; bila tidak ada, browser mencoba key lama.
3. Jawaban valid langsung direstore dan UI menampilkan pemberitahuan singkat.
4. Setiap perubahan jawaban memanggil `saveDraft()`.
5. Peserta menekan submit atau timer mencapai nol.
6. UI dikunci dan payload dibuat dari draft di memory.
7. Server memvalidasi kepemilikan, menghitung skor di luar transaksi, lalu melakukan upsert dan finalisasi dalam transaksi pendek.
8. Jika server mengonfirmasi sukses, browser menghapus draft dan redirect.
9. Jika Cloudflare/network/server gagal, browser mempertahankan draft, melakukan retry terbatas, lalu memberi tombol coba lagi.
10. Refresh halaman memulihkan draft dan peserta dapat mengirim ulang.

## 7. Tahapan Implementasi

### Task 1 — Tambahkan regression test backend sebelum mengubah controller

**Deskripsi:** Buat test yang mengunci kontrak keamanan, idempotensi, dan finalisasi kategori.

**File:**

- `tests/Feature/ExamSubmissionRecoveryTest.php` (baru)

**Skenario minimum:**

- Submit jawaban valid menyimpan satu record dan menandai kategori selesai.
- Payload yang sama disubmit dua kali; jumlah `user_answers` tetap satu per soal dan respons kedua tetap sukses.
- Submit ulang dengan payload berbeda setelah kategori selesai tidak mengubah jawaban yang sudah final.
- Submit kosong dengan `finish_category=true` tetap menandai kategori selesai.
- Question ID milik kategori/sesi lain ditolak `422` dan tidak tersimpan.
- Category ID milik sesi lain ditolak `404` atau `422` secara konsisten.
- User lain tidak dapat menulis jawaban participant tersebut.

**Acceptance criteria:**

- [ ] Test gagal terhadap bug yang ada sebelum implementasi.
- [ ] Test tidak bergantung pada urutan data atau ID hardcoded.
- [ ] Test memeriksa database, bukan hanya body response.

**Verification:**

```bash
php artisan test --filter=ExamSubmissionRecoveryTest
```

**Dependencies:** Tidak ada.

**Estimasi:** M, 1 file.

### Task 2 — Perketat scope, validasi, dan transaksi submit

**Deskripsi:** Ubah `ExamController::submitCategory()` pada titik bersama agar seluruh caller mendapat perilaku yang sama.

**File:**

- `app/Http/Controllers/ExamController.php`
- `tests/Feature/ExamSubmissionRecoveryTest.php`

**Langkah:**

1. Scope kategori ke `participant->exam_session_id`.
2. Ambil status kategori participant yang benar.
3. Jika status sudah selesai, kembalikan sukses `already_finished` tanpa menulis ulang jawaban.
4. Validasi bentuk request.
5. Ambil hanya soal participant dalam kategori tersebut. Gunakan kolom yang diperlukan untuk scoring; jangan eager-load data yang tidak dipakai.
6. Tolak ID soal di luar kumpulan yang diizinkan.
7. Jalankan normalisasi dan kalkulasi skor sebelum membuka transaksi.
8. Di dalam transaksi, lock row status, periksa lagi `finished_at`, lakukan bulk upsert per chunk, lalu update `finished_at`.
9. Jangan `return` lebih awal saat jawaban kosong jika `finish_category=true`.
10. Kembalikan response sukses sesuai kontrak pada bagian 5.2.
11. Pertahankan helper scoring yang sudah ada; jangan menduplikasi rumus ke class baru.

**Acceptance criteria:**

- [ ] Semua test Task 1 lulus.
- [ ] Request ulang tidak membuat duplikat.
- [ ] Request yang datang setelah kategori selesai mendapat sukses tanpa mengubah jawaban final.
- [ ] Kategori tidak selesai bila penyimpanan jawaban gagal.
- [ ] Transaksi hanya membungkus operasi tulis.
- [ ] Soal asing tidak dapat disimpan atau dinilai.

**Verification:**

```bash
php artisan test --filter=ExamSubmissionRecoveryTest
```

**Dependencies:** Task 1.

**Estimasi:** M, 2 file.

### Task 3 — Satukan penyimpanan dan restore LocalStorage

**Deskripsi:** Ganti akses LocalStorage tersebar dengan tiga fungsi kecil di script Blade yang sama.

**File:**

- `resources/views/exam/main.blade.php`

**Langkah:**

1. Tambahkan `draftKey` sesuai bagian 5.1.
2. Implementasikan `loadDraft()`, `saveDraft()`, dan `clearDraft()` dengan `try/catch`.
3. `loadDraft()` memvalidasi `version`, bentuk `answers/doubtfuls`, dan question ID yang ada pada halaman.
4. Tambahkan fallback key lama untuk deployment ketika ujian sedang berjalan.
5. Ganti seluruh `localStorage.setItem()` langsung dengan `saveDraft()`.
6. Bila restore menemukan data valid, render jawaban seperti biasa dan tampilkan toast `Jawaban sebelumnya berhasil dipulihkan dari perangkat ini.`
7. Bila storage tidak tersedia, tampilkan warning yang tidak menutup ujian: `Penyimpanan lokal tidak tersedia. Jangan refresh halaman sebelum submit berhasil.`

**Acceptance criteria:**

- [ ] Refresh mengembalikan pilihan untuk semua tipe soal.
- [ ] Status ragu-ragu ikut kembali.
- [ ] Draft kategori A tidak masuk ke kategori B.
- [ ] JSON rusak tidak membuat halaman blank/error JavaScript.
- [ ] Draft legacy yang sesuai kategori tetap dapat dipulihkan.
- [ ] Tidak ada token atau data pribadi dalam value LocalStorage.

**Verification manual:**

1. Isi masing-masing tipe soal.
2. Buka DevTools > Application > Local Storage dan periksa value.
3. Refresh halaman.
4. Pastikan pilihan dan ragu-ragu kembali.
5. Isi value dengan JSON rusak lalu refresh; halaman harus tetap dapat digunakan dan warning tampil.

**Dependencies:** Tidak bergantung pada Task 2, tetapi selesaikan sebelum Task 4.

**Estimasi:** S, 1 file.

### Task 4 — Buat submit frontend tahan network/Cloudflare error

**Deskripsi:** Perbaiki `autoSubmit()` agar hanya membersihkan draft setelah acknowledgment server yang benar.

**File:**

- `resources/views/exam/main.blade.php`

**Langkah:**

1. Tambahkan guard `isSubmitting` untuk semua sumber submit.
2. Disable tombol dan kontrol yang relevan selama submit.
3. Periksa status HTTP sebelum menerima response.
4. Parse JSON dengan aman; respons non-JSON dianggap error.
5. Terapkan retry terbatas sesuai bagian 5.3.
6. Panggil `clearDraft()` hanya setelah HTTP sukses dan `data.status === 'success'`.
7. Pada kegagalan akhir, tutup loading, aktifkan tombol retry, dan pertahankan draft.
8. Tangani kondisi browser offline tanpa loop retry agresif.
9. Gunakan fungsi submit yang sama untuk tombol selesai, timer, dan pelanggaran tab.

**Acceptance criteria:**

- [ ] HTTP `500` JSON tidak menghapus draft.
- [ ] HTML error Cloudflare tidak menghapus draft dan tidak meninggalkan spinner tanpa akhir.
- [ ] Network offline tidak menghapus draft.
- [ ] HTTP sukses dengan `{status:"error"}` tidak menghapus draft.
- [ ] Hanya respons sukses valid yang menghapus key baru dan key legacy.
- [ ] Klik ganda/timer bersamaan menghasilkan satu rangkaian submit aktif.
- [ ] Tombol `Coba Lagi` menggunakan draft yang sama.

**Verification manual dengan DevTools:**

- Network > Offline, submit, pastikan key tetap ada.
- Gunakan request blocking untuk route submit, pastikan retry berhenti dan tombol muncul.
- Mock/ubah endpoint sementara di local agar mengembalikan HTML `502`, pastikan tidak ada uncaught JSON error.
- Kembalikan endpoint normal, klik `Coba Lagi`, pastikan sukses, key hilang, dan redirect terjadi.

**Dependencies:** Task 2 dan Task 3.

**Estimasi:** M, 1 file.

### Checkpoint A — Recovery jawaban end-to-end

- [ ] Test backend lulus.
- [ ] Refresh sebelum submit memulihkan jawaban.
- [ ] Gagal submit mempertahankan jawaban.
- [ ] Retry setelah koneksi pulih berhasil.
- [ ] Draft baru dihapus hanya setelah sukses.
- [ ] Tidak ada error JavaScript di console.

### Task 5 — Pulihkan submit setelah waktu habis

**Deskripsi:** Hilangkan jalur yang menutup kategori sebelum browser mengirim draft lokal.

**File:**

- `app/Http/Controllers/ExamController.php`
- `resources/views/exam/main.blade.php`
- `tests/Feature/ExamSubmissionRecoveryTest.php`

**Langkah:**

1. Di `main()`, ganti update `finished_at` saat waktu habis dengan render halaman menggunakan nol detik.
2. Kirim flag Blade yang menunjukkan waktu sudah habis bila diperlukan; jangan membuat endpoint baru.
3. Setelah `loadDraft()`, disable seluruh input jawaban bila waktu nol.
4. Langsung panggil fungsi submit final yang sama.
5. Bila submit gagal, tampilkan tombol retry tanpa membuka kembali input jawaban.
6. Tambahkan test bahwa membuka halaman saat waktu habis tidak langsung menandai kategori selesai.

**Acceptance criteria:**

- [ ] Draft dipulihkan sebelum auto-submit waktu habis dijalankan.
- [ ] Peserta tidak dapat mengubah jawaban setelah waktu nol.
- [ ] Kegagalan submit pada waktu nol tidak menghapus draft.
- [ ] Refresh masih memberi kesempatan retry selama kategori belum berhasil diselesaikan.
- [ ] Setelah sukses, kategori selesai dan participant kembali ke daftar kategori.

**Dependencies:** Checkpoint A.

**Estimasi:** M, 3 file.

### Task 6 — Perbaiki error handling akhir sesi

**Deskripsi:** `confirmFinish()` pada halaman kategori juga harus keluar dari loading ketika request gagal.

**File:**

- `resources/views/exam/categories.blade.php`

**Langkah:**

1. Periksa `response.ok` dan `data.status`.
2. Tambahkan `.catch()` dan tombol `Coba Lagi`.
3. Disable tombol selama request untuk mencegah klik ganda.
4. Jangan mengubah queue IRT; `finishSession()` sudah menggunakannya.

**Acceptance criteria:**

- [ ] Error network/Cloudflare menampilkan pesan dan tidak membuat loading tanpa akhir.
- [ ] Peserta dapat mencoba lagi.
- [ ] Sukses tetap menuju halaman hasil.

**Dependencies:** Tidak ada perubahan backend tambahan.

**Estimasi:** S, 1 file.

### Task 7 — Tambahkan observability submit tanpa mencatat isi jawaban

**Deskripsi:** Sediakan bukti untuk membedakan masalah aplikasi, database, resource origin, dan Cloudflare.

**File:**

- `app/Http/Controllers/ExamController.php`
- Opsional `app/Http/Middleware/...` hanya jika logging yang sama memang diperlukan oleh banyak route; untuk issue ini logging lokal di submit lebih sederhana.

**Data log yang diizinkan:**

- `participant_id`
- `exam_session_id`
- `exam_session_category_id`
- jumlah jawaban
- durasi total dan durasi transaksi dalam milidetik
- status sukses/gagal
- exception class
- header `CF-Ray` bila tersedia

**Larangan:** Jangan log isi jawaban, correct answer, cookie, CSRF token, password, atau seluruh request body.

**Aturan volume:** Log warning untuk submit lambat (awal: `>= 2000 ms`) dan log error untuk exception. Tidak perlu log info untuk setiap submit sukses jika menambah beban disk.

**Acceptance criteria:**

- [ ] Submit lambat dapat dikorelasikan dengan participant/category dan Ray ID.
- [ ] Exception tercatat tanpa data sensitif.
- [ ] Logging tidak mengubah response sukses.

**Dependencies:** Task 2.

**Estimasi:** S, 1 file.

### Checkpoint B — Aplikasi siap diuji beban

- [ ] Recovery dan retry lulus QA.
- [ ] Transaksi submit sudah pendek.
- [ ] Logging cukup untuk mencari request lambat/gagal.
- [ ] `php artisan test` lulus.
- [ ] `npm run build` lulus.

### Task 8 — Perbaiki skenario load test agar mewakili pengguna nyata

**Deskripsi:** Ubah `k6-exam.js`; jangan menguji 50 VU dengan satu participant yang sama.

**File:**

- `k6-exam.js`
- File data akun test lokal bila diperlukan, tetapi jangan commit credential production.

**Langkah:**

1. Ambil `BASE_URL`, code sesi, dan parameter test dari environment variable k6.
2. Gunakan satu akun/participant unik per VU.
3. Gunakan format JSON submit yang sama dengan browser saat ini.
4. Sediakan data sesi khusus staging dengan jumlah soal dan kategori mendekati production.
5. Ramp bertahap, misalnya 10, 25, 50, lalu 100 VU; jangan langsung menghantam production.
6. Fokuskan satu skenario pada submit serentak karena itu titik masalah.
7. Catat status code, `http_req_failed`, p95, p99, dan durasi endpoint submit.

**Target awal:**

- error rate `< 1%`
- p95 submit `< 2 detik`
- p99 submit `< 5 detik`
- tidak ada restart/OOM container
- koneksi database tidak mendekati batas maksimum

Target boleh disesuaikan setelah baseline, tetapi request tidak boleh mendekati timeout Cloudflare.

**Acceptance criteria:**

- [ ] Tidak ada akun yang dipakai oleh dua VU bersamaan.
- [ ] Payload sama dengan kontrak endpoint aktual.
- [ ] Report memuat jumlah VU saat error mulai muncul.
- [ ] Test dilakukan di staging atau waktu maintenance dengan izin eksplisit.

**Dependencies:** Checkpoint B dan data test.

**Estimasi:** M, 1–2 file.

### Task 9 — Diagnosis Cloudflare dan origin berdasarkan bukti

**Deskripsi:** Jalankan load test sambil memonitor seluruh jalur request.

**Checklist pengamatan pada timestamp yang sama:**

- [ ] Kode error Cloudflare, URL, waktu, dan Ray ID dicatat.
- [ ] Cloudflare Analytics/Events diperiksa.
- [ ] Log aplikasi dan log web server/origin diperiksa.
- [ ] `docker compose ps` tidak menunjukkan restart/unhealthy.
- [ ] CPU dan memory app, queue, Redis, dan MySQL diamati dengan `docker stats` atau monitoring setara.
- [ ] Koneksi aktif, lock wait, dan slow query MySQL diperiksa.
- [ ] Panjang antrean queue dan failed jobs diperiksa.
- [ ] Durasi route submit dibandingkan dengan data k6.
- [ ] Kapasitas disk dan I/O diperiksa bila log atau database tersendat.

**Keputusan berdasarkan hasil:**

- Bila route lambat tetapi resource longgar: profile query/kode submit dan perbaiki bottleneck yang terukur.
- Bila app CPU/memory penuh atau container restart: tambah resource/instance sesuai headroom host.
- Bila worker web habis tetapi DB sehat: tune jumlah worker FrankenPHP berdasarkan CPU dan memory hasil test.
- Bila DB connection/lock penuh: periksa transaksi/query/index sebelum menaikkan `max-connections`.
- Bila `522/523` tanpa request mencapai origin: periksa firewall, DNS origin, routing, dan allowlist IP Cloudflare.
- Bila `524` dan request terlihat lama di origin: perbaiki durasi aplikasi/database; jangan sekadar menaikkan timeout.
- Bila queue berat mengganggu web: kurangi concurrency queue atau pisahkan resource host berdasarkan hasil pengukuran.

**Output wajib:** Tambahkan komentar hasil pengujian ke issue/PR: error code, bottleneck, grafik/angka sebelum-sesudah, dan perubahan yang dipilih.

**Dependencies:** Task 8.

**Estimasi:** M; dapat membutuhkan akses Cloudflare dan server production/staging.

### Task 10 — Terapkan baseline server dan perubahan kapasitas yang terbukti perlu

Setting correctness/security pada bagian 5.8 wajib diterapkan. Perubahan kapasitas dan jumlah thread di luar baseline tetap kondisional berdasarkan Task 9.

**Kemungkinan file:**

- `docker-compose.yml`
- Konfigurasi deployment/Cloudflare di luar repository

**Aturan:**

1. Rekam baseline sebelum mengubah resource: p95/p99 submit, error rate, peak memory, restart/OOM, koneksi MySQL, lock wait, dan `evicted_keys` Redis.
2. Terapkan `APP_ENV`, `APP_DEBUG`, rotasi log, pembatasan port, dan secret production pada bagian 5.8.
3. Terapkan batas thread/memory PHP serta konfigurasi queue/`retry_after` pada bagian 5.8.
4. Ubah satu bottleneck yang sudah terbukti, lalu ulangi test yang sama.
5. Sisakan minimal sekitar 20% headroom CPU dan memory pada beban target.
6. Jangan menaikkan jumlah web thread atau queue worker melebihi kemampuan memory/database.
7. Jika menambah replica app, semua replica tetap memakai Redis dan MySQL bersama; session tidak boleh tersimpan lokal container.
8. Dokumentasikan rollback untuk setiap perubahan deployment.

**Acceptance criteria:**

- [ ] Hasil sesudah perubahan dibandingkan dengan baseline yang sama.
- [ ] Web app memakai maksimal dua thread dan memory PHP `128M`, kecuali angka load test membuktikan konfigurasi lain lebih baik.
- [ ] Queue memakai satu worker, `retry_after > timeout`, dan environment production.
- [ ] Log Laravel dan Docker memiliki rotasi.
- [ ] Redis tidak diekspos ke internet dan MySQL tidak memakai user root untuk aplikasi.
- [ ] Target error rate dan latency tercapai.
- [ ] Tidak ada OOM/restart atau lonjakan lock/koneksi database.
- [ ] Rollback sudah diuji atau minimal ditulis dengan perintah yang jelas.

**Dependencies:** Temuan Task 9.

**Estimasi:** Tidak dapat ditentukan sebelum diagnosis.

### Task 11 — Regression test dan QA akhir

**Automated:**

```bash
php artisan test --filter=ExamSubmissionRecoveryTest
php artisan test
npm run build
vendor/bin/pint --test
```

**Matriks QA manual:**

| Skenario | Hasil yang wajib |
|---|---|
| Refresh saat mengerjakan | Semua tipe jawaban dan ragu-ragu kembali |
| Browser offline saat submit | Draft tetap ada, pesan aman tampil |
| Cloudflare/HTML `5xx` | Tidak ada error JSON mentah, draft tetap ada |
| JSON error `500` | Draft tetap ada |
| CSRF `419` | Draft tetap ada dan instruksi login/refresh tampil |
| Validasi `422` | Tidak retry otomatis, draft tetap ada |
| Request sukses | Draft dihapus lalu redirect |
| Respons sukses hilang lalu retry | Tidak ada duplikat, retry sukses |
| Waktu habis | Input terkunci, draft direstore lalu disubmit |
| Waktu habis + offline | Draft tetap ada dan dapat dicoba ulang |
| Dua tab/klik ganda | Tidak ada request paralel dari tab yang sama; data server tetap satu per soal |
| LocalStorage tidak tersedia | Ujian tetap berjalan dengan warning |
| Akhiri seluruh sesi gagal | Loading berhenti dan tombol retry tersedia |

**Acceptance criteria:**

- [ ] Seluruh test otomatis lulus.
- [ ] Seluruh matriks QA lulus di desktop dan mobile.
- [ ] Tidak ada error console.
- [ ] Tidak ada jawaban sensitif di log server.
- [ ] Load test memenuhi target atau bottleneck tersisa terdokumentasi jelas.

**Dependencies:** Task 1–9 dan Task 10 bila diperlukan.

**Estimasi:** M.

## 8. Urutan Dependensi

```text
Task 1 (test backend)
  -> Task 2 (backend aman dan idempotent)
       -> Task 4 (submit + retry)
       -> Task 7 (observability)

Task 3 (LocalStorage + restore)
  -> Task 4
       -> Checkpoint A
            -> Task 5 (waktu habis)

Task 6 (akhir sesi) dapat dikerjakan setelah Checkpoint A.

Task 2 + Task 4 + Task 5 + Task 7
  -> Checkpoint B
       -> Task 8 (load test)
            -> Task 9 (diagnosis)
                 -> Task 10 hanya bila terbukti perlu
                      -> Task 11 (QA akhir)
```

Jangan mengerjakan Task 4 dan Task 2 secara terpisah di branch berbeda tanpa menyepakati kontrak response terlebih dahulu.

## 9. Risiko dan Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Draft lama bercampur antarkategori | Jawaban salah terkirim | Key per kategori dan filter question ID |
| Respons pertama commit tetapi hilang | Peserta retry dan duplikat | Unique index + upsert + finalisasi idempotent |
| LocalStorage dimodifikasi user | Soal asing/data tidak valid | Validasi kepemilikan dan tipe di backend |
| Deployment saat ujian aktif | Key lama tidak terbaca | Fallback/migrasi dua key legacy |
| Retry memperbesar beban origin | Gangguan makin berat | Maksimal 3 percobaan dengan jeda; setelah itu manual |
| Log membocorkan jawaban | Risiko privasi/keamanan | Log metadata saja, tanpa payload |
| Perubahan resource tanpa diagnosis | Biaya naik, masalah tetap ada | Task 9 wajib sebelum Task 10 |
| Waktu habis saat offline | Submit tertunda | Kunci input, simpan draft, izinkan retry hingga sukses |

## 10. Rencana Deployment dan Rollback

1. Jalankan test dan QA di staging.
2. Deployment idealnya di luar jam tryout aktif.
3. Karena ada fallback key lama, deployment tetap tidak boleh membuang draft peserta yang sudah membuka halaman.
4. Pantau error rate, p95/p99 submit, log slow submit, restart container, dan koneksi database selama tryout pertama.
5. Pantau `used_memory_peak`/`evicted_keys` Redis, penggunaan disk, dan failed jobs queue.
6. Jalankan `php artisan optimize` dan restart queue setelah environment production lengkap.
7. Jika frontend baru bermasalah, rollback asset/Blade dan controller ke release sebelumnya. Tidak ada migration database dalam issue ini.
8. Jangan rollback unique index `user_answers`; index tersebut dibutuhkan untuk mencegah duplikat.

## 11. Definition of Done

- [ ] Jawaban dan ragu-ragu tersimpan per participant dan per kategori.
- [ ] Semua tipe jawaban berhasil direstore setelah refresh.
- [ ] Draft hanya terhapus setelah HTTP dan JSON sama-sama menyatakan sukses.
- [ ] Network/Cloudflare/JSON error tidak menghilangkan draft.
- [ ] Peserta dapat mencoba submit kembali tanpa data duplikat.
- [ ] Waktu habis tidak menandai kategori selesai sebelum draft sempat dikirim.
- [ ] Endpoint hanya menerima soal yang sah untuk participant dan kategori tersebut.
- [ ] Transaksi database hanya membungkus operasi tulis.
- [ ] Finish session tidak macet pada loading saat request gagal.
- [ ] Test backend, seluruh test suite, build, dan formatter lulus.
- [ ] Load test memakai participant unik dan payload aktual.
- [ ] Kode error Cloudflare dan bottleneck origin sudah dibuktikan, bukan diasumsikan.
- [ ] Baseline server 2 vCPU/2 GB pada bagian 5.8 diterapkan dan diverifikasi dengan load test.
- [ ] Queue tidak mengulang job IRT hanya karena `timeout` lebih pendek daripada `retry_after`.
- [ ] Log memiliki rotasi dan service database/cache tidak terbuka ke internet.
- [ ] Perubahan kapasitas, bila ada, menunjukkan perbaikan terukur dan memiliki rollback.

## 12. Catatan untuk Implementer

Solusi minimum yang benar cukup memakai API browser `localStorage`, `fetch`, bulk upsert Laravel, unique index yang sudah ada, dan test Laravel. Jangan menambah dependency atau arsitektur baru. Jika setelah semua task aplikasi lolos load test tetapi Cloudflare masih error, bawa bukti Ray ID dan log origin ke konfigurasi jaringan/hosting; jangan menambah retry tanpa batas di browser.
