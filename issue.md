# Fitur: Mengizinkan Edit Konfigurasi Tampilan Skor & Ambang Predikat untuk Sesi Berjalan

## Deskripsi Masalah
Saat ini, jika sebuah sesi ujian (Tryout) sudah mulai dikerjakan oleh peserta (terdapat data `started_at`), sistem akan memblokir semua perubahan pada konfigurasi penilaian melalui validasi service dengan pesan error:
`"Konfigurasi nilai tidak dapat diubah karena sesi sudah mulai dikerjakan."`

Pembatasan ini terlalu ketat. Kita hanya perlu mengunci parameter penilaian inti yang mempengaruhi kalkulasi nilai, yaitu `min_score_irt`, `max_score_irt`, dan `max_score_raw`. Sedangkan pengaturan tampilan dan ambang predikat seharusnya bisa diubah kapan saja.

Pengaturan yang **harus bisa diedit** meskipun sesi sudah berjalan:
1. Skor yang ditampilkan ke peserta (`participant_score_display`)
2. Ambang predikat raw (`predicate_raw_kurang_min`, `predicate_raw_memadai_min`, `predicate_raw_baik_min`, `predicate_raw_istimewa_min`)
3. Ambang predikat irt (`predicate_kurang_min`, `predicate_memadai_min`, `predicate_baik_min`, `predicate_istimewa_min`)

## Tahapan Implementasi

Untuk junior programmer atau AI model, silakan ikuti langkah-langkah detail berikut:

### 1. Ubah Deteksi Perubahan di `ExamSessionService`
Buka file `app/Services/ExamSessionService.php` dan cari metode `scoringSignature()`.
Metode ini digunakan untuk membuat "tanda tangan" (signature) dari konfigurasi penilaian. Jika signature ini berubah, maka sistem akan menganggap ada perubahan konfigurasi penilaian dan memicu error.

Saat ini metode tersebut memantau `categories`, `thresholds`, `raw_thresholds`, dan `participant_score_display`.
**Tugas Anda:** 
Ubah agar metode ini **hanya** memantau `categories` (yang berisi `raw_max`, `min`, dan `max` dari tiap kategori). Hapus pemantauan untuk `thresholds`, `raw_thresholds`, dan `participant_score_display`.

Contoh perubahan:
```php
// Sebelum:
return json_encode([
    'categories' => $categories,
    'thresholds' => $thresholds->map(fn ($value) => number_format((float) $value, 2, '.', ''))->all(),
    'raw_thresholds' => $rawThresholds->map(fn ($value) => number_format((float) $value, 2, '.', ''))->all(),
    'participant_score_display' => $display,
], JSON_THROW_ON_ERROR);

// Sesudah (Hanya sertakan categories):
return json_encode([
    'categories' => $categories,
], JSON_THROW_ON_ERROR);
```

### 2. Update Unit Test untuk Tampilan Skor
Karena kita sekarang mengizinkan perubahan tampilan skor dan predikat pada sesi yang sudah berjalan, test yang sebelumnya memastikan hal itu ditolak kini harus diubah.

Buka file `tests/Feature/ExamSessionScoreDisplayTest.php`:
1. Cari method `test_started_session_rejects_score_display_changes()`.
2. Ubah nama method menjadi `test_started_session_allows_score_display_changes()` (atau sejenisnya).
3. Hapus baris yang mengharapkan exception:
   ```php
   $this->expectException(DomainException::class);
   $this->expectExceptionMessage('Konfigurasi nilai tidak dapat diubah karena sesi sudah mulai dikerjakan.');
   ```
4. Ubah logic-nya agar memanggil `$service->updateWithCategories($session->id, $data);` secara sukses, dan pastikan data di database (tabel `exam_sessions`) benar-benar terupdate dengan `participant_score_display` yang baru (contohnya bisa dipastikan menggunakan `$this->assertDatabaseHas('exam_sessions', ['participant_score_display' => 'raw']);`).

### 3. Pastikan Test yang Lain Tetap Lulus
Di file `tests/Feature/ExamSessionIrtConfigurationTest.php`, terdapat test `test_started_session_rejects_scoring_configuration_changes()`.
Test ini mencoba mengubah `min_score_irt` dan `predicate_kurang_min`. Karena parameter inti `min_score_irt` (yang berada di dalam object `categories`) diubah, maka sistem **harus tetap** menolak perubahannya dan mengeluarkan error `DomainException`.
Anda tidak perlu mengubah test ini, cukup jalankan `php artisan test` dan pastikan test ini tetap *Passed / Lulus*.

---

**Ceklist Akhir:**
- [ ] `scoringSignature` di `ExamSessionService.php` sudah diubah untuk hanya me-return properties `categories`.
- [ ] Test `test_started_session_rejects_score_display_changes` telah di-refactor menjadi sukses berjalan ketika dilakukan update pada session display mode.
- [ ] Menjalankan command `php artisan test` sukses tanpa ada yang failed.
