# Implementasi Fitur Pembahasan Soal di Halaman Preview

**Tujuan**: Menambahkan tampilan "Pembahasan Soal" di setiap soal pada halaman Preview Soal Sesi (contoh URL: `http://localhost:8000/admin/sessions/3/preview-questions`), sehingga admin dapat melihat kunci dan pembahasannya sekaligus.

**Target File yang akan dimodifikasi**:
`resources/views/admin/sessions/preview.blade.php`

---

## Langkah-Langkah Implementasi

### 1. Buka File Target
Buka file `resources/views/admin/sessions/preview.blade.php`.

### 2. Temukan Bagian Akhir dari Render Opsi Jawaban
Di dalam file tersebut, terdapat *looping* untuk merender daftar soal menggunakan `@foreach($session->questions as $index => $question)`.
Scroll ke bawah di dalam *looping* tersebut, dan cari bagian blok di mana *opsi jawaban* selesai dirender (tepat sebelum penutup `</div>` dari sebuah *card* soal). 
Kode penutupnya terlihat seperti ini (sekitar baris 177):

```html
        @endif
    </div>
    @endforeach
```

### 3. Tambahkan Blok Pembahasan Soal
Sisipkan kode untuk menampilkan pembahasan soal tepat di atas penutup div dari masing-masing soal. Kita akan menggunakan kondisi `@if($question->explanation)` agar blok pembahasan hanya muncul apabila soal tersebut memang memiliki pembahasan.

Tambahkan kode HTML berikut ini:

```html
        <!-- Tambahan: Blok Pembahasan Soal -->
        @if($question->explanation)
        <div style="margin-top: 24px; padding: 16px; border-radius: 10px; background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.2);">
            <div style="font-weight: 600; color: #3b82f6; margin-bottom: 8px; font-size: 0.9rem;">
                <i class="fas fa-lightbulb"></i> Pembahasan:
            </div>
            <div class="session-preview-content" style="font-size: 0.95rem; color: #0f172a;">
                {!! $question->explanation !!}
            </div>
        </div>
        @endif
        <!-- Akhir Blok Pembahasan -->
```

### 4. Hasil Akhir Penempatan Kode
Pastikan kodenya tersusun rapi seperti ini:

```html
            </div>
        @endif
        
        <!-- Tambahan: Blok Pembahasan Soal -->
        @if($question->explanation)
        <div style="margin-top: 24px; padding: 16px; border-radius: 10px; background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.2);">
            <div style="font-weight: 600; color: #3b82f6; margin-bottom: 8px; font-size: 0.9rem;">
                <i class="fas fa-lightbulb"></i> Pembahasan:
            </div>
            <div class="session-preview-content" style="font-size: 0.95rem; color: #0f172a;">
                {!! $question->explanation !!}
            </div>
        </div>
        @endif
        <!-- Akhir Blok Pembahasan -->
        
    </div>
    @endforeach
```

### 5. Pengujian (Testing)
- Buka browser dan arahkan ke halaman preview soal dari salah satu sesi, misalnya `http://localhost:8000/admin/sessions/3/preview-questions`.
- Gulir/scroll ke daftar soal yang tampil.
- Pastikan bahwa untuk soal-soal yang sebelumnya telah Anda isikan teks pembahasannya, terdapat sebuah kotak berwarna biru muda dengan ikon lampu di bawah daftar opsi jawaban, yang berisi teks pembahasan lengkap dari soal tersebut.
- Pastikan juga soal yang tidak memiliki pembahasan (kosong) tidak menampilkan kotak biru tersebut secara berantakan.
