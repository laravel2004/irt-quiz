# ISSUE: Lock Soal Saat Membuat Sesi Ujian

## Ringkasan

Tambahkan opsi `is_lock_quiz` pada form tambah/edit sesi ujian. Jika opsi ini aktif, soal yang terpilih saat generate menjadi milik lock sesi tersebut dan tidak boleh dipilih oleh sesi lain. Lock dapat dilepas kembali dengan mengedit sesi dan mematikan opsi tersebut.

Pemilihan soal tetap acak berdasarkan mata pelajaran, sub mata pelajaran, dan persentase. Form harus menampilkan jumlah soal yang masih tersedia pada setiap sub mata pelajaran serta perkiraan jumlah soal yang akan dipakai dari persentase yang diinput.

Pada setiap baris input sub mata pelajaran, jumlah soal yang akan dipakai wajib ditampilkan secara langsung. Contoh: jika total kategori 20 soal dan persentase sub mata pelajaran 25%, tampilkan `Total soal yang akan dipakai: 5`.

> **PENTING - DATABASE PRODUCTION:** Fitur ini akan dipasang pada aplikasi yang sudah production dan sudah memiliki data. Implementasi dilarang menjalankan `php artisan migrate:fresh`, `php artisan migrate:refresh`, `php artisan db:wipe`, menghapus tabel, atau mengedit migration lama yang sudah pernah dijalankan. Perubahan schema harus memakai migration incremental baru dan dijalankan dengan `php artisan migrate --force` setelah backup database.

## Tujuan Bisnis

- Admin dapat menentukan apakah soal pada suatu sesi bersifat eksklusif.
- Soal yang dikunci tidak muncul dalam hasil generate sesi berikutnya.
- Admin dapat membuka lock tanpa menghapus sesi atau soal.
- Admin mengetahui kapasitas soal sebelum menyimpan sesi.
- Jumlah soal hasil generate tepat sesuai total dan pembagian persentase.
- Jika stok tidak cukup, penyimpanan gagal dengan pesan yang jelas dan tidak meninggalkan data setengah jadi.

## Kondisi Codebase Saat Ini

Alur yang sudah ada:

1. Form tambah/edit sesi berada di `resources/views/admin/sessions/index.blade.php`.
2. Payload form dikirim sebagai JSON ke `ExamSessionController::store()` atau `ExamSessionController::update()`.
3. Validasi sesi berada di `app/Http/Controllers/Admin/ExamSessionController.php`.
4. `ExamSessionService::createWithCategories()` membuat sesi, konfigurasi mata pelajaran, konfigurasi sub mata pelajaran, lalu langsung memanggil `generateSessionQuestions()` di dalam transaksi.
5. `generateSessionQuestions()` memilih soal dan memasukkannya ke tabel pivot `session_questions`.
6. Generate ulang juga dapat dipanggil dari:
   - `ExamSessionController::previewQuestions()`
   - `ExamSessionController::previewQuestionsOnly()`
   - `ExamController::agreeTerms()` jika sesi belum memiliki soal
7. Edit sesi saat ini mengganti konfigurasi mata pelajaran/sub mata pelajaran, tetapi tidak meregenerate soal.
8. Pemilihan soal per sub mata pelajaran sudah mengacak soal dan mencoba meratakan pemilihan berdasarkan `kode_soal`.
9. Fallback saat stok kurang saat ini dapat mengambil soal dari sub mata pelajaran yang tidak dipilih dan bahkan menduplikasi soal. Perilaku ini harus dihapus karena bertentangan dengan fitur lock dan persentase.
10. Belum ada test khusus untuk generate soal atau CRUD sesi.

## Keputusan Desain Wajib

### 1. Simpan opsi lock pada sesi

Tambahkan kolom berikut ke `exam_sessions`:

```text
is_lock_quiz BOOLEAN NOT NULL DEFAULT FALSE
```

Default `false` menjaga perilaku sesi lama setelah migration dijalankan.

### 2. Simpan pemilik lock pada soal, bukan hanya boolean

Tambahkan kolom berikut ke `question_banks`:

```text
locked_by_exam_session_id BIGINT UNSIGNED NULL
FOREIGN KEY -> exam_sessions.id, ON DELETE SET NULL
```

Jangan menambah `question_banks.is_locked`. Boolean saja tidak dapat menjawab sesi mana yang memiliki lock dan berisiko membuat satu sesi membuka lock milik sesi lain.

Definisi status soal:

- Available: `locked_by_exam_session_id IS NULL`.
- Locked oleh sesi saat ini: `locked_by_exam_session_id = session.id`.
- Tidak boleh dipakai sesi saat ini: `locked_by_exam_session_id` berisi ID sesi lain.

`ON DELETE SET NULL` memastikan soal otomatis available bila sesi pemilik lock dihapus.

### 3. Semua jalur generate memakai aturan yang sama

Filter lock harus berada di `ExamSessionService::generateSessionQuestions()` dan helper query yang dipakai method tersebut. Jangan hanya menambah filter di controller create karena preview, regenerate, dan mulai ujian juga dapat memanggil generator.

Saat regenerate sesi yang sudah mengunci soal, soal milik sesi itu sendiri tetap boleh menjadi kandidat. Soal milik sesi terkunci lain tetap harus dikecualikan.

### 4. Kekurangan stok adalah error, bukan alasan menduplikasi soal

Generator harus menghasilkan tepat `total_questions` soal unik. Jika jumlah available pada salah satu alokasi tidak cukup, batalkan seluruh transaksi dan tampilkan pesan, misalnya:

```text
Soal tersedia untuk Aljabar hanya 3, sedangkan sesi membutuhkan 5 soal.
```

Jangan mengisi kekurangan dengan:

- soal locked;
- soal dari sub mata pelajaran yang tidak dipilih;
- ID soal yang sama lebih dari sekali.

### 5. Gunakan satu aturan pembulatan di backend dan frontend

Persentase dapat menghasilkan pecahan. Gunakan metode largest remainder agar total alokasi selalu sama dengan `total_questions`:

1. Hitung nilai mentah: `percentage / 100 * total_questions`.
2. Ambil `floor()` untuk setiap sub mata pelajaran.
3. Hitung sisa: `total_questions - jumlah seluruh floor`.
4. Bagikan sisa satu per satu kepada nilai dengan pecahan terbesar.
5. Jika nilai pecahan sama, gunakan urutan baris sub mata pelajaran sebagai tie-breaker agar hasil backend dan frontend konsisten.

Contoh: total 10 soal dengan persentase 33%, 33%, 34% harus menghasilkan 3, 3, dan 4 soal, bukan 3, 3, dan fallback 1 soal dari sub mata pelajaran lain.

## Aturan Perilaku

### Membuat sesi dengan `is_lock_quiz = false`

- Generator hanya mengambil soal available.
- Soal yang terpilih disimpan ke `session_questions`.
- `locked_by_exam_session_id` tetap `NULL`, sehingga soal boleh dipakai lagi oleh sesi lain.

### Membuat sesi dengan `is_lock_quiz = true`

- Generator hanya mengambil soal available.
- Setelah seluruh alokasi tervalidasi, simpan `session_questions`.
- Set `locked_by_exam_session_id` setiap soal terpilih ke ID sesi baru.
- Pembuatan sesi, pemilihan soal, dan penguncian harus berada dalam satu transaksi.

### Edit dari locked menjadi unlocked

- Pertahankan isi `session_questions` jika konfigurasi alokasi tidak berubah.
- Set semua `question_banks.locked_by_exam_session_id` milik sesi itu menjadi `NULL`.
- Jangan membuka soal yang dikunci sesi lain.

### Edit dari unlocked menjadi locked

- Generate ulang agar sesi hanya memperoleh soal yang dapat dikunci saat transaksi berjalan.
- Setelah berhasil, tandai semua soal hasil generate sebagai milik lock sesi tersebut.
- Jika stok tidak cukup, batalkan update dan pertahankan data sesi sebelum edit.

### Edit konfigurasi alokasi

Konfigurasi alokasi berarti kategori, sub kategori, persentase, atau `total_questions` berubah.

- Generate ulang menggunakan konfigurasi baru.
- Jika sesi tetap locked, soal lama milik sesi itu boleh dipilih kembali.
- Lepas lock soal lama yang tidak lagi terpilih.
- Kunci soal baru yang terpilih jika `is_lock_quiz = true`.
- Jika hanya nama, tanggal, waktu, durasi, atau skor yang berubah, jangan acak ulang soal.

### Sesi sudah mulai dikerjakan

Jika ada peserta dengan `started_at` terisi:

- Tolak perubahan kategori, sub kategori, persentase, jumlah soal, atau perubahan `is_lock_quiz` dari false menjadi true karena perubahan tersebut dapat mengganti soal peserta.
- Tetap izinkan perubahan dari true menjadi false karena tindakan ini hanya melepas reservasi dan tidak mengubah soal peserta.
- Tetap izinkan edit metadata yang tidak mengubah kumpulan soal.
- Tolak regenerate dari halaman preview.

### Menghapus sesi

- Relasi `session_questions` terhapus melalui cascade yang sudah ada.
- `locked_by_exam_session_id` otomatis menjadi `NULL` melalui foreign key baru.
- Tidak perlu menulis loop unlock manual pada controller delete.

## Tahapan Implementasi

## Tahap 1: Tambahkan Schema Lock

**File baru:** migration baru di `database/migrations/`.

Langkah:

1. Buat migration incremental baru. Jangan mengubah migration lama yang sudah pernah dijalankan di production.
2. Tambahkan `is_lock_quiz` boolean default `false` pada `exam_sessions`.
3. Tambahkan `locked_by_exam_session_id` nullable pada `question_banks`.
4. Buat foreign key ke `exam_sessions.id` dengan `nullOnDelete()`.
5. Pastikan method `down()` menghapus foreign key/kolom `locked_by_exam_session_id`, lalu menghapus `is_lock_quiz`.
6. Uji migration menggunakan salinan database production atau staging yang memiliki data. Pastikan data sesi, soal, peserta, jawaban, dan hasil ujian lama tidak berubah.
7. Sebelum deployment, buat backup database dan pastikan backup dapat direstore.
8. Di production, jalankan hanya `php artisan migrate --force`.
9. Dilarang menjalankan `migrate:fresh`, `migrate:refresh`, `db:wipe`, `DROP TABLE`, atau perintah reset database lainnya pada production.

**Acceptance criteria:**

- [ ] `php artisan migrate` berhasil pada database yang sudah memiliki data.
- [ ] Semua sesi lama memiliki nilai lock `false`.
- [ ] Seluruh data lama tetap ada dan jumlah record tabel utama tidak berubah setelah migration.
- [ ] Menghapus sesi pemilik lock membuat kolom lock pada soal menjadi `NULL`.
- [ ] `php artisan migrate:rollback` untuk migration baru berhasil.
- [ ] Deployment production tidak menggunakan perintah reset database.

## Tahap 2: Perbarui Model dan Query Availability

**File:**

- `app/Models/ExamSession.php`
- `app/Models/QuestionBank.php`

Langkah:

1. Tambahkan `is_lock_quiz` ke `$fillable` dan `$casts` sebagai boolean pada `ExamSession`.
2. Tambahkan relasi `lockedQuestions()` dari `ExamSession` ke `QuestionBank` menggunakan foreign key `locked_by_exam_session_id`.
3. Tambahkan relasi `lockedBySession()` pada `QuestionBank`.
4. Jangan masukkan `locked_by_exam_session_id` ke `$fillable`; status lock hanya boleh diubah oleh service, bukan payload CRUD soal.
5. Tambahkan local scope pada `QuestionBank`, misalnya `scopeAvailableForSession($query, ?int $sessionId = null)`:
   - create: hanya `locked_by_exam_session_id IS NULL`;
   - regenerate/edit: `NULL` atau lock dimiliki `$sessionId`.
6. Gunakan scope tersebut untuk semua query kandidat, termasuk fallback kategori tanpa konfigurasi sub mata pelajaran.

**Acceptance criteria:**

- [ ] Scope create tidak mengembalikan soal locked.
- [ ] Scope edit mengembalikan soal available dan soal milik sesi itu sendiri.
- [ ] Scope edit tidak mengembalikan soal milik sesi lain.

## Tahap 3: Buat Perhitungan Alokasi yang Deterministik

**File:** `app/Services/ExamSessionService.php`.

Langkah:

1. Buat satu private method kecil untuk menerima total soal dan daftar persentase, lalu mengembalikan jumlah soal per baris menggunakan largest remainder.
2. Pertahankan urutan konfigurasi `exam_session_sub_categories` sebagai tie-breaker.
3. Ganti penggunaan `round()` per sub mata pelajaran di `generateSessionQuestions()` dengan hasil method ini.
4. Jika sebuah kategori tidak memiliki konfigurasi sub mata pelajaran, ambil acak dari seluruh soal available dalam kategori tersebut untuk menjaga kompatibilitas sesi lama.
5. Jika kategori memiliki konfigurasi sub mata pelajaran, jangan mengambil soal dari sub mata pelajaran di luar daftar untuk menutup kekurangan.
6. Pertahankan logika pemerataan `kode_soal` yang sudah ada, tetapi jalankan hanya terhadap kumpulan soal available.
7. Hapus fallback yang menambahkan ID acak berulang kali saat stok unik kurang.

**Acceptance criteria:**

- [ ] Jumlah alokasi selalu sama dengan `total_questions` jika total persentase 100%.
- [ ] Kasus 10 soal dengan 33/33/34 menghasilkan 3/3/4.
- [ ] Semua soal hasil generate unik.
- [ ] Soal selalu berasal dari kategori dan sub mata pelajaran yang sesuai.

## Tahap 4: Jadikan Generate dan Lock Sebagai Operasi Atomik

**File:** `app/Services/ExamSessionService.php`.

Langkah:

1. Pastikan seluruh isi `generateSessionQuestions()` berjalan dalam `DB::transaction()`. Nested transaction dari `createWithCategories()` diperbolehkan oleh Laravel.
2. Ambil record sesi untuk update dan muat konfigurasi terbaru.
3. Ambil kandidat melalui `availableForSession($sessionId)` dan kunci row kandidat menggunakan `lockForUpdate()` sebelum finalisasi pilihan. Tujuannya mencegah dua request locked memilih soal yang sama secara bersamaan.
4. Hitung dan pilih semua soal terlebih dahulu. Jangan menghapus pivot lama sebelum semua alokasi lulus validasi stok.
5. Jika stok kurang, lempar exception yang berisi nama sub mata pelajaran, jumlah tersedia, dan jumlah dibutuhkan. Transaction harus rollback.
6. Setelah semua pilihan valid:
   - ganti isi `session_questions` dengan ID unik yang baru;
   - jika sesi locked, lepas lock lama milik sesi yang tidak terpilih dan klaim semua soal terpilih;
   - jika sesi unlocked, lepas seluruh lock yang masih dimiliki sesi tersebut.
7. Saat mengklaim lock, update hanya row yang `locked_by_exam_session_id IS NULL` atau sudah dimiliki sesi yang sama. Verifikasi jumlah row yang berhasil diklaim. Bila tidak sesuai, lempar exception dan rollback.
8. Jangan membuat service/factory baru; logika ini sudah menjadi tanggung jawab `ExamSessionService`.

**Catatan concurrency:** `lockForUpdate()` hanya efektif di dalam transaction. Jangan memindahkannya ke luar callback transaction.

**Acceptance criteria:**

- [ ] Kegagalan generate tidak menghapus soal lama sesi.
- [ ] Tidak ada sesi locked berbeda yang memiliki soal dengan ID sama.
- [ ] Regenerate sesi locked boleh mempertahankan soal yang sebelumnya dikunci sesi itu.
- [ ] Lock dan pivot tidak pernah tersimpan setengah jadi.

## Tahap 5: Validasi Request dan Aturan Update

**File:** `app/Http/Controllers/Admin/ExamSessionController.php` dan `app/Services/ExamSessionService.php`.

Langkah:

1. Tambahkan rule `is_lock_quiz => required|boolean` pada `store()` dan `update()`.
2. Tambahkan `distinct` untuk ID kategori dan ID sub kategori agar satu konfigurasi tidak dimasukkan dua kali.
3. Validasi bahwa setiap sub kategori benar-benar merupakan anak dari kategori pada baris yang sama. Rule `exists:sub_categories,id` saja belum menjamin hubungan ini.
4. Tetap validasi total persentase tepat 100 untuk kategori yang memiliki sub kategori.
5. Sebelum update, simpan snapshot konfigurasi lama dan nilai `is_lock_quiz` lama.
6. Bandingkan hanya field yang memengaruhi alokasi: kategori, sub kategori, persentase, dan `total_questions`.
7. Jalankan aturan edit berikut di dalam satu transaction:
   - alokasi berubah: simpan konfigurasi lalu generate ulang;
   - false menjadi true: generate ulang lalu lock;
   - true menjadi false tanpa perubahan alokasi: simpan sesi lalu release lock tanpa regenerate;
   - tidak ada perubahan alokasi/lock: jangan generate ulang.
8. Tambahkan pengecekan `participants.started_at` sebelum operasi yang dapat meregenerate soal.
9. Ubah kegagalan stok menjadi response HTTP 422 dengan pesan yang dapat ditampilkan langsung oleh form.
10. Gunakan data tervalidasi untuk update; jangan percaya field lock dari payload lain.

**Acceptance criteria:**

- [ ] Nilai `is_lock_quiz` tersimpan saat create dan edit.
- [ ] Edit metadata tidak mengubah `session_questions`.
- [ ] Unlock melepas hanya soal milik sesi yang diedit.
- [ ] Perubahan alokasi meregenerate soal sebelum ada peserta yang mulai.
- [ ] Perubahan berbahaya ditolak setelah peserta mulai.
- [ ] Payload sub kategori milik kategori lain ditolak dengan 422.

## Tahap 6: Tampilkan Jumlah Soal Available di Form

**File:** `app/Http/Controllers/Admin/ExamSessionController.php`.

Saat memuat halaman index, ubah query kategori agar setiap `subCategory` memiliki atribut:

```text
available_questions_count
```

Nilainya adalah jumlah `question_banks` pada sub kategori tersebut dengan `locked_by_exam_session_id IS NULL`.

Gunakan eager loading dan `withCount`; jangan menjalankan query baru dari dalam loop Blade atau JavaScript.

Untuk form create, angka ini adalah stok yang benar-benar dapat dipakai. Pada form edit sesi locked, soal milik sesi tersebut juga dapat dipakai kembali saat regenerate. Agar angka edit tidak menyesatkan, response `show()` perlu mengirim availability untuk sesi tersebut dengan aturan `NULL atau locked_by_exam_session_id = session.id`, atau mengirim count soal milik sesi per sub kategori lalu menambahkannya pada count global. Pilih satu pendekatan dan gunakan hasilnya saat membuka modal edit.

**Acceptance criteria:**

- [ ] Setiap pilihan sub mata pelajaran menampilkan teks seperti `Aljabar (12 soal tersedia)`.
- [ ] Count tidak memasukkan soal yang dikunci sesi lain.
- [ ] Pada edit sesi locked, count memasukkan soal yang dikunci oleh sesi itu sendiri.
- [ ] Halaman tidak menimbulkan query N+1.

## Tahap 7: Tambahkan Toggle dan Estimasi Soal pada UI

**File:** `resources/views/admin/sessions/index.blade.php`.

Langkah:

1. Tambahkan checkbox/toggle berlabel `Kunci soal untuk sesi ini` pada bagian Informasi Dasar Sesi.
2. Gunakan input checkbox native dengan ID `sIsLockQuiz`; jangan menambah library UI.
3. Default form create adalah tidak aktif, sesuai default database.
4. Saat membuka modal edit, isi toggle dari `session.is_lock_quiz`.
5. Tambahkan `is_lock_quiz: document.getElementById('sIsLockQuiz').checked` ke payload.
6. Ubah option sub mata pelajaran menjadi format `Nama (N soal tersedia)`.
7. Di setiap baris sub mata pelajaran, tambahkan elemen teks total pemakaian, misalnya `Total soal yang akan dipakai: 4` dan keterangan stok `12 soal tersedia`. Jangan hanya menampilkan total gabungan di bagian bawah kategori.
8. Buat satu fungsi JavaScript yang menghitung seluruh estimasi dalam satu kategori menggunakan aturan largest remainder yang sama dengan backend.
9. Panggil ulang fungsi estimasi ketika:
   - jumlah soal kategori berubah;
   - persentase berubah;
   - sub mata pelajaran dipilih;
   - baris sub mata pelajaran ditambah atau dihapus;
   - data edit dimuat.
10. Jika estimasi kebutuhan melebihi jumlah available, tampilkan teks merah dan cegah submit dengan pesan yang menyebut sub mata pelajaran terkait.
11. Tetap pertahankan validasi total persentase 100%; backend tetap menjadi sumber validasi utama.
12. Pastikan teks estimasi dapat turun baris pada layar kecil dan tidak mendorong tombol hapus keluar container.

**Acceptance criteria:**

- [ ] Toggle bekerja pada create dan edit.
- [ ] Payload selalu mengirim boolean, bukan string `"on"`.
- [ ] Count available terlihat setelah sub mata pelajaran dipilih.
- [ ] Setiap baris sub mata pelajaran menampilkan `Total soal yang akan dipakai: N`.
- [ ] Total pemakaian berubah langsung tanpa reload saat persentase atau jumlah soal kategori diubah.
- [ ] Jumlah estimasi seluruh sub mata pelajaran sama dengan total soal kategori.
- [ ] Form memberi peringatan sebelum submit jika stok tidak cukup.

## Tahap 8: Amankan Semua Pemanggil Generate

**File:**

- `app/Http/Controllers/Admin/ExamSessionController.php`
- `app/Http/Controllers/ExamController.php`

Langkah:

1. Pastikan kedua endpoint preview tetap memanggil method service yang sama.
2. Jika parameter `regenerate` digunakan, tolak regenerate ketika peserta sudah mulai.
3. `ExamController::agreeTerms()` boleh menjalankan generator hanya jika pivot benar-benar kosong; error stok harus ditangani dan tidak menghasilkan ujian parsial.
4. Jangan menduplikasi filter lock di tiga controller. Semua aturan availability tetap berada di service/model scope.
5. Pastikan pesan error 422 dari create/update tampil di toast form. Tambahkan handler untuk network error agar form tidak gagal diam-diam.

**Acceptance criteria:**

- [ ] Create, preview regenerate, dan fallback saat mulai ujian menggunakan aturan lock identik.
- [ ] Tidak ada jalur yang dapat memasukkan soal locked milik sesi lain.
- [ ] Error generate ditampilkan kepada admin dan tidak meninggalkan data parsial.

## Tahap 9: Tambahkan Test Terfokus

**File baru yang disarankan:** `tests/Feature/ExamSessionQuestionLockTest.php`.

Gunakan `RefreshDatabase`. Karena factory domain belum tersedia, buat record kategori, sub kategori, soal, dan sesi langsung dengan model atau query builder. Tidak perlu membuat factory baru hanya untuk test ini.

Minimal test cases:

1. Create sesi unlocked memilih soal available tetapi tidak mengisi `locked_by_exam_session_id`.
2. Create sesi locked mengisi pivot dan lock owner dengan ID sesi yang benar.
3. Sesi berikutnya tidak pernah memilih soal milik lock sesi lain.
4. Unlock membuat seluruh soal milik sesi tersebut available tanpa membuka lock sesi lain.
5. Hapus sesi locked membuat soal kembali available melalui foreign key.
6. Regenerate sesi locked boleh memakai soal miliknya sendiri dan tidak memakai lock sesi lain.
7. Alokasi 10 soal dengan 33/33/34 menghasilkan 3/3/4.
8. Semua ID hasil generate unik.
9. Stok sub mata pelajaran kurang menghasilkan 422/exception dan transaction rollback.
10. Edit metadata tidak mengganti pivot soal.
11. Edit alokasi sebelum ujian meregenerate soal.
12. Edit alokasi atau false-to-true setelah peserta mulai ditolak.
13. True-to-false setelah peserta mulai tetap melepas lock tanpa mengganti pivot.
14. Validasi menolak sub kategori yang bukan anak kategori terkait.
15. Count availability tidak menghitung soal locked sesi lain dan, dalam mode edit, menghitung soal milik sesi sendiri.

Perintah verifikasi:

```bash
php artisan test --filter=ExamSessionQuestionLockTest
php artisan test
npm run build
```

## Urutan Pengerjaan yang Disarankan

Kerjakan berurutan agar setiap tahap mudah diperiksa:

1. Migration dan model.
2. Test alokasi dan query availability.
3. Refactor generator untuk alokasi tepat dan stok kurang.
4. Tambahkan lock/unlock atomik.
5. Tambahkan validasi serta aturan update.
6. Tambahkan availability count dari backend.
7. Tambahkan toggle, label stok, dan estimasi pada Blade/JavaScript.
8. Amankan preview/regenerate/start exam.
9. Jalankan test penuh dan build frontend.

Catatan deployment production setelah seluruh verifikasi berhasil:

```bash
# Buat dan verifikasi backup database terlebih dahulu.
php artisan migrate --force
php artisan optimize:clear
```

Jangan memasukkan perintah `migrate:fresh`, `migrate:refresh`, atau `db:wipe` ke dokumentasi deployment, script CI/CD, maupun instruksi untuk operator production.

Commit kecil yang disarankan:

```text
feat: add exam question lock ownership schema
feat: exclude locked questions during session generation
feat: add quiz lock controls and availability estimates
test: cover exam session question locking
```

## Checklist Akhir

- [ ] Migration aman untuk data lama dan dapat di-rollback.
- [ ] Backup production dibuat dan diverifikasi sebelum migration.
- [ ] Tidak ada perintah reset database dalam langkah deployment.
- [ ] `is_lock_quiz` tersedia pada create dan edit.
- [ ] Soal locked memiliki satu pemilik yang jelas.
- [ ] Semua generator menghindari lock sesi lain.
- [ ] Hasil generate unik dan sesuai pembagian sub mata pelajaran.
- [ ] Kekurangan stok membatalkan transaksi dengan pesan jelas.
- [ ] Unlock dan delete sesi membuat soal available kembali.
- [ ] Count available dan estimasi UI memakai aturan yang konsisten dengan backend.
- [ ] Setiap input sub mata pelajaran menampilkan total soal yang akan dipakai.
- [ ] Edit metadata tidak mengacak ulang soal.
- [ ] Sesi yang sudah dikerjakan tidak dapat diregenerate.
- [ ] Test terfokus, seluruh test, dan build frontend berhasil.

## Di Luar Scope

- Riwayat siapa dan kapan lock/unlock dilakukan.
- Tombol bulk unlock banyak sesi.
- Dashboard audit lock.
- Perubahan desain bank soal selain menampilkan status availability di form sesi.
- Penambahan package baru.

Fitur tersebut baru perlu ditambahkan jika ada kebutuhan operasional terpisah.
