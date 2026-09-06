# Issue: Batas Nilai IRT, Predikat Sesi, dan Pembatasan Skor Raw

**Tipe:** Feature + perubahan tampilan hasil
**Prioritas:** Tinggi
**Target implementer:** Junior programmer / AI model berbiaya rendah
**Status:** Siap direview sebelum implementasi

> Dokumen ini adalah rencana implementasi. Jangan mulai mengubah kode sebelum keputusan dan acceptance criteria di bawah disetujui oleh product owner.

## 1. Tujuan

Tambahkan konfigurasi penilaian pada saat superadmin membuat atau mengedit sesi ujian agar:

1. Setiap mata pelajaran mempunyai batas bawah dan batas atas skor IRT.
2. Peserta yang salah atau kosong semua tetap mendapat skor IRT sebesar batas bawah yang dikonfigurasi.
3. Setiap sesi mempunyai empat predikat berdasarkan total skor IRT: `Kurang`, `Memadai`, `Baik`, dan `Istimewa`.
4. Peserta hanya melihat skor IRT dan predikat. Skor raw tidak boleh muncul pada halaman, grafik, respons JSON, atau narasi AI yang dapat dilihat peserta.
5. Skor raw tetap dihitung, disimpan, dan dapat dilihat oleh `superadmin` serta `admin_sesi`.

## 2. Kondisi Kode Saat Ini

Hasil audit singkat terhadap repository:

- Form sesi berada di `resources/views/admin/sessions/index.blade.php`.
- Validasi request create/update berada di `App\Http\Controllers\Admin\ExamSessionController::validateSessionData()`.
- Penyimpanan konfigurasi mata pelajaran berada di `App\Services\ExamSessionService`.
- Nilai maksimum IRT sudah disimpan per mata pelajaran pada `exam_session_categories.max_score_irt`.
- Perhitungan skor berada di `App\Services\AssessmentService::calculateIRT()`.
- Total raw disimpan di `exam_results.score`; raw per mata pelajaran disimpan di `exam_category_results.score`.
- Total IRT disimpan di `exam_results.irt_score`; IRT per mata pelajaran disimpan di `exam_category_results.irt_score`.
- Skor raw masih muncul pada dashboard peserta, halaman sukses, hasil, review, grafik percobaan, statistik, dan input analisis AI peserta.
- Halaman detail sesi admin dan export CSV saat ini menampilkan raw dan IRT. Perilaku ini harus dipertahankan.

## 3. Keputusan Implementasi

Gunakan keputusan berikut agar implementer tidak perlu menebak.

### 3.1 Lokasi batas IRT

Batas IRT disimpan **per mata pelajaran dalam sesi**, bukan langsung pada `exam_sessions`.

Alasannya: konfigurasi yang sudah ada menempatkan `max_score_irt` pada `exam_session_categories`, dan perhitungan IRT juga dilakukan per mata pelajaran sebelum dijumlahkan.

- Tambahkan `exam_session_categories.min_score_irt` sebagai batas bawah.
- Pertahankan `exam_session_categories.max_score_irt` sebagai batas atas.
- Jangan mengganti nama atau menghapus `max_score_irt` agar migrasi tetap kecil dan aman.

### 3.2 Lokasi ambang predikat

Ambang predikat disimpan pada `exam_sessions` karena predikat berlaku untuk **total IRT satu sesi**, bukan untuk masing-masing mata pelajaran.

Kolom yang direkomendasikan:

- `predicate_kurang_min`
- `predicate_memadai_min`
- `predicate_baik_min`
- `predicate_istimewa_min`

Keempat nilai tersebut adalah **nilai minimum** untuk masing-masing predikat.

### 3.3 Predikat tidak disimpan di hasil

Jangan menambah kolom `predicate` pada `exam_results`. Predikat adalah data turunan dari `irt_score` dan konfigurasi sesi. Buat satu method pada model `ExamSession`, misalnya:

```php
public function predicateForIrtScore(float $score): string
```

Semua controller/view yang memerlukan predikat harus memakai method yang sama. Ini mencegah rumus predikat berbeda di beberapa halaman.

### 3.4 Skor raw tidak dihapus dari database

Jangan menghapus kolom berikut:

- `exam_results.score`
- `exam_category_results.score`
- `user_answers.score`
- `exam_session_categories.max_score_raw`

Skor raw masih dibutuhkan untuk proses internal dan laporan admin. Yang dihapus hanya paparan skor raw kepada peserta.

### 3.5 Konfigurasi nilai dikunci setelah ujian dimulai

Perubahan `min_score_irt`, `max_score_irt`, atau ambang predikat harus ditolak jika sudah ada peserta pada sesi tersebut yang mempunyai `started_at`.

Jangan otomatis menghitung ulang hasil saat konfigurasi diubah. Mengunci konfigurasi lebih sederhana dan mencegah hasil lama berubah tanpa sengaja.

## 4. Kontrak Data

### 4.1 Perubahan tabel

| Tabel | Kolom | Tipe yang disarankan | Default | Keterangan |
|---|---|---:|---:|---|
| `exam_session_categories` | `min_score_irt` | `decimal(12,2)` | `0` | Batas bawah IRT per mata pelajaran |
| `exam_sessions` | `predicate_kurang_min` | `decimal(12,2)` | `0` | Minimum predikat Kurang |
| `exam_sessions` | `predicate_memadai_min` | `decimal(12,2)` | `0` | Minimum predikat Memadai |
| `exam_sessions` | `predicate_baik_min` | `decimal(12,2)` | `0` | Minimum predikat Baik |
| `exam_sessions` | `predicate_istimewa_min` | `decimal(12,2)` | `0` | Minimum predikat Istimewa |

Tambahkan casts `decimal:2` pada model yang relevan supaya nilai konsisten saat diserialisasi ke form edit.

### 4.2 Data sesi lama

Migrasi harus tetap valid pada database yang sudah berisi sesi.

1. Isi `min_score_irt = 0` untuk setiap `exam_session_categories` lama. Ini mempertahankan hasil historis seperti sebelum fitur ini dibuat.
2. Untuk setiap sesi lama, hitung:

```text
totalMin = SUM(min_score_irt)
totalMax = SUM(max_score_irt)
range    = totalMax - totalMin
```

3. Isi default predikat lama:

```text
Kurang    = totalMin
Memadai   = totalMin + 50% × range
Baik      = totalMin + 70% × range
Istimewa  = totalMin + 85% × range
```

4. Bulatkan hasil backfill menjadi dua angka desimal.
5. Gunakan query builder `DB::table(...)` di migration, bukan model Eloquent, agar migration tidak bergantung pada bentuk model di masa depan.

Default 50/70/85 hanya dipakai untuk data lama dan nilai awal form. Admin tetap dapat menggantinya sebelum sesi mulai dikerjakan.

### 4.3 Perubahan model

`App\Models\ExamSessionCategory`:

- Tambahkan `min_score_irt` ke `$fillable`.
- Tambahkan cast decimal untuk `min_score_irt` bila project memakai casts pada model ini.

`App\Models\ExamSession`:

- Tambahkan empat kolom predikat ke `$fillable`.
- Tambahkan casts decimal untuk keempat kolom.
- Tambahkan `predicateForIrtScore()`.

## 5. Aturan Validasi

Validasi browser hanya untuk UX. Validasi backend tetap wajib menjadi sumber kebenaran.

### 5.1 Validasi batas per mata pelajaran

Untuk setiap item `categories.*`:

- `min_score_irt`: wajib, numeric, minimal `0`.
- `max_score_irt`: wajib, numeric, harus lebih besar daripada `min_score_irt`.
- `max_score_raw`: tetap wajib seperti sekarang.
- Jangan menerima `NaN`, string nonnumeric, atau nilai negatif.

### 5.2 Validasi total dan predikat

Setelah array kategori lolos validasi dasar, hitung:

```text
totalMinIRT = SUM(categories.*.min_score_irt)
totalMaxIRT = SUM(categories.*.max_score_irt)
```

Kemudian validasi:

```text
predicate_kurang_min    == totalMinIRT
predicate_kurang_min    < predicate_memadai_min
predicate_memadai_min   < predicate_baik_min
predicate_baik_min      < predicate_istimewa_min
predicate_istimewa_min  <= totalMaxIRT
```

Gunakan perbandingan setelah semua nilai dinormalisasi ke dua angka desimal.

Contoh pesan error yang jelas:

- `Batas atas IRT harus lebih besar dari batas bawah IRT.`
- `Nilai minimum predikat Kurang harus sama dengan total batas bawah IRT, yaitu 200.`
- `Urutan ambang predikat harus Kurang < Memadai < Baik < Istimewa.`
- `Ambang Istimewa tidak boleh melebihi total batas atas IRT, yaitu 2000.`

### 5.3 Validasi perubahan sesi berjalan

Pada `ExamSessionService::updateWithCategories()`:

1. Bandingkan konfigurasi scoring lama dan data baru.
2. Jika konfigurasi scoring berubah dan ada participant dengan `started_at`, lempar `DomainException`.
3. Pesan yang disarankan: `Konfigurasi nilai tidak dapat diubah karena sesi sudah mulai dikerjakan.`
4. Jangan memasukkan nilai scoring ke `allocationSignature()` karena perubahan skor tidak boleh memicu generate ulang soal. Buat perbandingan scoring terpisah dan sesingkat mungkin.

## 6. Algoritma Skor IRT

### 6.1 Pertahankan rasio performa yang sudah ada

`AssessmentService` saat ini menghitung bobot kesulitan item dan rasio performa per mata pelajaran. Jangan mengganti metode penentuan bobot item dalam issue ini.

Untuk satu mata pelajaran:

```text
weightedEarned = jumlah bobot item yang diperoleh peserta
weightedMax    = jumlah bobot seluruh item mata pelajaran

performanceRatio = weightedEarned / weightedMax
performanceRatio = clamp(performanceRatio, 0, 1)
```

Jika `weightedMax <= 0`, hentikan proses dan laporkan konfigurasi sesi tidak valid. Jangan diam-diam membagi dengan nol.

### 6.2 Ubah scaling IRT ke rentang bawah–atas

Rumus lama:

```text
finalIRT = performanceRatio × maxScoreIRT
```

Rumus baru:

```text
irtRange = maxScoreIRT - minScoreIRT
finalIRT = minScoreIRT + (performanceRatio × irtRange)
finalIRT = clamp(finalIRT, minScoreIRT, maxScoreIRT)
finalIRT = round(finalIRT, 2)
```

Contoh:

```text
Batas bawah = 200
Batas atas  = 1000

Rasio 0%    -> 200
Rasio 25%   -> 400
Rasio 50%   -> 600
Rasio 100%  -> 1000
```

Konsekuensi yang wajib dipenuhi:

- Salah semua menghasilkan batas bawah.
- Kosong semua juga menghasilkan batas bawah.
- Benar semua menghasilkan batas atas.
- Jawaban parsial berada secara linear di antara batas bawah dan batas atas.
- Nilai tidak boleh keluar dari rentang walaupun `score_incorrect` negatif atau data jawaban tidak normal.

### 6.3 Total sesi

Setelah setiap skor kategori dibulatkan dua desimal:

```text
totalIRT = round(SUM(finalIRT setiap kategori), 2)
```

Simpan hasil seperti saat ini:

- `exam_category_results.irt_score = finalIRT` per mata pelajaran.
- `exam_results.irt_score = totalIRT` untuk satu peserta.

Raw score tetap dihitung dan disimpan dengan algoritma lama.

## 7. Algoritma Predikat

Predikat ditentukan dari **total IRT tersimpan**, bukan raw score dan bukan nilai per mata pelajaran.

Urutan pemeriksaan harus dari ambang tertinggi ke terendah:

```text
if totalIRT >= predicate_istimewa_min:
    Istimewa
else if totalIRT >= predicate_baik_min:
    Baik
else if totalIRT >= predicate_memadai_min:
    Memadai
else:
    Kurang
```

Batas bawah bersifat inklusif. Contoh ambang `100, 500, 700, 850`:

| Total IRT | Predikat |
|---:|---|
| `100.00` sampai `499.99` | Kurang |
| `500.00` sampai `699.99` | Memadai |
| `700.00` sampai `849.99` | Baik |
| `850.00` ke atas, maksimum sesuai sesi | Istimewa |

Method `predicateForIrtScore()` harus diuji tepat di bawah, tepat pada, dan tepat di atas setiap ambang.

## 8. Perubahan Form Sesi Admin

File utama: `resources/views/admin/sessions/index.blade.php`.

### 8.1 Konfigurasi mata pelajaran

Pada setiap baris mata pelajaran:

- Ubah label `Skor IRT` menjadi `Batas Atas IRT`.
- Tambahkan input `Batas Bawah IRT`.
- Gunakan `type="number"`, `min="0"`, dan `step="0.01"`.
- Default batas bawah adalah `0`.
- Kirim nilai sebagai `categories[].min_score_irt`.
- Saat mode edit, isi dari `session_categories[].min_score_irt`.

Input `Skor Raw` tetap ada karena admin masih boleh mengelolanya.

### 8.2 Konfigurasi predikat sesi

Tambahkan satu bagian `Ambang Predikat IRT` setelah konfigurasi mata pelajaran, berisi empat input:

- Minimum Kurang
- Minimum Memadai
- Minimum Baik
- Minimum Istimewa

Tampilkan juga informasi read-only yang dihitung dari baris mata pelajaran:

- `Total batas bawah IRT`
- `Total batas atas IRT`

Saat batas kategori berubah, perbarui informasi total. Untuk sesi baru, isi nilai awal 0/50/70/85 persen dari rentang total. Jangan menimpa angka yang sudah diedit manual oleh admin.

### 8.3 Payload create/update

Payload harus memuat:

```json
{
  "predicate_kurang_min": 100,
  "predicate_memadai_min": 500,
  "predicate_baik_min": 700,
  "predicate_istimewa_min": 850,
  "categories": [
    {
      "id": 1,
      "duration": 60,
      "total_questions": 40,
      "max_score_raw": 100,
      "min_score_irt": 100,
      "max_score_irt": 1000,
      "sub_categories": []
    }
  ]
}
```

Pastikan endpoint `show()` yang dipakai mode edit mengembalikan semua kolom baru melalui serialisasi model.

## 9. Matriks Visibilitas Skor

| Area | Peserta | Superadmin | Admin sesi |
|---|---|---|---|
| Total IRT | Tampil | Tampil | Tampil |
| IRT per mata pelajaran | Tampil | Tampil | Tampil |
| Predikat total sesi | Tampil | Boleh tampil | Boleh tampil |
| Total raw | Tidak boleh tampil/terkirim | Tampil | Tampil |
| Raw per mata pelajaran | Tidak boleh tampil/terkirim | Tampil | Tampil |
| Raw pada export admin | Tidak punya akses | Tampil | Tampil |
| Raw sebagai input narasi AI peserta | Tidak boleh | Tidak relevan | Tidak relevan |

`Tidak boleh tampil/terkirim` juga berarti nilai raw tidak boleh disisipkan ke JavaScript, HTML comment, data attribute, atau JSON endpoint peserta walaupun elemen visualnya disembunyikan dengan CSS.

## 10. Lokasi Peserta yang Harus Dibersihkan

### 10.1 Dashboard peserta

Files:

- `App\Http\Controllers\Participant\DashboardController::index()`
- `resources/views/participant/dashboard.blade.php`

Perubahan:

- Bangun dataset grafik dari `result.irt_score`, bukan `result.score`.
- Ganti nama key menjadi jelas, misalnya `irt_scores`.
- Label grafik harus `Skor IRT`.
- Bila tooltip menampilkan predikat, kirim array predikat yang dihasilkan dari method model, bukan perhitungan ulang di JavaScript.

### 10.2 Detail sesi dan grafik percobaan

Files:

- `App\Http\Controllers\Participant\DashboardController::showSession()`
- `resources/views/participant/session_detail.blade.php`

Perubahan:

- Hapus `rawScores` dari source HTML/JavaScript.
- Grafik hanya mempunyai satu dataset IRT.
- Riwayat hasil boleh menampilkan `IRT + predikat` per percobaan.

### 10.3 Halaman selesai setelah submit

Files:

- `App\Http\Controllers\ExamController::success()`
- `resources/views/exam/success.blade.php`

Perubahan:

- Jangan membuat atau mengirim `$rawScore`.
- Hapus `categoryScores[].score` dari data view.
- Tampilkan total IRT dan predikat.
- Detail mata pelajaran hanya menampilkan IRT.

### 10.4 Halaman lihat hasil

File: `resources/views/participant/result.blade.php`.

Perubahan:

- Hapus kartu total raw.
- Hapus raw per mata pelajaran dan variabel `$maxRaw`/`$maxRawCat`.
- Tampilkan total IRT, rentang total IRT, dan predikat.
- Detail mata pelajaran menampilkan skor IRT serta batas bawah/atas IRT.

### 10.5 Halaman review/pembahasan

File: `resources/views/participant/review.blade.php`.

Perubahan:

- Hapus seluruh perhitungan raw score inline di Blade.
- Hapus total raw dan raw per mata pelajaran.
- Gunakan `registration.result.irt_score` dan predikat sesi.
- Perhitungan benar/salah/kosong dan pembahasan soal tetap dipertahankan.

Catatan: penggunaan `user_answers.score` untuk menghitung statistik internal tidak perlu dihapus selama nilainya tidak dikirim atau ditampilkan sebagai raw score.

### 10.6 Statistik/leaderboard peserta

Files:

- `App\Http\Controllers\Participant\DashboardController::showStatistics()`
- `resources/views/participant/statistics.blade.php`

Perubahan:

- Urutkan best attempt dan ranking selalu berdasarkan `irt_score`.
- Hapus semua fallback `irt_score > 0 ? irt_score : score`. Nilai IRT `0` atau batas bawah `0` adalah nilai sah, bukan tanda bahwa IRT belum tersedia.
- Jangan pernah memakai `score` sebagai tie-breaker yang terlihat oleh peserta. Gunakan `total_correct`, lalu `id` sebagai tie-breaker stabil bila diperlukan.
- Tampilkan hanya IRT. Predikat boleh ditampilkan di sampingnya.
- Ubah keterangan statistik sementara: IRT dapat berubah ketika peserta lain selesai dan sesi dihitung ulang.

### 10.7 Analisis AI peserta

Files:

- `App\Http\Controllers\Participant\DashboardController::generateAIAnalysis()`
- `App\Http\Controllers\Participant\DashboardController::generateAggregateAnalysis()`
- `App\Services\AIService::buildPrompt()`
- `App\Services\AIService::buildAggregatePrompt()`
- `App\Jobs\GenerateAiAnalysisJob`

Perubahan:

- Hapus perhitungan raw sementara dari controller.
- Jangan membuat hasil dengan `irt_score = 0` hanya agar analisis AI dapat berjalan. Jika hasil IRT belum tersedia, kembalikan status yang jelas dan minta frontend mencoba lagi setelah kalkulasi selesai.
- Ganti input `raw_score` dengan `irt_score` dan, bila berguna, `predicate`.
- Ubah prompt agar tidak meminta AI membandingkan atau menyebut skor raw.
- Pertahankan angka benar/salah/kosong karena angka tersebut bukan raw score nilai.
- Bersihkan `exam_results.ai_analysis` dan `aggregate_ai_analyses.analysis_data` lama yang mungkin sudah berisi narasi raw, atau tandai untuk digenerate ulang. Tanpa langkah ini, teks lama masih dapat membocorkan raw kepada peserta.

Jangan ubah AI report card admin karena area itu hanya dapat diakses superadmin.

## 11. Area Admin yang Harus Dipertahankan

Pastikan perubahan peserta tidak merusak area berikut:

- `resources/views/admin/sessions/show.blade.php`: kolom `SKOR RAW` dan `SKOR IRT` tetap tampil untuk superadmin/admin sesi.
- `App\Http\Controllers\Admin\ExamSessionController::exportResults()`: CSV tetap berisi `Skor Raw` dan `Skor IRT`.
- Form sesi admin tetap memiliki konfigurasi `max_score_raw`.
- Report card admin dan print report tetap boleh memakai data internal yang dibutuhkan.

Tambahkan tampilan batas bawah/atas dan tabel ambang predikat pada detail sesi admin agar konfigurasi dapat diaudit tanpa membuka modal edit.

## 12. Tahapan Implementasi

Kerjakan sesuai urutan berikut. Jangan mengerjakan semua perubahan sekaligus.

### Task 0 — Baseline dan inventarisasi

**Tujuan:** Pastikan kondisi awal diketahui sebelum mengubah kode.

**Langkah:**

1. Jalankan test suite saat ini.
2. Catat kegagalan yang sudah ada dan jangan memperbaiki kegagalan yang tidak berhubungan.
3. Jalankan pencarian raw score dan simpan daftar lokasi sebagai checklist.

**Verifikasi:**

```bash
composer test
rg -n -i "skor raw|skor mentah|raw score|raw_score|rawScores|result->score" app resources/views routes
```

**Dependencies:** Tidak ada.
**Estimasi:** XS, tanpa perubahan file.

### Task 1 — Migration dan model konfigurasi

**Tujuan:** Menyediakan kolom batas IRT dan ambang predikat, termasuk data lama.

**Files kemungkinan diubah:**

- migration baru di `database/migrations/`
- `app/Models/ExamSession.php`
- `app/Models/ExamSessionCategory.php`
- test baru `tests/Feature/ExamSessionIrtConfigurationTest.php`

**Acceptance criteria:**

- Migration `up()` menambah lima kolom yang dijelaskan pada kontrak data.
- Sesi lama mendapat default ambang yang valid.
- Migration `down()` hanya menghapus kolom baru dan tidak menyentuh data raw.
- Model dapat mass-assign dan membaca nilai baru dengan presisi dua desimal.

**Verifikasi:**

```bash
php artisan test --filter=ExamSessionIrtConfigurationTest
php artisan migrate:fresh --env=testing
```

**Dependencies:** Task 0.
**Estimasi:** M, maksimal 4 file.

### Checkpoint A — Fondasi data

- [ ] Migration berhasil pada database kosong.
- [ ] Migration berhasil pada fixture yang memiliki sesi lama.
- [ ] Rollback migration tidak menghapus kolom raw/IRT lama.
- [ ] Model mengembalikan semua konfigurasi baru.

### Task 2 — Form create/edit dan validasi backend

**Tujuan:** Admin dapat menyimpan konfigurasi batas serta predikat yang valid.

**Files kemungkinan diubah:**

- `resources/views/admin/sessions/index.blade.php`
- `app/Http/Controllers/Admin/ExamSessionController.php`
- `app/Services/ExamSessionService.php`
- `tests/Feature/ExamSessionIrtConfigurationTest.php`

**Acceptance criteria:**

- Create dan edit mengirim, memvalidasi, menyimpan, serta memuat kembali semua nilai baru.
- Backend menolak batas bawah >= batas atas.
- Backend menolak empat predikat yang tidak berurutan.
- Backend menolak `Kurang` yang tidak sama dengan total batas bawah dan `Istimewa` yang melebihi total batas atas.
- Perubahan scoring pada sesi yang sudah mulai ditolak tanpa mengubah data apa pun.
- Perubahan scoring tidak menjalankan generate ulang soal.

**Verifikasi:**

```bash
php artisan test --filter=ExamSessionIrtConfigurationTest
npm run build
```

Manual:

1. Buat sesi dengan satu mata pelajaran.
2. Edit sesi dan pastikan semua angka terisi kembali.
3. Coba urutan predikat salah dan pastikan pesan server mudah dipahami.
4. Mulai satu participant, lalu coba ubah batas IRT dan pastikan request ditolak.

**Dependencies:** Task 1.
**Estimasi:** M, 4 file.

### Task 3 — Algoritma rentang IRT dan predikat

**Tujuan:** Menghasilkan nilai di antara batas bawah dan atas serta satu predikat konsisten.

**Files kemungkinan diubah:**

- `app/Services/AssessmentService.php`
- `app/Models/ExamSession.php`
- test baru `tests/Feature/AssessmentIrtBoundsTest.php`

**Acceptance criteria:**

- Salah/kosong semua menghasilkan tepat batas bawah setiap kategori.
- Benar semua menghasilkan tepat batas atas setiap kategori.
- Jawaban parsial mengikuti rumus linear dan tidak keluar rentang.
- Total IRT sama dengan jumlah skor IRT kategori yang sudah dibulatkan.
- Predikat tepat pada semua sisi ambang.
- Menjalankan kalkulasi ulang menghasilkan nilai yang sama untuk input yang sama.
- Raw score masih dihitung dan disimpan.

**Kasus test minimum:**

1. Satu kategori, semua salah.
2. Satu kategori, semua kosong.
3. Satu kategori, semua benar.
4. Satu kategori dengan partial credit.
5. Dua kategori dengan batas berbeda.
6. Skor tepat pada ambang Memadai, Baik, dan Istimewa.
7. `score_incorrect` negatif tetap tidak menurunkan IRT di bawah batas bawah.

**Verifikasi:**

```bash
php artisan test --filter=AssessmentIrtBoundsTest
```

**Dependencies:** Task 1 dan Task 2.
**Estimasi:** M, 3 file.

### Checkpoint B — Mesin penilaian

- [ ] Semua kasus batas lulus.
- [ ] Nilai tersimpan maksimal dua angka desimal.
- [ ] Predikat berasal dari method tunggal pada `ExamSession`.
- [ ] Tidak ada perubahan pada rumus raw score.

### Task 4 — Halaman sukses dan lihat hasil peserta

**Tujuan:** Dua halaman hasil utama hanya menampilkan IRT dan predikat.

**Files kemungkinan diubah:**

- `app/Http/Controllers/ExamController.php`
- `resources/views/exam/success.blade.php`
- `resources/views/participant/result.blade.php`
- test baru `tests/Feature/ParticipantScoreVisibilityTest.php`

**Acceptance criteria:**

- HTML kedua halaman tidak mengandung label atau angka raw.
- Controller tidak mengirim variabel raw ke view peserta.
- Total IRT, predikat, dan IRT per mata pelajaran tampil.
- Batas nilai yang ditampilkan sesuai konfigurasi sesi.

**Verifikasi:**

```bash
php artisan test --filter=ParticipantScoreVisibilityTest
```

**Dependencies:** Task 3.
**Estimasi:** M, 4 file.

### Task 5 — Dashboard, grafik percobaan, dan statistik peserta

**Tujuan:** Semua grafik dan leaderboard peserta hanya menggunakan IRT.

**Files kemungkinan diubah:**

- `app/Http/Controllers/Participant/DashboardController.php`
- `resources/views/participant/dashboard.blade.php`
- `resources/views/participant/session_detail.blade.php`
- `resources/views/participant/statistics.blade.php`
- `tests/Feature/ParticipantScoreVisibilityTest.php`

**Acceptance criteria:**

- Dashboard dan grafik percobaan mempunyai dataset IRT saja.
- Source HTML/JavaScript tidak memuat array raw.
- Statistik sementara maupun final diurutkan berdasarkan IRT.
- IRT bernilai `0` tetap diperlakukan sebagai nilai valid.
- Tie-breaker tidak membocorkan raw.
- Predikat yang ditampilkan cocok dengan skor IRT setiap attempt.

**Verifikasi:**

```bash
php artisan test --filter=ParticipantScoreVisibilityTest
npm run build
```

**Dependencies:** Task 3.
**Estimasi:** M, 5 file.

### Task 6 — Review dan analisis AI peserta

**Tujuan:** Menghapus raw dari review serta seluruh jalur AI peserta, termasuk data tersimpan lama.

**Files kemungkinan diubah:**

- `resources/views/participant/review.blade.php`
- `app/Http/Controllers/Participant/DashboardController.php`
- `app/Services/AIService.php`
- `app/Jobs/GenerateAiAnalysisJob.php`
- migration/data cleanup atau test visibility yang sudah dibuat

**Acceptance criteria:**

- Review hanya menampilkan IRT dan predikat.
- Prompt AI participant tidak mempunyai field atau kata `raw score`/`skor mentah`.
- Endpoint AI participant tidak mengirim raw score.
- AI menunggu hasil IRT tersedia dan tidak membuat placeholder IRT palsu.
- Analisis peserta lama yang menyebut raw tidak lagi ditampilkan; data dihapus atau digenerate ulang.
- Flow AI report card admin tidak berubah.

**Verifikasi:**

```bash
php artisan test --filter=ParticipantScoreVisibilityTest
rg -n -i "raw_score|skor mentah|raw score" app/Http/Controllers/Participant app/Jobs/GenerateAiAnalysisJob.php resources/views/participant resources/views/exam
```

Setiap hasil pencarian yang tersisa harus dijelaskan sebagai penggunaan internal yang tidak terkirim ke peserta. Target ideal untuk folder/view peserta adalah nol hasil.

**Dependencies:** Task 3.
**Estimasi:** M, maksimal 5 file per commit; pisahkan cleanup data lama bila migration membuat task melebihi batas.

### Task 7 — Detail admin dan regression test role

**Tujuan:** Memastikan raw tetap tersedia hanya pada role admin yang diizinkan.

**Files kemungkinan diubah:**

- `resources/views/admin/sessions/show.blade.php`
- `app/Http/Controllers/Admin/ExamSessionController.php` bila predikat ditambahkan ke CSV/detail
- `tests/Feature/AdminScoreVisibilityTest.php`

**Acceptance criteria:**

- Superadmin melihat raw dan IRT pada detail sesi.
- Admin sesi melihat raw dan IRT pada sesi yang dapat diaksesnya.
- Export CSV admin tetap memuat kedua skor.
- Peserta tidak dapat mengakses route admin.
- Detail admin menampilkan batas bawah/atas per kategori serta empat ambang predikat.

**Verifikasi:**

```bash
php artisan test --filter=AdminScoreVisibilityTest
```

**Dependencies:** Task 2 dan Task 3.
**Estimasi:** S, 3 file.

### Checkpoint C — Fitur lengkap

- [ ] Create/edit konfigurasi berhasil.
- [ ] Rumus batas bawah/atas teruji.
- [ ] Empat predikat teruji pada boundary.
- [ ] Tidak ada raw pada output peserta.
- [ ] Raw tetap ada pada output superadmin/admin sesi.
- [ ] Semua test dan build lulus.

## 13. Urutan Dependensi

```text
Task 0: baseline
  -> Task 1: schema + model
       -> Task 2: form + validation
       -> Task 3: scoring + predicate
            -> Task 4: success + result peserta
            -> Task 5: dashboard + statistik peserta
            -> Task 6: review + AI peserta
       -> Task 7: audit tampilan admin
```

Task 4, 5, dan 6 boleh dikerjakan paralel hanya setelah kontrak model dan method predikat pada Task 3 selesai. Karena beberapa task menyentuh `DashboardController`, koordinasikan commit agar tidak saling menimpa.

## 14. Test Plan Ringkas

### Automated tests wajib

- `ExamSessionIrtConfigurationTest`
  - simpan create/update yang valid;
  - invalid min/max;
  - invalid urutan predikat;
  - invalid terhadap total rentang;
  - scoring config terkunci setelah participant mulai;
  - data sesi lama mendapat default migration.
- `AssessmentIrtBoundsTest`
  - all wrong, all blank, all correct, partial, multi-category;
  - clamp negatif/lebih dari maksimum;
  - total dan rounding;
  - boundary predikat.
- `ParticipantScoreVisibilityTest`
  - dashboard, detail sesi, success, result, review, statistics;
  - respons endpoint analisis AI;
  - HTML/JSON tidak memuat raw.
- `AdminScoreVisibilityTest`
  - superadmin dan admin sesi tetap melihat raw;
  - peserta ditolak dari route admin;
  - CSV admin tetap berisi raw.

### Manual QA minimum

1. Buat sesi satu kategori dengan IRT `200–1000`, predikat `200/500/700/850`.
2. Kerjakan attempt dengan semua jawaban salah; hasil harus `200` dan `Kurang`.
3. Kerjakan attempt dengan semua jawaban benar; hasil harus `1000` dan `Istimewa`.
4. Buat sesi dua kategori dengan batas berbeda; pastikan total minimum/maksimum adalah jumlah keduanya.
5. Periksa dashboard, sukses, hasil, review, detail sesi, statistik, source HTML, dan network response sebagai peserta.
6. Periksa detail sesi serta CSV sebagai superadmin dan admin sesi; raw harus tetap ada.
7. Jalankan analisis AI peserta dan pastikan narasi tidak menyebut raw/mentah.
8. Coba edit nilai sesi setelah participant mulai; perubahan harus ditolak tanpa partial update.

## 15. Perintah Verifikasi Akhir

Jalankan dari root repository:

```bash
composer test
./vendor/bin/pint --test
npm run build
git diff --check
rg -n -i "skor raw|skor mentah|raw score|raw_score|rawScores" resources/views/participant resources/views/exam app/Http/Controllers/Participant app/Jobs/GenerateAiAnalysisJob.php
```

Review setiap hasil `rg`. Tidak semua penggunaan internal `score` harus dihapus; yang dilarang adalah pengiriman atau tampilan raw kepada peserta.

## 16. Risiko dan Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Rumus hanya menambahkan minimum tanpa mengurangi range | Nilai dapat melewati batas atas | Gunakan `min + ratio × (max - min)` dan test all-correct |
| Predicate dibandingkan dengan skor yang belum dibulatkan | Tampilan angka dan predikat tampak bertentangan | Bulatkan skor kategori dan total ke dua desimal sebelum menentukan predikat |
| Konfigurasi diubah setelah ada hasil | Nilai/predikat lama tidak konsisten | Kunci scoring config setelah participant mulai |
| Raw hanya disembunyikan dengan CSS | Nilai masih bocor di source/network | Hapus raw dari controller payload dan JavaScript, lalu test response |
| Fallback raw pada statistik tetap aktif | Peserta masih melihat/terpengaruh raw | Hapus seluruh fallback dan anggap IRT `0` sebagai valid |
| Narasi AI lama menyebut raw | Raw tetap terlihat walau view sudah bersih | Purge/regen cache analisis participant saat rollout |
| Migration data lama memakai model aplikasi | Migration dapat rusak setelah model berubah | Gunakan query builder dalam migration |
| IRT berubah ketika peserta lain selesai | Ranking/predikat sementara dapat berubah | Jelaskan pada statistik sementara dan invalidasi cache setelah kalkulasi |

## 17. Di Luar Scope

Jangan melakukan hal berikut dalam issue ini:

- Mengganti model matematika atau bobot kesulitan IRT yang sudah ada.
- Menghapus raw score dari database atau area admin.
- Menambah package/dependency baru.
- Membuat tabel khusus predikat; empat kolom pada sesi sudah cukup untuk empat tipe tetap.
- Membuat predikat per mata pelajaran.
- Mengubah role/permission selain menguji bahwa peserta tidak dapat membuka area admin.
- Merombak desain seluruh halaman; cukup sesuaikan komponen skor yang ada.

## 18. Definition of Done

Fitur dianggap selesai hanya jika:

- [ ] Seluruh acceptance criteria setiap task terpenuhi.
- [ ] Test baru terbukti gagal sebelum implementasi dan lulus sesudah implementasi.
- [ ] Seluruh test lama tetap lulus.
- [ ] Migration dan rollback teruji.
- [ ] Build frontend dan Pint lulus.
- [ ] Flow create sesi sampai peserta melihat hasil diuji secara runtime.
- [ ] Audit source HTML dan network memastikan raw tidak bocor ke peserta.
- [ ] Raw tetap dapat dilihat superadmin/admin sesi.
- [ ] Tidak ada dependency, abstraction, atau refactor di luar kebutuhan issue.
- [ ] Product owner mereview hasil sebelum deploy.

## 19. Keputusan yang Perlu Dikonfirmasi Product Owner

Rencana ini dapat langsung dipakai dengan default berikut bila tidak ada koreksi:

1. Batas bawah/atas IRT dikonfigurasi per mata pelajaran.
2. Predikat hanya untuk total IRT sesi.
3. Empat input predikat berarti nilai minimum, bukan nilai maksimum/range ganda.
4. Minimum `Kurang` harus sama dengan total batas bawah IRT agar seluruh skor mempunyai predikat.
5. Default sesi lama dan form baru memakai titik 0%, 50%, 70%, dan 85% dari rentang IRT.
6. Konfigurasi nilai tidak dapat diedit setelah peserta mulai mengerjakan.

Jika salah satu keputusan tersebut berubah, perbarui bagian kontrak data, validasi, algoritma, dan test plan sebelum coding.
