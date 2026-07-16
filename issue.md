# Implementasi Perhitungan Skor Proporsional untuk Soal Benar/Salah Majemuk (Multiple Benar/Salah)

**Tujuan**: Memperbaiki dan menyelaraskan logika penilaian untuk tipe soal `multiple_benar_salah` agar sejalan dengan `multiple_choice`. Walaupun saat ini sudah menggunakan persentase, kita perlu memastikan bahwa jika peserta menjawab salah semua (persentase 0), sistem akan memberikan nilai penalti (berdasarkan `score_incorrect`) alih-alih 0 biasa, dan strukturnya lebih konsisten.

**Target File yang akan dimodifikasi**:
`app/Http/Controllers/ExamController.php`

---

## Langkah-Langkah Implementasi

### 1. Buka File Target
Buka file `app/Http/Controllers/ExamController.php`.

### 2. Temukan Blok Logika `multiple_benar_salah`
Cari bagian kode yang menangani `elseif ($question->type === 'multiple_benar_salah')`. Letaknya tepat di bawah blok `multiple_choice` (sekitar baris 294).

Kode lama saat ini:
```php
} elseif ($question->type === 'multiple_benar_salah') {
    if (is_array($answer)) {
        $totalStatements = count($options);
        $correctCount = 0;
        
        foreach ($options as $idx => $optText) {
            $userAnswer = $answer[strval($idx)] ?? null;
            $shouldBeBenar = in_array(strval($idx), $correctArr);
            
            if (($shouldBeBenar && $userAnswer === 'benar') || (!$shouldBeBenar && $userAnswer === 'salah')) {
                $correctCount++;
            }
        }
        
        $percentage = $totalStatements > 0 ? ($correctCount / $totalStatements) : 0;
        $score = round($percentage * ($question->score_correct ?? 1), 2);
        $isCorrect = ($correctCount === $totalStatements);
    }
}
```

### 3. Sesuaikan Logika Perhitungan (Tambahkan Handler Nilai Salah)
Ubah blok kode tersebut untuk menangani kasus saat persentase 0 (salah semua) agar mengambil `score_incorrect`, serta menambahkan sedikit penyempurnaan kode.

Ganti dengan kode berikut:
```php
} elseif ($question->type === 'multiple_benar_salah') {
    $isCorrect = false;
    if (is_array($answer)) {
        $totalStatements = count($options);
        $correctCount = 0;
        
        foreach ($options as $idx => $optText) {
            $userAnswer = $answer[strval($idx)] ?? null;
            $shouldBeBenar = in_array(strval($idx), $correctArr);
            
            if (($shouldBeBenar && $userAnswer === 'benar') || (!$shouldBeBenar && $userAnswer === 'salah')) {
                $correctCount++;
            }
        }
        
        $percentage = $totalStatements > 0 ? ($correctCount / $totalStatements) : 0;
        $score = round($percentage * ($question->score_correct ?? 1), 2);
        
        if ($correctCount === $totalStatements) {
            $isCorrect = true;
        } else if ($percentage == 0) {
            // Jika salah semua, ambil nilai penalti (score_incorrect)
            $score = $question->score_incorrect ?? 0;
        }
    } else {
        $score = $question->score_incorrect ?? 0;
    }
}
```

### 4. Pengujian (Testing)
- Jawab soal berjenis "Benar/Salah Majemuk".
- Coba jawab salah pada seluruh pernyataan dan pastikan skor yang didapat bukan 0 biasa melainkan nilai dari `score_incorrect` (misalnya jika diset -1, maka harusnya dapat -1).
- Coba jawab separuh benar dan pastikan skornya adalah persentase (proporsional).
