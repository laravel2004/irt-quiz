# Issue: Pengaturan Skor Peserta per Sesi Ujian

## Ringkasan

Tambahkan pengaturan pada form pembuatan dan pengeditan sesi ujian agar admin dapat memilih skor yang boleh dilihat peserta:

- `raw`: peserta hanya melihat skor raw.
- `irt`: peserta hanya melihat skor IRT.
- `both`: peserta melihat skor raw dan skor IRT.

Pengaturan ini hanya mengatur tampilan dan pengalaman peserta. Sistem harus tetap menghitung dan menyimpan kedua jenis skor. Predikat selalu mengikuti jenis skor: skor raw memakai Predikat Raw dan skor IRT memakai Predikat IRT. Role `superadmin` dan `admin_sesi` harus selalu dapat melihat kedua skor beserta kedua predikat, apa pun pengaturan sesinya.

## Tujuan

1. Admin dapat menentukan jenis skor peserta ketika membuat sesi ujian.
2. Pengaturan yang sama dapat dilihat dan diubah ketika mengedit sesi, selama sesi belum mulai dikerjakan.
3. Semua halaman peserta konsisten menampilkan hanya skor yang diizinkan oleh sesi.
4. Semua halaman admin dan file ekspor tetap menampilkan skor raw, skor IRT, Predikat Raw, dan Predikat IRT.
5. Sesi lama tetap berfungsi tanpa perlu diedit ulang.

## Keputusan Implementasi

Keputusan berikut dipakai sebagai kontrak agar implementor tidak perlu menebak:

1. Nama kolom database: `participant_score_display` pada tabel `exam_sessions`.
2. Nilai yang valid hanya `raw`, `irt`, dan `both`.
3. Gunakan kolom `string`, bukan database `enum`, lalu validasi nilainya di Laravel. Ini lebih mudah dimigrasikan dan diuji.
4. Default database adalah `irt`, karena perilaku peserta saat ini hanya menampilkan skor IRT. Dengan demikian, sesi lama tidak berubah perilakunya.
5. Nilai default pada form tambah sesi juga `irt`.
6. Setting dikunci setelah ada peserta dengan `started_at` terisi, sama seperti konfigurasi penilaian yang sudah ada. Perubahan sebelum ujian dimulai diperbolehkan.
7. `ExamResult::score` dan `ExamCategoryResult::score` adalah skor raw.
8. `ExamResult::irt_score` dan `ExamCategoryResult::irt_score` adalah skor IRT.
9. Predikat selalu tampil mengikuti jenis skor yang ditampilkan.
10. Mode `raw` menampilkan satu Predikat Raw yang dihitung dari skor raw dan ambang predikat raw.
11. Mode `irt` menampilkan satu Predikat IRT yang dihitung dari skor IRT dan ambang predikat IRT yang sudah ada.
12. Mode `both` menampilkan dua predikat terpisah: Predikat Raw dan Predikat IRT. Jangan menggabungkannya menjadi satu predikat.
13. Rentang IRT hanya boleh tampil pada mode `irt` atau `both`.
14. Rentang/maksimum raw hanya boleh tampil pada mode `raw` atau `both`.
15. Kalkulasi IRT di `AssessmentService` tidak diubah. Kedua skor tetap dihitung dan disimpan untuk kebutuhan audit admin.
16. Pada statistik peserta, mode `raw` menentukan best attempt dan urutan peringkat menggunakan `score`. Mode `irt` dan `both` tetap menggunakan `irt_score` sebagai skor utama peringkat.
17. Analisis AI yang terlihat peserta harus menerima hanya skor dan predikat yang diizinkan. Mode `raw` mengirim skor serta Predikat Raw, mode `irt` mengirim skor serta Predikat IRT, dan mode `both` mengirim kedua pasangan tersebut.

## Matriks Visibilitas

| Area | Mode `raw` | Mode `irt` | Mode `both` |
|---|---|---|---|
| Peserta: total skor | Raw | IRT | Raw dan IRT |
| Peserta: skor per mata pelajaran | Raw | IRT | Raw dan IRT |
| Peserta: predikat | Predikat Raw | Predikat IRT | Predikat Raw dan Predikat IRT |
| Peserta: grafik percobaan/riwayat | Raw | IRT | Dua dataset |
| Peserta: leaderboard | Raw, urut raw | IRT, urut IRT | Raw dan IRT, urut IRT |
| Peserta: analisis AI | Raw + Predikat Raw | IRT + Predikat IRT | Kedua skor + kedua predikat |
| `superadmin` | Kedua skor + kedua predikat | Kedua skor + kedua predikat | Kedua skor + kedua predikat |
| `admin_sesi` | Kedua skor + kedua predikat | Kedua skor + kedua predikat | Kedua skor + kedua predikat |
| CSV admin | Kedua skor + kedua predikat | Kedua skor + kedua predikat | Kedua skor + kedua predikat |

Catatan: jumlah benar, salah, kosong, dan skor benar pada halaman pembahasan soal bukan pilihan skor hasil. Data tersebut tetap boleh tampil.

## Area Kode yang Sudah Ada

Gunakan struktur yang sudah ada. Jangan membuat service, repository, package, atau tabel baru selain yang disebutkan di issue ini.

- Model sesi: `app/Models/ExamSession.php`
- Validasi create/update: `app/Http/Controllers/Admin/ExamSessionController.php`
- Penyimpanan sesi dan aturan penguncian: `app/Services/ExamSessionService.php`
- Form create/edit sesi dan payload AJAX: `resources/views/admin/sessions/index.blade.php`
- Controller hasil langsung setelah ujian: `app/Http/Controllers/ExamController.php`
- Controller dashboard, hasil, review, AI, dan statistik peserta: `app/Http/Controllers/Participant/DashboardController.php`
- Halaman peserta:
  - `resources/views/exam/success.blade.php`
  - `resources/views/participant/dashboard.blade.php`
  - `resources/views/participant/session_detail.blade.php`
  - `resources/views/participant/result.blade.php`
  - `resources/views/participant/review.blade.php`
  - `resources/views/participant/statistics.blade.php`
- Regression test yang sudah ada:
  - `tests/Feature/ParticipantScoreVisibilityTest.php`
  - `tests/Feature/AdminScoreVisibilityTest.php`
  - `tests/Feature/ExamSessionIrtConfigurationTest.php`

## Kontrak Data

### Migration

Buat satu migration baru, jangan mengubah migration lama yang mungkin sudah dijalankan di server lain. Migration ini menambahkan mode tampilan dan empat ambang predikat raw.

Isi migration:

```php
Schema::table('exam_sessions', function (Blueprint $table) {
    $table->string('participant_score_display')->default('irt')->after('is_lock_quiz');
    $table->decimal('predicate_raw_kurang_min', 12, 2)->default(0);
    $table->decimal('predicate_raw_memadai_min', 12, 2)->default(0);
    $table->decimal('predicate_raw_baik_min', 12, 2)->default(0);
    $table->decimal('predicate_raw_istimewa_min', 12, 2)->default(0);
});
```

Setelah kolom dibuat, backfill setiap sesi lama berdasarkan jumlah `exam_session_categories.max_score_raw`:

- Kurang: `0`.
- Memadai: `50%` dari total maksimum raw.
- Baik: `70%` dari total maksimum raw.
- Istimewa: `85%` dari total maksimum raw.

Gunakan pembulatan dua desimal. Method `down()` harus menghapus kelima kolom baru tersebut.

### Model

Tambahkan `participant_score_display` dan keempat kolom `predicate_raw_*` ke `$fillable` pada `ExamSession`. Tambahkan cast `decimal:2` untuk setiap ambang raw.

Tambahkan dua helper sederhana pada model supaya aturan tidak disalin berbeda-beda ke banyak view:

```php
public function showsRawScoreToParticipant(): bool
{
    return in_array($this->participant_score_display, ['raw', 'both'], true);
}

public function showsIrtScoreToParticipant(): bool
{
    return in_array($this->participant_score_display, ['irt', 'both'], true);
}
```

Jangan membuat enum class atau abstraction tambahan untuk tiga nilai ini.

Tambahkan method `predicateForRawScore(float $score): string` pada model. Logikanya sama dengan `predicateForIrtScore()`, tetapi membaca kolom `predicate_raw_kurang_min`, `predicate_raw_memadai_min`, `predicate_raw_baik_min`, dan `predicate_raw_istimewa_min`.

Jangan mengubah `predicateForIrtScore()`. Predikat total dihitung di model; jangan menduplikasi perbandingan ambang di controller atau Blade.

Pemetaan kolom predikat:

| Jenis | Kurang | Memadai | Baik | Istimewa | Method |
|---|---|---|---|---|---|
| Raw | `predicate_raw_kurang_min` | `predicate_raw_memadai_min` | `predicate_raw_baik_min` | `predicate_raw_istimewa_min` | `predicateForRawScore()` |
| IRT | `predicate_kurang_min` | `predicate_memadai_min` | `predicate_baik_min` | `predicate_istimewa_min` | `predicateForIrtScore()` |

### Validasi Request

Di `ExamSessionController::validateSessionData()`, tambahkan:

```php
'participant_score_display' => 'required|in:raw,irt,both',
'predicate_raw_kurang_min' => 'required|numeric|min:0',
'predicate_raw_memadai_min' => 'required|numeric|min:0',
'predicate_raw_baik_min' => 'required|numeric|min:0',
'predicate_raw_istimewa_min' => 'required|numeric|min:0',
```

Backend wajib menolak nilai lain walaupun request tidak berasal dari form browser.

Tambahkan validasi lanjutan untuk ambang raw:

- `predicate_raw_kurang_min` harus sama dengan `0`.
- Urutan harus `Kurang < Memadai < Baik < Istimewa`.
- `predicate_raw_istimewa_min` tidak boleh melebihi jumlah seluruh `categories.*.max_score_raw`.
- Default form adalah `0%`, `50%`, `70%`, dan `85%` dari total maksimum raw.

### Aturan Update

Masukkan `participant_score_display` dan seluruh `predicate_raw_*` ke signature konfigurasi yang dibandingkan oleh `ExamSessionService::updateWithCategories()` atau tambahkan pemeriksaan setara di tempat yang sama.

Hasil yang wajib:

- Belum ada peserta mulai: mode dan ambang predikat dapat diubah.
- Sudah ada peserta dengan `started_at`: perubahan mode atau ambang predikat ditolak dengan status `422`.
- Mengirim mode dan ambang yang sama tidak dianggap perubahan dan tidak ditolak.
- Pesan error harus jelas, misalnya: `Pengaturan tampilan skor dan predikat tidak dapat diubah karena sesi sudah mulai dikerjakan.`

## Tahapan Implementasi

Kerjakan berurutan. Selesaikan acceptance criteria dan verification pada satu task sebelum berpindah ke task berikutnya.

### Task 1 - Tambahkan penyimpanan dan helper mode skor

**Yang dikerjakan:**

1. Buat migration baru untuk mode tampilan dan empat ambang predikat raw.
2. Isi default mode `irt` agar sesi lama mempertahankan tampilan saat ini.
3. Backfill ambang predikat raw sesi lama berdasarkan total maksimum raw.
4. Tambahkan semua kolom baru ke `$fillable` dan `$casts` model `ExamSession`.
5. Tambahkan helper visibilitas serta `predicateForRawScore()`.

**File yang kemungkinan disentuh:**

- `database/migrations/<timestamp>_add_participant_score_display_and_raw_predicates_to_exam_sessions.php`
- `app/Models/ExamSession.php`
- Satu file test baru atau test sesi yang sudah ada

**Acceptance criteria:**

- [ ] Migration `up()` menambahkan satu kolom mode dan empat ambang raw.
- [ ] Migration melakukan backfill ambang raw untuk sesi lama.
- [ ] Migration `down()` menghapus kelima kolom baru.
- [ ] Sesi yang dibuat tanpa nilai eksplisit mendapatkan mode `irt`.
- [ ] Helper model menghasilkan kombinasi boolean yang benar untuk ketiga mode.
- [ ] `predicateForRawScore()` menghasilkan Kurang, Memadai, Baik, dan Istimewa pada batas yang benar.

**Verification:**

```bash
php artisan migrate
php artisan test --filter=ExamSessionScoreDisplayTest
```

### Task 2 - Tambahkan pilihan pada generate/create dan edit sesi

**Yang dikerjakan:**

1. Di modal form sesi, tambahkan satu pilihan berlabel `Skor yang Ditampilkan ke Peserta`.
2. Gunakan tiga radio button atau satu select native: `Skor Raw`, `Skor IRT`, `Skor Raw dan IRT`.
3. Default create adalah `irt`.
4. Ketika `editSession(id)` memuat data sesi, pilih nilai yang tersimpan dari response `show`.
5. Tambahkan `participant_score_display` ke object `data` yang dikirim oleh handler submit.
6. Tambahkan bagian `Ambang Predikat Raw` berisi Kurang, Memadai, Baik, dan Istimewa.
7. Hitung default ambang raw dari jumlah maksimum raw seluruh mata pelajaran, mengikuti rasio `0% / 50% / 70% / 85%`, selama admin belum mengubah ambang secara manual.
8. Muat ambang raw tersimpan ketika edit dan kirim seluruh field `predicate_raw_*` pada payload.
9. Tambahkan validasi backend untuk mode dan urutan/rentang ambang raw.
10. Pastikan create dan update meneruskan seluruh field baru ke `ExamSessionService` tanpa perubahan nama.
11. Bagian Ambang Predikat Raw dan Ambang Predikat IRT harus selalu terlihat oleh admin, apa pun mode tampilan peserta.

**File yang kemungkinan disentuh:**

- `resources/views/admin/sessions/index.blade.php`
- `app/Http/Controllers/Admin/ExamSessionController.php`
- `tests/Feature/ExamSessionScoreDisplayTest.php`

**Acceptance criteria:**

- [ ] Admin dapat membuat sesi dengan masing-masing dari tiga mode.
- [ ] Edit modal menampilkan mode yang sedang tersimpan.
- [ ] Edit modal menampilkan keempat ambang raw yang sedang tersimpan.
- [ ] Update menyimpan mode baru sebelum sesi dimulai.
- [ ] Create/update menyimpan dan memvalidasi ambang predikat raw.
- [ ] Nilai seperti `all`, string kosong, atau field yang hilang ditolak dengan `422`.
- [ ] Perubahan mode atau ambang raw setelah peserta mulai ditolak, sedangkan submit nilai yang sama tetap berhasil.

**Verification:**

```bash
php artisan test --filter=ExamSessionScoreDisplayTest
```

Lakukan pemeriksaan manual create dan edit karena test HTTP tidak membuktikan radio/select JavaScript terisi dengan benar.

### Checkpoint A - Fondasi setting

- [ ] Migration dapat dijalankan dan di-rollback.
- [ ] Create, show JSON, dan update membawa nilai yang sama.
- [ ] Mode dan kedua kelompok ambang predikat tidak dapat berubah setelah ujian mulai.
- [ ] Test Task 1-2 lulus sebelum mengubah halaman peserta.

### Task 3 - Terapkan mode pada hasil utama peserta

**Yang dikerjakan:**

1. Perbarui `ExamController::success()` agar mengirim data raw dan IRT total serta per mata pelajaran ke view. Jangan hanya menyiapkan IRT.
2. Gunakan label status generik seperti `Hasil sedang dihitung` saat hasil belum tersedia. Jangan menyebut kalibrasi IRT pada mode `raw`.
3. Di `exam/success.blade.php`, tampilkan card/kolom skor berdasarkan helper pada sesi.
4. Di `participant/result.blade.php`, terapkan aturan yang sama untuk total dan detail per mata pelajaran.
5. Pada raw, tampilkan maksimum raw dari `sum(max_score_raw)`. Per kategori gunakan `max_score_raw` kategori tersebut.
6. Pada raw, tampilkan skor raw beserta Predikat Raw dari `predicateForRawScore()`.
7. Pada IRT, pertahankan rentang bawah/atas dan Predikat IRT yang sudah ada.
8. Pada both, tampilkan skor raw + Predikat Raw dan skor IRT + Predikat IRT sebagai dua pasangan yang jelas.

**File yang kemungkinan disentuh:**

- `app/Http/Controllers/ExamController.php`
- `resources/views/exam/success.blade.php`
- `resources/views/participant/result.blade.php`
- `tests/Feature/ParticipantScoreVisibilityTest.php`

**Acceptance criteria:**

- [ ] Mode raw tidak merender label/nilai `Skor IRT`, `Rentang IRT`, atau `Predikat IRT`, tetapi merender `Predikat Raw`.
- [ ] Mode IRT tidak merender label/nilai `Skor Raw` atau `Predikat Raw`, tetapi merender `Predikat IRT`.
- [ ] Mode both merender kedua skor dan dua predikat terpisah.
- [ ] Aturan yang sama berlaku pada total dan setiap mata pelajaran.
- [ ] Nilai tetap berasal dari record hasil, tidak dihitung ulang di Blade.

**Verification:**

```bash
php artisan test --filter=ParticipantScoreVisibilityTest
```

### Task 4 - Terapkan mode pada review peserta

**Yang dikerjakan:**

1. Ubah ringkasan total di `participant/review.blade.php` mengikuti mode sesi.
2. Ubah detail nilai setiap mata pelajaran mengikuti mode sesi.
3. Ubah nilai pada daftar/review per mata pelajaran mengikuti mode sesi.
4. Jangan mengubah `participant/review_category.blade.php` untuk teks jumlah pernyataan benar; itu adalah progres jawaban, bukan skor hasil raw/IRT.
5. Pastikan mode raw menampilkan Predikat Raw tanpa menyisakan Predikat IRT atau rentang IRT di HTML.

**File yang kemungkinan disentuh:**

- `resources/views/participant/review.blade.php`
- `tests/Feature/ParticipantScoreVisibilityTest.php`

**Acceptance criteria:**

- [ ] Mode raw hanya menampilkan raw dan Predikat Raw pada bagian skor hasil.
- [ ] Mode IRT hanya menampilkan IRT dan Predikat IRT.
- [ ] Mode both menampilkan raw, IRT, dan masing-masing predikatnya.
- [ ] Pembahasan jawaban dan hitungan benar/salah tetap berfungsi.

**Verification:**

```bash
php artisan test --filter=ParticipantScoreVisibilityTest
```

### Checkpoint B - Hasil satu percobaan

- [ ] Halaman success, result, dan review konsisten untuk ketiga mode.
- [ ] Tidak ada nilai tersembunyi yang ikut tercetak di HTML peserta.
- [ ] Layout mobile dan desktop tetap terbaca pada mode one-score maupun two-score.

### Task 5 - Terapkan mode pada dashboard dan riwayat percobaan

**Yang dikerjakan:**

1. Di `Participant/DashboardController::index()`, siapkan `raw_scores` dan `irt_scores` berdasarkan mode masing-masing sesi.
2. Gunakan `null` untuk data yang tidak boleh ditampilkan agar Chart.js tidak menggambar nilai tersembunyi.
3. Di dashboard, buat dataset Raw hanya jika setidaknya ada satu raw yang boleh tampil. Buat dataset IRT hanya jika setidaknya ada satu IRT yang boleh tampil.
4. Tooltip titik raw menampilkan Predikat Raw dan tooltip titik IRT menampilkan Predikat IRT.
5. Di `session_detail.blade.php`, badge tiap attempt dan grafik attempt mengikuti satu mode milik sesi tersebut.
6. Mode both menampilkan dua dataset dengan label jelas. Jika skala raw dan IRT berbeda jauh, gunakan dua sumbu Y Chart.js yang sudah tersedia; jangan menambah library chart baru.

**File yang kemungkinan disentuh:**

- `app/Http/Controllers/Participant/DashboardController.php`
- `resources/views/participant/dashboard.blade.php`
- `resources/views/participant/session_detail.blade.php`
- `tests/Feature/ParticipantScoreVisibilityTest.php`

**Acceptance criteria:**

- [ ] Setiap titik dashboard mengikuti setting sesi asalnya.
- [ ] Grafik satu sesi mengikuti mode sesi itu.
- [ ] Payload JavaScript peserta tidak berisi skor yang disembunyikan.
- [ ] Tooltip menampilkan predikat yang berasal dari jenis skor dataset tersebut.
- [ ] Mode both menampilkan dua dataset tanpa error JavaScript.

**Verification:**

```bash
php artisan test --filter=ParticipantScoreVisibilityTest
npm run build
```

Lakukan manual check di browser pada mode `raw`, `irt`, dan `both`, termasuk viewport mobile.

### Task 6 - Terapkan mode pada statistik dan leaderboard

**Yang dikerjakan:**

1. Di `showStatistics()`, mode raw memilih best attempt dan mengurutkan ranking berdasarkan `score`.
2. Mode irt dan both mempertahankan pemilihan dan urutan berdasarkan `irt_score`.
3. Pada kondisi nilai sama, pertahankan tie-breaker yang sudah ada: `total_correct`, lalu `id`.
4. Ubah cache key statistik agar menyertakan mode, misalnya `statistics_session_{id}_{mode}`. Ini mencegah data ranking dari mode lama digunakan setelah deploy atau perubahan setting sebelum ujian mulai.
5. Ubah teks penjelasan dan kolom skor pada `participant/statistics.blade.php` agar sesuai mode.

**File yang kemungkinan disentuh:**

- `app/Http/Controllers/Participant/DashboardController.php`
- `resources/views/participant/statistics.blade.php`
- `tests/Feature/ParticipantScoreVisibilityTest.php`

**Acceptance criteria:**

- [ ] Raw mengurutkan dan menampilkan raw beserta Predikat Raw tanpa label/nilai IRT.
- [ ] IRT mengurutkan dan menampilkan IRT beserta Predikat IRT tanpa raw.
- [ ] Both mengurutkan berdasarkan IRT dan menampilkan kedua skor beserta dua predikat terpisah.
- [ ] Cache untuk satu mode tidak digunakan oleh mode lain.

**Verification:**

```bash
php artisan test --filter=ParticipantScoreVisibilityTest
```

### Task 7 - Selaraskan analisis AI yang dilihat peserta

**Yang dikerjakan:**

1. Pada `generateAIAnalysis()`, bentuk `total_score` berdasarkan mode sesi.
2. Pada mode raw, kirim skor raw dan Predikat Raw; jangan masukkan IRT atau Predikat IRT ke prompt.
3. Pada mode irt, gunakan skor IRT dan Predikat IRT seperti sekarang; jangan masukkan raw atau Predikat Raw.
4. Pada mode both, masukkan skor raw + Predikat Raw dan skor IRT + Predikat IRT dengan label yang jelas.
5. Pada `generateAggregateAnalysis()`, bentuk data setiap attempt berdasarkan aturan yang sama.
6. Periksa template prompt di `app/Services/AIService.php`. Hilangkan instruksi hard-coded yang memaksa AI menyebut IRT bila mode raw dipilih, tetapi jangan mengubah format response lain.

**File yang kemungkinan disentuh:**

- `app/Http/Controllers/Participant/DashboardController.php`
- `app/Services/AIService.php` hanya jika prompt saat ini hard-coded ke IRT
- `tests/Unit/AIServicePromptTest.php`
- `tests/Feature/ParticipantScoreVisibilityTest.php`

**Acceptance criteria:**

- [ ] Prompt raw mengandung skor dan Predikat Raw, tetapi tidak mengandung nilai/Predikat IRT.
- [ ] Prompt IRT mengandung skor dan Predikat IRT, tetapi tidak mengandung nilai/Predikat Raw.
- [ ] Prompt both mengandung kedua skor dan kedua predikat.
- [ ] Response analisis tetap dapat dirender oleh view yang ada.

**Verification:**

```bash
php artisan test --filter=AIServicePromptTest
php artisan test --filter=ParticipantScoreVisibilityTest
```

Jangan memanggil API AI sungguhan dalam automated test. Uji payload/prompt secara lokal seperti pola test yang sudah ada.

### Checkpoint C - Seluruh pengalaman peserta

- [ ] Semua halaman peserta pada daftar inventaris sudah diperiksa.
- [ ] Tidak ada label, angka, tooltip, JSON, atau teks AI yang membocorkan skor yang tidak dipilih.
- [ ] Dasar ranking sesuai mode.
- [ ] Build frontend berhasil tanpa console error.

### Task 8 - Pastikan seluruh area admin menampilkan kedua skor dan predikat

**Yang dikerjakan:**

1. Jangan membungkus kolom skor admin dengan helper visibilitas peserta.
2. Pastikan detail sesi admin di `resources/views/admin/sessions/show.blade.php` selalu menampilkan raw + Predikat Raw dan IRT + Predikat IRT pada setiap hasil peserta.
3. Pada bagian konfigurasi detail sesi, tampilkan `Ambang Predikat Raw` dan `Ambang Predikat IRT` sebagai dua kelompok terpisah.
4. Pastikan laporan peserta admin dan versi print selalu menampilkan kedua skor dan kedua predikat.
5. Tambahkan kolom `Predikat Raw` dan `Predikat IRT` pada `ExamSessionController::exportResults()` di samping `Skor Raw` dan `Skor IRT` untuk semua mode.
6. Perluas `AdminScoreVisibilityTest` agar menjalankan assertion untuk mode raw, irt, dan both serta untuk role `superadmin` dan `admin_sesi`.

**File yang kemungkinan disentuh:**

- `resources/views/admin/sessions/show.blade.php`
- `resources/views/admin/participants/report.blade.php`
- `resources/views/admin/participants/report-print.blade.php`
- `app/Http/Controllers/Admin/ExamSessionController.php`
- `tests/Feature/AdminScoreVisibilityTest.php`

**Acceptance criteria:**

- [ ] Kedua role admin melihat kedua skor dan kedua predikat pada semua mode sesi.
- [ ] Kedua kelompok ambang predikat terlihat di detail sesi admin.
- [ ] CSV selalu berisi kedua skor, kedua predikat, dan nilainya.
- [ ] User peserta tetap mendapat `403` saat membuka route admin.

**Verification:**

```bash
php artisan test --filter=AdminScoreVisibilityTest
```

### Task 9 - Regression test dan QA akhir

**Yang dikerjakan:**

1. Jalankan semua test, bukan hanya test fitur baru.
2. Jalankan formatter dan build asset.
3. Lakukan pencarian global untuk semua penggunaan `->score`, `irt_score`, `Skor Raw`, `Skor IRT`, `Predikat Raw`, dan `Predikat IRT` di view/controller peserta.
4. Periksa manual satu sesi untuk masing-masing mode dari create sampai hasil akhir.
5. Periksa role `superadmin` dan `admin_sesi` pada ketiga mode.

**Verification akhir:**

```bash
vendor/bin/pint --test
php artisan test
npm run build
rg -n "Skor Raw|Skor IRT|irt_score|predicateForRawScore|predicateForIrtScore|->score" app/Http/Controllers resources/views
```

**Acceptance criteria:**

- [ ] Seluruh test lulus.
- [ ] Formatter tidak menemukan masalah.
- [ ] Build frontend lulus.
- [ ] Tidak ada lokasi peserta yang melewati aturan setting.
- [ ] Admin tetap melihat kedua skor dan kedua predikat.

## Skenario Test Minimum

Automated test harus menggunakan angka yang mudah dibedakan, misalnya raw `42.50` dan IRT `900.00`, supaya assertion tidak salah mengenali angka yang sama.

1. Sesi lama/default menghasilkan mode `irt`.
2. Create sesi berhasil untuk `raw`, `irt`, dan `both`.
3. Create/update ditolak untuk nilai mode tidak valid.
4. Ambang raw default dan custom menghasilkan empat predikat pada boundary yang benar.
5. Ambang raw dengan urutan/rentang tidak valid ditolak.
6. Update mode dan ambang berhasil sebelum peserta mulai.
7. Update mode atau ambang ditolak sesudah peserta mulai.
8. Mode raw menampilkan `42.50` dan Predikat Raw, tetapi tidak menampilkan `900.00`, `Skor IRT`, atau Predikat IRT pada seluruh halaman peserta.
9. Mode irt menampilkan `900.00` dan Predikat IRT, tetapi tidak menampilkan `42.50`, `Skor Raw`, atau Predikat Raw.
10. Mode both menampilkan `42.50`, `900.00`, Predikat Raw, dan Predikat IRT secara terpisah.
11. Aturan yang sama diuji untuk skor total dan skor per mata pelajaran; predikat tetap hanya untuk total kecuali produk meminta predikat per mata pelajaran.
12. Leaderboard raw memilih best attempt berdasarkan raw.
13. Leaderboard irt/both memilih best attempt berdasarkan IRT.
14. Dashboard dengan sesi campuran tidak mengirim skor yang disembunyikan ke JSON halaman.
15. Role `superadmin` dan `admin_sesi` selalu melihat kedua skor dan kedua predikat pada ketiga mode.
16. CSV selalu menampilkan kedua skor dan kedua predikat pada ketiga mode.
17. Peserta tidak dapat mengakses route admin.

## Urutan Dependensi

```text
Task 1 (database + model)
  -> Task 2 (form + validasi + penguncian)
      -> Task 3 (success + result)
          -> Task 4 (review)
          -> Task 5 (dashboard + riwayat)
          -> Task 6 (statistik)
          -> Task 7 (analisis AI)
              -> Task 8 (regresi admin)
                  -> Task 9 (QA akhir)
```

Task 4-7 boleh dikerjakan terpisah setelah Task 3, tetapi jangan dikerjakan paralel jika implementornya hanya satu orang/model karena semuanya menyentuh aturan visibilitas yang sama.

## Risiko dan Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Sesi lama memiliki nilai `null` | Tampilan peserta kosong atau salah | Default migration `irt`; helper tetap dapat memakai fallback `?? 'irt'` selama rollout bila diperlukan |
| Hanya label disembunyikan, tetapi angka masih ada di HTML/JSON | Skor yang tidak diizinkan bocor | Assertion `assertDontSee` untuk label dan nilai unik; periksa source HTML dan payload chart |
| Setting diterapkan pada admin | Admin kehilangan data audit | Helper bernama khusus `ToParticipant`; admin selalu merender kedua skor/predikat; regression test dua role admin |
| Predikat raw memakai ambang IRT | Predikat salah karena skala raw dan IRT berbeda | Simpan empat ambang raw tersendiri dan gunakan `predicateForRawScore()` |
| Mode both hanya menampilkan satu predikat | Peserta tidak tahu predikat tersebut milik skor yang mana | Tampilkan dua label eksplisit: Predikat Raw dan Predikat IRT |
| Grafik rusak pada mode both | Dua skala menghasilkan chart tidak terbaca | Gunakan dua dataset/two axes dari Chart.js yang sudah ada; test manual browser |
| Ranking raw masih memakai IRT | Urutan tidak sesuai skor yang terlihat | Pilih field best attempt dan sorter dari mode sesi; tambahkan test dengan urutan raw dan IRT yang berlawanan |
| Analisis AI menyebut skor tersembunyi | Kebijakan tampilan tidak konsisten | Bentuk payload prompt berdasarkan mode dan unit test isi prompt |
| Mode berubah ketika ujian berjalan | Peserta pada sesi yang sama melihat aturan berbeda | Tolak perubahan setelah `started_at` terisi |

## Di Luar Scope

- Mengubah rumus raw atau IRT.
- Menghapus kolom skor dari database atau response internal admin.
- Menambah jenis skor baru.
- Membuat permission/role baru.
- Menambah package frontend/backend baru.
- Mengubah desain besar halaman hasil.
- Mengubah file migration lama.

## Definition of Done

Fitur dianggap selesai hanya jika:

- [ ] Admin dapat memilih raw, IRT, atau keduanya saat generate/membuat sesi.
- [ ] Pilihan tersimpan dan muncul kembali saat edit.
- [ ] Setting terkunci setelah sesi mulai dikerjakan.
- [ ] Semua tampilan peserta mengikuti matriks visibilitas.
- [ ] Predikat selalu tampil sesuai skor yang terlihat.
- [ ] Mode both menampilkan Predikat Raw dan Predikat IRT secara terpisah.
- [ ] Ranking dan analisis AI mengikuti mode.
- [ ] `superadmin` dan `admin_sesi` selalu melihat raw, IRT, Predikat Raw, dan Predikat IRT.
- [ ] CSV admin selalu berisi kedua skor dan kedua predikat.
- [ ] Tidak ada skor tersembunyi di HTML atau JSON peserta.
- [ ] Semua automated test, formatter, dan build lulus.
- [ ] QA manual untuk tiga mode pada desktop dan mobile selesai.
