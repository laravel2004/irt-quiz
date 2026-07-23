# Issue: Penyesuaian Penilaian Parsial (Partial Scoring) untuk Tipe Soal Multiple Choice dan Multiple Benar-Salah

## Deskripsi
Sistem saat ini perlu diperbaiki dalam memberikan penilaian (scoring) untuk tipe soal **Multiple Choice** (pilihan ganda kompleks / lebih dari satu jawaban benar) dan **Multiple Benar-Salah**. 

Sesuai dengan *requirement*:
> "Untuk jawaban multiple choice dan multiple benar salah itu kalau ada salah satu jawaban yang dijawab salah, tetap salahkan soal tersebut (`is_correct = false`) tapi masih dikasih nilai persentase sesuai dengan jawaban benar-nya. Jadi kalau ada 3 jawaban benar dari 5 jawaban, maka kehitungnya adalah persentasenya."

## Target File
- `app/Http/Controllers/ExamController.php` (di dalam method `submitCategory`)

## Masalah Saat Ini pada Sistem
1. **Multiple Choice (Pilihan Ganda Kompleks)**: Saat ini, jika *user* memilih semua opsi (termasuk opsi yang salah), sistem hanya menghitung opsi benar yang dipilih dan bisa memberikan status `is_correct = true` serta nilai 100%. Tidak ada deteksi jika *user* memilih jawaban yang salah.
2. **Multiple Benar-Salah**: Perlu dipastikan bahwa jika ada 1 saja pernyataan yang dijawab tidak tepat, status soal secara keseluruhan adalah salah (`is_correct = false`), namun *user* tetap mendapatkan proporsi nilai sesuai jumlah pernyataan yang dijawab benar.

---

## Tahapan Implementasi (Step-by-Step)

Untuk programmer junior atau AI Model, silakan ikuti tahapan berikut secara berurutan:

### Langkah 1: Modifikasi Logika `multiple_choice`
1. Buka file `app/Http/Controllers/ExamController.php` dan cari blok kode `elseif ($question->type === 'multiple_choice')`.
2. Ubah cara menghitung jawaban benar dan deteksi jawaban salah dengan logika berikut:
   - Hitung jumlah jawaban benar yang dipilih *user*: `$correctSelected = count(array_intersect($userIndices, $correctIndices));`
   - Hitung jumlah jawaban **SALAH** yang dipilih *user*: `$wrongSelected = count(array_diff($userIndices, $correctIndices));`
   - Hitung total jawaban benar yang tersedia pada kunci jawaban: `$totalCorrectAvailable = count($correctIndices);`
3. Tentukan status `is_correct`:
   - Soal HANYA dianggap benar sempurna (`$isCorrect = true`) **JIKA** *user* memilih SEMUA jawaban benar (`$correctSelected === $totalCorrectAvailable`) **DAN** TIDAK memilih jawaban salah sama sekali (`$wrongSelected === 0`).
   - Jika kondisi di atas tidak terpenuhi, maka pastikan `$isCorrect = false`.
4. Tentukan `score` (Persentase Nilai):
   - **Pencegahan Kecurangan**: Untuk mencegah *user* mencentang semua pilihan agar mendapat persentase benar, berikan penalti dari jawaban yang salah.
     Rumus: `$netCorrect = max(0, $correctSelected - $wrongSelected);`
   - Hitung persentasenya: `$percentage = $totalCorrectAvailable > 0 ? ($netCorrect / $totalCorrectAvailable) : 0;`
   - Jika `$percentage == 0`, skor diambil dari nilai salah: `$score = $question->score_incorrect ?? 0;`
   - Jika `$percentage > 0`, skor dihitung proporsional: `$score = round($percentage * ($question->score_correct ?? 1), 2);`

### Langkah 2: Modifikasi Logika `multiple_benar_salah`
1. Cari blok kode `elseif ($question->type === 'multiple_benar_salah')` pada file yang sama.
2. Hitung `$totalStatements = count($options);`.
3. Hitung berapa jumlah pernyataan yang dijawab dengan benar (`$correctCount`). (Logika *loop* saat ini sudah benar, tinggal disesuaikan penilaiannya).
4. Tentukan status `is_correct`:
   - Jika *user* menjawab semua pernyataan dengan benar (`$correctCount === $totalStatements`), maka `$isCorrect = true`.
   - Jika ada minimal 1 pernyataan yang dijawab salah (`$correctCount < $totalStatements`), maka paksa `$isCorrect = false`.
5. Tentukan `score` (Persentase Nilai) saat `$isCorrect = false`:
   - Hitung persentase jawaban benar: `$percentage = $totalStatements > 0 ? ($correctCount / $totalStatements) : 0;`
   - Jika `$percentage == 0` (salah semua), maka `$score = $question->score_incorrect ?? 0;`.
   - Jika `$percentage > 0` (misal benar 3 dari 5 pernyataan), maka `$score = round($percentage * ($question->score_correct ?? 1), 2);`.

### Langkah 3: Testing & Verifikasi
Pastikan implementasi lolos kasus pengujian berikut:

- **Kasus Uji A (Multiple Benar-Salah)**
  - Kondisi: Soal memiliki 5 pernyataan. Nilai benar = 10, nilai salah = 0.
  - Aksi: User menjawab 3 benar dan 2 salah.
  - Hasil yang diharapkan: `is_correct = false` (karena ada jawaban salah), dan `score = 6` (karena persentase 3/5 * 10).

- **Kasus Uji B (Multiple Choice)**
  - Kondisi: Terdapat opsi A, B, C, D. Kunci jawaban adalah A dan B (total 2 kunci benar). Nilai benar = 10, nilai salah = 0.
  - Aksi 1: *User* menjawab A dan B -> Hasil: `is_correct = true`, `score = 10`.
  - Aksi 2: *User* menjawab A saja -> Hasil: `is_correct = false`, `score = 5` (1 benar, 0 salah).
  - Aksi 3: *User* menjawab A, B, dan C -> Hasil: `is_correct = false`, `score = 5` (2 benar dikurangi 1 salah = 1 net, maka 1/2 * 10).
  - Aksi 4: *User* menjawab semua opsi (A, B, C, D) -> Hasil: `is_correct = false`, `score = 0` (2 benar dikurangi 2 salah = 0, dapat skor salah).

---
Selesai. Blueprint ini dibuat sangat eksplisit agar mudah diimplementasikan baik oleh *junior programmer* maupun model AI yang lebih efisien.
