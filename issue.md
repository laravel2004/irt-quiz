# Rencana Implementasi: Tipe Soal Baru `essai_singkat` (Essay Singkat)

Dokumen ini disusun sebagai panduan spesifikasi teknis dan implementasi langkah-demi-langkah bagi **Junior Programmer** atau **Model AI (Cost-effective / Budget AI)** untuk menambahkan tipe soal baru **`essai_singkat`** pada sistem JagoBelajar Tryout IRT & Modul Quiz.

---

## 1. Ringkasan & Spesifikasi Fitur

### 1.1. Konteks Saat Ini
Saat ini sistem memiliki 4 tipe soal:
1. `pilihan_ganda`: Pilihan ganda standar (single choice A, B, C, D, ...).
2. `benar_salah`: Pilihan benar atau salah (single choice).
3. `multiple_choice`: Pilihan ganda kompleks (memilih lebih dari satu opsi yang benar).
4. `multiple_benar_salah`: Pernyataan majemuk di mana setiap baris ditentukan benar atau salah.

### 1.2. Kebutuhan Fitur Baru: `essai_singkat`
Dibutuhkan tipe soal ke-5 bernama **`essai_singkat`** (Essay Singkat / Isian Singkat).

### 1.3. Aturan Penilaian (Grading Rules)
1. **Pencocokan Kunci Jawaban:**
   Jawaban peserta dinyatakan **BENAR** jika teks yang diketik sama dengan isian kunci jawaban yang ditentukan oleh admin/guru.
2. **Case-Insensitive (Abaikan Huruf Besar/Kecil):**
   Perbedaan huruf kapital dan huruf kecil **TIDAK** menggugurkan kebenaran jawaban.
   *Contoh:* Kunci `"Jakarta"`, peserta menjawab `"jakarta"` atau `"JAKARTA"` $\rightarrow$ **BENAR**.
3. **Space-Insensitive (Normalisasi Spasi):**
   - Spasi di awal (*leading space*) dan di akhir (*trailing space*) dibersihkan (*trimmed*).
   - Spasi ganda atau berlebih di tengah kalimat dinormalisasi menjadi 1 spasi tunggal.
   *Contoh:* Kunci `"Bhinneka Tunggal Ika"`, peserta menjawab `"  bhinneka   tunggal   ika  "` $\rightarrow$ **BENAR**.
4. **Jawaban Kosong:**
   Jika peserta hanya mengirim spasi kosong `""` atau `"   "`, otomatis dinilai **SALAH** (skor tidak diberikan).
5. **Skor:**
   - Jika benar: Mendapatkan `score_correct` (default 1).
   - Jika salah / kosong: Mendapatkan `score_incorrect` (default 0).

---

## 2. Format Data & Karakteristik Model

| Field Model | Nilai untuk `essai_singkat` | Keterangan |
| :--- | :--- | :--- |
| `type` | `'essai_singkat'` | Nilai enum/string tipe soal baru |
| `options` | `null` atau `[]` | Tipe essay tidak membutuhkan opsi pilihan A/B/C/D |
| `correct_answer` | `["Jakarta"]` | Disimpan sebagai array JSON berisi string kunci jawaban |
| `answer` (User Answer) | `"Jakarta"` | Jawaban siswa disimpan sebagai teks string langsung |

---

## 3. Algoritma Normalisasi Jawaban (Helper Normalizer)

Gunakan formula normalisasi standar berikut di seluruh controller, service, dan checker:

```php
/**
 * Normalisasi teks jawaban essay:
 * 1. Trim spasi di awal dan akhir string.
 * 2. Ubah multiple spasi berurutan / tabs / newlines menjadi 1 spasi tunggal.
 * 3. Ubah seluruh huruf menjadi lowercase (menggunakan mb_strtolower UTF-8).
 */
function normalizeEssayAnswer(mixed $text): string
{
    if ($text === null) {
        return '';
    }
    $str = is_scalar($text) ? (string) $text : '';
    $normalizedWhitespace = preg_replace('/\s+/', ' ', trim($str));
    return mb_strtolower($normalizedWhitespace, 'UTF-8');
}
```

### Tabel Uji Kasus Normalisasi:
| Kunci Jawaban | Jawaban Peserta | Hasil | Alasan |
| :--- | :--- | :---: | :--- |
| `Jakarta` | `jakarta` | ✅ BENAR | Case-insensitive |
| `Jakarta` | `JAKARTA` | ✅ BENAR | Case-insensitive |
| `Jakarta` | `  Jakarta   ` | ✅ BENAR | Leading & trailing space di-trim |
| `Bhinneka Tunggal Ika` | `bhinneka   tunggal   ika` | ✅ BENAR | Multiple whitespace dinormalisasi |
| `Fotosintesis` | `Fotosintesa` | ❌ SALAH | Beda kata/ejaan |
| `Jakarta` | `""` (spasi saja) | ❌ SALAH | Kosong tidak boleh dihitung benar |

---

## 4. Daftar File yang Terlibat

| No | File | Jenis Perubahan | Deskripsi |
|---|---|---|---|
| 1 | `database/migrations/xxxx_add_essai_singkat_to_question_banks_type.php` | File Baru | Migrasi untuk mengupdate kolom `type` pada tabel `question_banks` di MySQL |
| 2 | `app/Http/Controllers/Admin/QuestionBankController.php` | Edit | Tambahkan `essai_singkat` pada validasi `store()` dan `update()` |
| 3 | `app/Services/AssessmentService.php` | Edit | Logika auto-grading essay singkat untuk penilaian sesi ujian IRT & Raw |
| 4 | `app/Http/Controllers/ExamController.php` | Edit | Logika penilaian saat siswa submit kategori ujian di sesi ujian |
| 5 | `app/Services/QuizAnswerChecker.php` | Edit | Logika pengecekan jawaban essay singkat pada Modul Quiz harian |
| 6 | `resources/views/admin/questions/_modal_form.blade.php` | Edit | Tambahkan `<option value="essai_singkat">` pada dropdown Tipe Soal |
| 7 | `resources/views/admin/questions/_modal_scripts.blade.php` | Edit | Logika JavaScript UI input kunci jawaban essay (hide opsi, show single text input), edit modal, dan preview modal |
| 8 | `resources/views/admin/questions/index.blade.php` | Edit | Label badge tipe soal `Essai Singkat` pada tabel daftar bank soal |
| 9 | `resources/views/admin/sub_categories/show.blade.php` | Edit | Label badge tipe soal `Essai Singkat` pada tabel sub kategori |
| 10 | `resources/views/admin/quizzes/_modal_form.blade.php` | Edit | Tambahkan opsi `Essai Singkat` pada modal quiz |
| 11 | `resources/views/admin/quizzes/_modal_scripts.blade.php` | Edit | Logika JavaScript input kunci jawaban essay pada modul quiz |
| 12 | `resources/views/admin/quizzes/index.blade.php` | Edit | Label badge tipe soal pada modul quiz |
| 13 | `resources/views/admin/quizzes/import.blade.php` | Edit | Label tipe soal pada modal import bank soal ke quiz |
| 14 | `resources/views/exam/main.blade.php` | Edit | Render input text box untuk soal essay singkat saat ujian berlangsung |
| 15 | `resources/views/participant/quiz.blade.php` | Edit | Render input text box untuk soal essay singkat pada quiz harian |
| 16 | `resources/views/participant/review_category.blade.php` | Edit | Render kartu perbandingan jawaban peserta vs kunci jawaban pada review hasil ujian |
| 17 | `resources/views/admin/sessions/preview.blade.php` | Edit | Tampilan kunci jawaban essay pada halaman preview soal sesi ujian |
| 18 | `resources/views/admin/sessions/preview-only.blade.php` | Edit | Tampilan kunci jawaban essay pada halaman preview-only sesi ujian |
| 19 | `tests/Feature/ShortEssayQuestionTest.php` | File Baru | Automated Feature & Unit Test untuk memastikan seluruh alur berjalan sempurna |

---

## 5. Rincian Langkah Implementasi (Step-by-Step)

### Langkah 1: Buat Database Migration
Buat file migration baru untuk memperbarui ENUM kolom `type` pada tabel `question_banks`:
Jalankan artisan command:
```bash
php artisan make:migration add_essai_singkat_to_question_banks_type
```
Isi migration:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE question_banks MODIFY COLUMN type ENUM('pilihan_ganda', 'benar_salah', 'multiple_choice', 'multiple_benar_salah', 'essai_singkat') NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE question_banks MODIFY COLUMN type ENUM('pilihan_ganda', 'benar_salah', 'multiple_choice', 'multiple_benar_salah') NOT NULL");
        }
    }
};
```
> **Catatan:** Tabel `quizzes` sudah menggunakan tipe data `$table->string('type')` sehingga tidak memerlukan alter table.

---

### Langkah 2: Update Validasi di `QuestionBankController.php`
Buka file `app/Http/Controllers/Admin/QuestionBankController.php`:

Perbarui baris 59 pada method `store()` dan baris 102 pada method `update()`:

**Sebelum:**
```php
'type' => 'required|in:pilihan_ganda,benar_salah,multiple_choice,multiple_benar_salah',
```

**Sesudah:**
```php
'type' => 'required|in:pilihan_ganda,benar_salah,multiple_choice,multiple_benar_salah,essai_singkat',
```

---

### Langkah 3: Update Auto-Grading di `AssessmentService.php` & `ExamController.php`

#### 3.1. File `app/Services/AssessmentService.php`
Di dalam method `calculateScores(ExamSession $session)`:
Cari pengecekan `if ($question->type === 'pilihan_ganda' || $question->type === 'benar_salah')` (sekitar baris 106-172):

Tambahkan blok kondisi untuk `essai_singkat`:
```php
} elseif ($question->type === 'essai_singkat') {
    $normalize = function ($str) {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $str)), 'UTF-8');
    };

    $userNormalized = $normalize($answer);
    $isCorrect = false;

    if ($userNormalized !== '') {
        foreach ($correctArr as $c) {
            if ($userNormalized === $normalize($c)) {
                $isCorrect = true;
                break;
            }
        }
    }

    $score = $isCorrect ? ($question->score_correct ?? 1) : ($question->score_incorrect ?? 0);
}
```

#### 3.2. File `app/Http/Controllers/ExamController.php`
Di dalam method `submitCategory(Request $request, $code, $id)`:
Cari pengecekan tipe soal (sekitar baris 380-432):

Tambahkan blok kondisi untuk `essai_singkat`:
```php
} elseif ($question->type === 'essai_singkat') {
    $normalize = function ($str) {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $str)), 'UTF-8');
    };

    $userNormalized = $normalize($answer);
    $isCorrect = false;

    if ($userNormalized !== '') {
        foreach ($correctArr as $c) {
            if ($userNormalized === $normalize($c)) {
                $isCorrect = true;
                break;
            }
        }
    }

    $score = $isCorrect ? ($question->score_correct ?? 1) : ($question->score_incorrect ?? 0);
}
```

---

### Langkah 4: Update Auto-Grading Quiz di `QuizAnswerChecker.php`
Buka file `app/Services/QuizAnswerChecker.php`:
Tambahkan case `'essai_singkat'` pada method `isCorrect(Quiz $quiz, mixed $answer)`:

```php
case 'essai_singkat':
    if (! is_scalar($answer)) {
        return false;
    }
    $userNormalized = mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $answer)), 'UTF-8');
    if ($userNormalized === '') {
        return false;
    }
    foreach ((array) $quiz->correct_answer as $c) {
        $correctNorm = mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $c)), 'UTF-8');
        if ($userNormalized === $correctNorm) {
            return true;
        }
    }
    return false;
```

---

### Langkah 5: Update Tampilan Admin Bank Soal

#### 5.1. File `resources/views/admin/questions/_modal_form.blade.php`
Tambahkan pilihan option di `<select name="type" id="typeSelect">` (baris ~44-50):
```blade
<select name="type" id="typeSelect" class="form-input" onchange="handleTypeChange()" required>
    <option value="pilihan_ganda">Pilihan Ganda (Single)</option>
    <option value="benar_salah">Benar / Salah</option>
    <option value="multiple_choice">Multiple Choice (Pilih Banyak)</option>
    <option value="multiple_benar_salah">Multiple Benar / Salah</option>
    <option value="essai_singkat">Essai Singkat</option>
</select>
```

#### 5.2. File `resources/views/admin/questions/_modal_scripts.blade.php`
1. **Di method `handleTypeChange()`:**
   Jika tipe adalah `'essai_singkat'`:
   - Sembunyikan tombol `#addOptionBtn` (`display: none`).
   - Ubah teks `#optionsLabelText` menjadi `"Kunci Jawaban Singkat"`.
   - Render input teks sederhana untuk memasukkan kunci jawaban:
   ```javascript
   if (type === 'essai_singkat') {
       document.getElementById('addOptionBtn').style.display = 'none';
       document.getElementById('optionsLabelText').textContent = 'Kunci Jawaban Singkat';
       optionsList.innerHTML = `
           <div class="form-group" style="margin-bottom: 0;">
               <label style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 8px; display: block;">
                   Tuliskan kunci jawaban (jawaban peserta akan dicocokkan otomatis tanpa membedakan huruf besar/kecil & spasi):
               </label>
               <input type="text" name="correct_answer[]" id="essayCorrectAnswerInput" class="form-input" placeholder="Contoh: Jakarta" required style="width: 100%; font-size: 1rem; padding: 10px 14px;">
           </div>
       `;
       return;
   }
   ```
2. **Di form submit handler (`form.onsubmit`):**
   Tambahkan pengecekan jika `typeSelect.value === 'essai_singkat'`:
   - Hapus field `options[]`.
   - Pastikan nilai dari `#essayCorrectAnswerInput` masuk ke `correct_answer[]`.
3. **Di method `editQuestion(id)`:**
   Tambahkan penanganan saat memuat data soal ber-tipe `essai_singkat`:
   ```javascript
   if (q.type === 'essai_singkat') {
       document.getElementById('addOptionBtn').style.display = 'none';
       document.getElementById('optionsLabelText').textContent = 'Kunci Jawaban Singkat';
       const keyVal = (q.correct_answer && q.correct_answer[0]) ? q.correct_answer[0] : '';
       optionsList.innerHTML = `
           <div class="form-group" style="margin-bottom: 0;">
               <label style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 8px; display: block;">
                   Tuliskan kunci jawaban:
               </label>
               <input type="text" name="correct_answer[]" id="essayCorrectAnswerInput" class="form-input" value="${escapeHtml(keyVal)}" required style="width: 100%; font-size: 1rem; padding: 10px 14px;">
           </div>
       `;
   }
   ```
4. **Di method `previewQuestion(id)`:**
   - Update label tipe: `else if (q.type === 'essai_singkat') typeLabel = 'Essai Singkat';`
   - Render tampilan kunci jawaban essay pada `#previewOptions`:
   ```javascript
   if (q.type === 'essai_singkat') {
       const keyVal = (q.correct_answer && q.correct_answer[0]) ? q.correct_answer[0] : '-';
       optionsContainer.innerHTML = `
           <div class="preview-option-item correct" style="border: 1px solid #10b981; padding: 16px; border-radius: 10px; background: rgba(16,185,129,0.06); display: flex; align-items: center; gap: 12px;">
               <div style="background: #10b981; color: white; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.85rem;">
                   <i class="fas fa-key"></i>
               </div>
               <div>
                   <div style="font-size: 0.8rem; color: #10b981; font-weight: 600;">KUNCI JAWABAN:</div>
                   <div style="font-size: 1.05rem; font-weight: 700; color: #0f172a;">${escapeHtml(keyVal)}</div>
               </div>
           </div>
       `;
   }
   ```

#### 5.3. File `resources/views/admin/questions/index.blade.php` & `sub_categories/show.blade.php`
Tambahkan cabang `elseif`:
```blade
@elseif($question->type === 'essai_singkat') Essai Singkat
```

---

### Langkah 6: Update Modul Quiz Admin
Lakukan perubahan analog pada modul Quiz:
- `resources/views/admin/quizzes/_modal_form.blade.php`: Tambahkan `<option value="essai_singkat">Essai Singkat</option>`.
- `resources/views/admin/quizzes/_modal_scripts.blade.php`: Tangani `essai_singkat` pada `handleTypeChange`, submit form, edit modal, dan preview modal.
- `resources/views/admin/quizzes/index.blade.php`: Tambahkan label `Essai Singkat`.
- `resources/views/admin/quizzes/import.blade.php`: Tambahkan label `Essai Singkat` pada modal import.

---

### Langkah 7: Update Tampilan Ujian Siswa (`resources/views/exam/main.blade.php`)

Pada file `resources/views/exam/main.blade.php`:

#### 7.1. Cek Status Soal Terjawab di Grid Navigasi (`renderNav()`)
Cari baris ~535:
```javascript
const isAnswered = q.type === 'multiple_benar_salah'
    ? (answers[q.id] && Object.keys(answers[q.id]).length > 0)
    : (q.type === 'essai_singkat'
        ? (typeof answers[q.id] === 'string' && answers[q.id].trim().length > 0)
        : (answers[q.id] && (!Array.isArray(answers[q.id]) || answers[q.id].length > 0)));
```

#### 7.2. Render Input Text Box untuk Soal Essay di `renderQuestion()`
Cari cabang pembuatan `optionsHtml` (baris ~580-595):
Tambahkan:
```javascript
} else if (q.type === 'essai_singkat') {
    const currentText = typeof selectedVal === 'string' ? selectedVal : '';
    optionsHtml = `
    <div class="essay-container" style="margin-top: 20px;">
        <label style="font-size: 0.95rem; font-weight: 600; color: #0f172a; margin-bottom: 10px; display: block;">
            <i class="fas fa-pencil-alt" style="margin-right: 6px; color: var(--accent);"></i> Tuliskan Jawaban Singkat Anda:
        </label>
        <input type="text" 
            id="essayInput_${q.id}" 
            class="form-input" 
            placeholder="Ketik jawaban Anda di sini..." 
            value="${escapeHtml(currentText)}"
            oninput="handleEssayInput('${q.id}', this.value)"
            autocomplete="off"
            style="width: 100%; max-width: 600px; font-size: 1.05rem; padding: 14px 18px; border: 2px solid #cbd5e1; border-radius: 10px; background: #ffffff; color: #0f172a;">
    </div>`;
}
```

#### 7.3. Buat Handler Input Essay (`handleEssayInput`)
Tambahkan fungsi Javascript di bagian scripts:
```javascript
function handleEssayInput(qId, val) {
    if (val.trim().length > 0) {
        answers[qId] = val;
    } else {
        delete answers[qId];
    }
    renderNav();
    syncAnswerControlState();
    debouncedAutoSave();
}
```

---

### Langkah 8: Update Tampilan Quiz Harian (`resources/views/participant/quiz.blade.php`)

Pada file `resources/views/participant/quiz.blade.php`:

#### 8.1. Di fungsi `optionsHtml(q)`:
Tambahkan:
```javascript
if (q.type === 'essai_singkat') {
    return `
        <div style="margin-top: 16px;">
            <input type="text" id="quizEssayInput" class="form-input" placeholder="Ketik jawaban singkat kamu..." 
                oninput="sel = this.value.trim().length > 0 ? this.value : null; $('btnSubmit').disabled = !ready(QUESTIONS[idx]);"
                style="width: 100%; font-size: 1rem; padding: 12px 16px; border-radius: 10px; border: 2px solid #e2e8f0;">
        </div>
    `;
}
```

#### 8.2. Di fungsi `ready(q)`:
```javascript
if (q.type === 'essai_singkat') return typeof sel === 'string' && sel.trim().length > 0;
```

#### 8.3. Di fungsi `renderPreviewFeedback(q)`:
Tampilkan feedback jawaban siswa vs kunci jawaban untuk essay singkat.

---

### Langkah 9: Update Halaman Review Siswa (`resources/views/participant/review_category.blade.php`)

Pada file `resources/views/participant/review_category.blade.php`:
1. **Di bagian blok kalkulasi skor (baris ~97-120):**
   Tambahkan logika perbandingan untuk `essai_singkat`:
   ```php
   if ($question->type === 'essai_singkat') {
       $normalize = function ($str) {
           return mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $str)), 'UTF-8');
       };
       $userNorm = $normalize($participantAnswer);
       $isCorrect = false;
       if ($userNorm !== '') {
           foreach ((array) $question->correct_answer as $c) {
               if ($userNorm === $normalize($c)) {
                   $isCorrect = true;
                   break;
               }
           }
       }
   }
   ```
2. **Di bagian tampilan opsi (baris ~136):**
   Jika `$question->type === 'essai_singkat'`, tampilkan kartu review khusus:
   ```blade
   @if($question->type === 'essai_singkat')
   <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px;">
       <div style="padding: 16px; border-radius: 12px; border: 1px solid {{ $participantAnswer === null ? '#cbd5e1' : ($isCorrect ? '#10b981' : '#ef4444') }}; background: {{ $participantAnswer === null ? 'rgba(241,245,249,0.5)' : ($isCorrect ? 'rgba(16,185,129,0.08)' : 'rgba(239,68,68,0.08)') }};">
           <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px;">JAWABAN ANDA:</div>
           <div style="font-size: 1.05rem; font-weight: 600; color: #0f172a;">{{ $participantAnswer ?? '(Tidak dijawab)' }}</div>
       </div>

       <div style="padding: 16px; border-radius: 12px; border: 1px solid #10b981; background: rgba(16,185,129,0.08);">
           <div style="font-size: 0.8rem; font-weight: 700; color: #10b981; margin-bottom: 4px;"><i class="fas fa-key"></i> KUNCI JAWABAN:</div>
           <div style="font-size: 1.05rem; font-weight: 700; color: #0f172a;">{{ (array_values((array)$question->correct_answer))[0] ?? '-' }}</div>
       </div>
   </div>
   @elseif($question->type === 'multiple_benar_salah')
   {{-- ... --}}
   ```

---

### Langkah 10: Update Halaman Preview Sesi Admin
Di file `resources/views/admin/sessions/preview.blade.php` dan `resources/views/admin/sessions/preview-only.blade.php`:
Tambahkan tampilan kunci jawaban essay:
```blade
@elseif($question->type === 'essai_singkat')
    <div style="padding: 16px; border-radius: 10px; background: rgba(16, 185, 129, 0.08); border: 1px solid #10b981; display: flex; align-items: center; gap: 12px;">
        <span style="font-weight: 700; color: #10b981;"><i class="fas fa-key"></i> Kunci Jawaban:</span>
        <span style="color: #0f172a; font-weight: 600;">{{ ((array) $question->correct_answer)[0] ?? '-' }}</span>
    </div>
```

---

## 6. Rencana Pengujian Otomatis (Automated Testing)

Buat file test baru di `tests/Feature/ShortEssayQuestionTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ExamSession;
use App\Models\ExamSessionCategory;
use App\Models\ExamSessionParticipant;
use App\Models\QuestionBank;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizSetting;
use App\Models\SubCategory;
use App\Models\User;
use App\Models\UserAnswer;
use App\Services\AssessmentService;
use App\Services\QuizAnswerChecker;
use App\Services\QuizService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShortEssayQuestionTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected Category $category;
    protected SubCategory $subCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['role' => 'superadmin']);
        $this->category = Category::create(['name' => 'Bahasa Indonesia', 'slug' => 'bahasa-indonesia']);
        $this->subCategory = SubCategory::create([
            'category_id' => $this->category->id,
            'name' => 'Tata Bahasa',
            'slug' => 'tata-bahasa',
        ]);
    }

    /** 1. Test Superadmin dapat membuat soal essai singkat via API / Controller */
    public function test_superadmin_can_create_short_essay_question(): void
    {
        $response = $this->actingAs($this->superAdmin)->postJson(route('admin.questions.store'), [
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'kode_soal' => 'ESSAY-01',
            'type' => 'essai_singkat',
            'question_text' => 'Ibu kota negara Indonesia adalah...',
            'options' => [],
            'correct_answer' => ['Jakarta'],
            'score_correct' => 1,
            'score_incorrect' => 0,
            'explanation' => 'Ibu kota Indonesia adalah Jakarta.',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('question_banks', [
            'kode_soal' => 'ESSAY-01',
            'type' => 'essai_singkat',
        ]);
    }

    /** 2. Test QuizAnswerChecker: Jawaban persis, beda case, beda spasi, dan salah */
    public function test_quiz_answer_checker_evaluates_short_essay_accurately(): void
    {
        $checker = app(QuizAnswerChecker::class);

        $quiz = new Quiz([
            'type' => 'essai_singkat',
            'correct_answer' => ['Bhinneka Tunggal Ika'],
        ]);

        // A. Persis sama -> Benar
        $this->assertTrue($checker->isCorrect($quiz, 'Bhinneka Tunggal Ika'));

        // B. Huruf kecil semua -> Benar
        $this->assertTrue($checker->isCorrect($quiz, 'bhinneka tunggal ika'));

        // C. Huruf kapital semua -> Benar
        $this->assertTrue($checker->isCorrect($quiz, 'BHINNEKA TUNGGAL IKA'));

        // D. Spasi di awal & akhir -> Benar
        $this->assertTrue($checker->isCorrect($quiz, '   Bhinneka Tunggal Ika   '));

        // E. Spasi ganda di tengah kalimat -> Benar
        $this->assertTrue($checker->isCorrect($quiz, 'bhinneka    tunggal   ika'));

        // F. Jawaban typo / salah -> Salah
        $this->assertFalse($checker->isCorrect($quiz, 'Bhinneka Ika'));

        // G. Jawaban kosong / spasi saja -> Salah
        $this->assertFalse($checker->isCorrect($quiz, ''));
        $this->assertFalse($checker->isCorrect($quiz, '   '));
    }

    /** 3. Test AssessmentService penilaian sesi ujian untuk soal essai singkat */
    public function test_assessment_service_scores_short_essay_correctly(): void
    {
        $question = QuestionBank::create([
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'kode_soal' => 'ESSAY-02',
            'type' => 'essai_singkat',
            'question_text' => 'Lambang negara Indonesia adalah burung...',
            'options' => [],
            'correct_answer' => ['Garuda'],
            'score_correct' => 5,
            'score_incorrect' => 0,
        ]);

        $session = ExamSession::create([
            'name' => 'Sesi Ujian Essay',
            'code' => 'ESSAY001',
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-10',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'is_active' => true,
        ]);

        $participantBenar = ExamSessionParticipant::create([
            'exam_session_id' => $session->id,
            'user_id' => User::factory()->create()->id,
            'name' => 'Siswa Benar',
            'whatsapp' => '08123456789',
            'access_code' => 'BENAR01',
        ]);

        $participantSalah = ExamSessionParticipant::create([
            'exam_session_id' => $session->id,
            'user_id' => User::factory()->create()->id,
            'name' => 'Siswa Salah',
            'whatsapp' => '08123456788',
            'access_code' => 'SALAH01',
        ]);

        // Siswa Benar menjawab dengan spasi berlebih dan lowercase: "  garuda  "
        UserAnswer::create([
            'exam_session_id' => $session->id,
            'participant_id' => $participantBenar->id,
            'question_bank_id' => $question->id,
            'answer' => '  garuda  ',
        ]);

        // Siswa Salah menjawab "Elang"
        UserAnswer::create([
            'exam_session_id' => $session->id,
            'participant_id' => $participantSalah->id,
            'question_bank_id' => $question->id,
            'answer' => 'Elang',
        ]);

        $assessmentService = app(AssessmentService::class);
        $assessmentService->calculateScores($session);

        $answerBenar = UserAnswer::where('participant_id', $participantBenar->id)->first();
        $this->assertTrue((bool) $answerBenar->is_correct);
        $this->assertEquals(5, $answerBenar->score);

        $answerSalah = UserAnswer::where('participant_id', $participantSalah->id)->first();
        $this->assertFalse((bool) $answerSalah->is_correct);
        $this->assertEquals(0, $answerSalah->score);
    }
}
```

---

## 7. Checklist Verifikasi Manual (QA Acceptance Criteria)

Lakukan pengujian manual berikut:

1. [ ] **Admin: Input Soal Essay Singkat Baru:**
   - Masuk ke Admin $\rightarrow$ **Bank Soal** $\rightarrow$ Klik **Tambah Soal**.
   - Pilih Tipe Soal: **Essai Singkat**.
   - Pastikan opsi A, B, C, D dan tombol "Tambah Opsi" disembunyikan.
   - Masukkan kunci jawaban: `"Pancasila"`.
   - Simpan soal dan pastikan tersimpan tanpa error validasi.
2. [ ] **Admin: Preview & Edit Soal:**
   - Klik ikon **Preview** pada soal yang baru dibuat. Pastikan badge bertuliskan **Essai Singkat** dan kunci jawaban tampil dengan rapi.
   - Klik **Edit Soal**. Pastikan input kunci jawaban terisi otomatis dengan nilai `"Pancasila"`, lalu simpan kembali.
3. [ ] **Siswa: Mengerjakan Ujian dengan Soal Essay:**
   - Mulai sesi ujian yang memuat soal essay singkat tersebut.
   - Pastikan muncul **kotak input isian teks** (bukan tombol pilihan ganda).
   - Ketik jawaban dengan huruf kecil dan spasi berlebih: `"  pancasila  "`.
   - Selesaikan ujian.
4. [ ] **Verifikasi Nilai & Review:**
   - Buka halaman review nilai siswa.
   - Pastikan jawaban dinilai **BENAR** (centang hijau) dengan skor penuh.
   - Uji juga dengan jawaban yang salah (misal: `"UUD 1945"`), pastikan dinilai **SALAH** (silang merah) dengan skor 0.
5. [ ] **Automated Test:**
   - Jalankan `php artisan test --filter=ShortEssayQuestionTest`.
   - Seluruh test harus berwarna hijau (**PASS**).
