# Issue: Fitur Bulk Generate Raport

## Deskripsi

Tambahkan fitur **Bulk Raport** di halaman **Manajemen Peserta** (`/admin/participants`).  
Saat ini, admin hanya bisa mencetak raport satu per satu per peserta. Fitur ini memungkinkan admin memilih **beberapa peserta sekaligus**, lalu memilih **sesi ujian**, kemudian men-generate raport untuk semua peserta yang dipilih secara massal.

---

## Alur Fitur (User Flow)

```
1. Admin masuk ke halaman /admin/participants
2. Admin klik tombol "Bulk Raport" (di samping tombol "Tambah Peserta")
3. Muncul modal STEP 1: Pilih peserta (checklist dari daftar peserta)
4. Admin centang peserta-peserta yang diinginkan, lalu klik "Lanjut"
5. Muncul modal STEP 2: Pilih sesi ujian (checklist dari sesi yang tersedia)
   - Sesi yang ditampilkan adalah sesi yang SEMUA peserta terpilih pernah ikuti
6. Admin centang sesi ujian yang diinginkan, lalu klik "Generate Raport"
7. Muncul state PROCESSING: Loading bar/spinner per peserta
8. Backend men-dispatch Job generate raport per peserta
9. Frontend polling status semua raport
10. Setelah selesai semua, muncul state DONE: Daftar raport yang berhasil, 
    dengan tombol "Lihat" per peserta
```

---

## Referensi Kode yang Sudah Ada

Sebelum mulai, **baca dan pahami** file-file ini karena fitur baru akan mengikuti pola yang sama:

| Fungsi | File | Keterangan |
|--------|------|------------|
| **Halaman Peserta (View)** | `resources/views/admin/participants/index.blade.php` | Template Blade utama. Lihat bagaimana modal cetak raport per-user bekerja (line 203-250) |
| **Controller** | `app/Http/Controllers/Admin/ParticipantController.php` | Lihat method `generateReport()` (line 166-193) sebagai contoh yang akan di-clone |
| **Routes** | `routes/web.php` (line 70-81) | Semua route terkait raport ada di sini |
| **Model** | `app/Models/ReportCard.php` | Model raport, perhatikan `fillable` dan `casts` |
| **Job** | `app/Jobs/GenerateReportCardJob.php` | Job yang memproses raport di background queue |
| **Migration** | `database/migrations/2026_08_08_120000_create_report_cards_table.php` | Struktur tabel `report_cards` |

---

## Tahapan Implementasi

### Tahap 1: Tambah Tombol "Bulk Raport" di View

**File:** `resources/views/admin/participants/index.blade.php`

**Apa yang dilakukan:**  
Tambahkan tombol baru di sebelah tombol "Tambah Peserta".

**Lokasi tepat:** Cari baris ini (sekitar line 25-27):
```html
<button class="btn-primary" onclick="openParticipantModal('create')" style="flex-shrink: 0;">
    <i class="fas fa-plus"></i> Tambah Peserta
</button>
```

**Tambahkan SEBELUM tombol di atas:**
```html
<button class="btn-primary" onclick="openBulkReportModal()" style="flex-shrink: 0; background: #10b981;">
    <i class="fas fa-file-alt"></i> Bulk Raport
</button>
```

---

### Tahap 2: Tambah Modal Bulk Raport di View

**File:** `resources/views/admin/participants/index.blade.php`

**Apa yang dilakukan:**  
Tambahkan HTML modal baru SETELAH modal `reportHistoryModal` (setelah line 287). Modal ini punya 4 state:

1. **Step 1 - Pilih Peserta**: Tampilkan daftar peserta dengan checkbox
2. **Step 2 - Pilih Sesi Ujian**: Tampilkan daftar sesi ujian dengan checkbox
3. **Processing**: Loading spinner dengan progress per peserta
4. **Done**: Daftar hasil raport yang berhasil

**HTML modal yang harus ditambahkan:**

```html
<!-- Modal Bulk Raport -->
<div class="modal-overlay" id="bulkReportModal">
    <div class="modal-content glass animate-fade-in" style="max-width: 700px;">
        <div class="modal-header">
            <h3 id="bulkReportModalTitle">Bulk Generate Raport</h3>
            <button class="close-modal" onclick="closeBulkReportModal()">&times;</button>
        </div>

        <!-- STEP 1: Pilih Peserta -->
        <div id="bulkStep1">
            <p style="color: var(--text-secondary); margin-bottom: 12px; font-size: 0.9rem;">
                Centang peserta yang ingin dicetak raportnya:
            </p>
            
            <!-- Search box untuk filter peserta -->
            <div style="position: relative; margin-bottom: 16px;">
                <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                <input type="text" id="bulkSearchParticipant" class="form-input" placeholder="Cari nama peserta..." style="padding-left: 36px; margin-bottom: 0;" oninput="filterBulkParticipants()">
            </div>
            
            <!-- Tombol Select All -->
            <div style="margin-bottom: 12px;">
                <label style="cursor: pointer; font-size: 0.85rem; color: var(--text-secondary); display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" id="bulkSelectAll" onchange="toggleSelectAllParticipants()" style="width: 16px; height: 16px;">
                    Pilih Semua
                </label>
            </div>
            
            <div id="bulkParticipantList" style="max-height: 300px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px;">
                <!-- Diisi via JavaScript -->
            </div>
            
            <div style="display: flex; gap: 12px; margin-top: 24px; justify-content: space-between; align-items: center;">
                <span id="bulkSelectedCount" style="font-size: 0.85rem; color: var(--text-secondary);">0 peserta dipilih</span>
                <div style="display: flex; gap: 12px;">
                    <button type="button" class="btn-primary" style="background: transparent; border: 1px solid var(--glass-border); color: var(--text-secondary);" onclick="closeBulkReportModal()">Batal</button>
                    <button type="button" class="btn-primary" onclick="bulkGoToStep2()" style="background: #3b82f6;">
                        Lanjut <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- STEP 2: Pilih Sesi Ujian -->
        <div id="bulkStep2" style="display: none;">
            <p style="color: var(--text-secondary); margin-bottom: 16px; font-size: 0.9rem;">
                Pilih sesi ujian yang ingin dimasukkan ke raport:
            </p>
            
            <!-- Loading saat fetch sesi -->
            <div id="bulkSessionLoading" style="text-align: center; padding: 40px;">
                <div style="width: 40px; height: 40px; border: 3px solid rgba(59, 130, 246, 0.3); border-top-color: #3b82f6; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 16px;"></div>
                <p style="color: var(--text-secondary);">Memuat daftar sesi ujian...</p>
            </div>
            
            <div id="bulkSessionCheckboxes" style="max-height: 300px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px;">
                <!-- Diisi via JavaScript -->
            </div>
            
            <div id="bulkStep2Actions" style="display: none;">
                <div style="display: flex; gap: 12px; margin-top: 24px; justify-content: space-between;">
                    <button type="button" class="btn-primary" style="background: transparent; border: 1px solid var(--glass-border); color: var(--text-secondary);" onclick="bulkBackToStep1()">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </button>
                    <button type="button" class="btn-primary" onclick="bulkSubmitGenerate()" style="background: #10b981;">
                        <i class="fas fa-file-alt"></i> Generate Raport
                    </button>
                </div>
            </div>
        </div>

        <!-- STEP 3: Processing -->
        <div id="bulkStep3" style="display: none;">
            <p style="color: var(--text-secondary); margin-bottom: 16px; font-size: 0.9rem;">
                Raport sedang digenerate, mohon tunggu...
            </p>
            <div id="bulkProgressList" style="display: flex; flex-direction: column; gap: 8px; max-height: 300px; overflow-y: auto;">
                <!-- Progress per peserta diisi via JavaScript -->
            </div>
        </div>

        <!-- STEP 4: Done -->
        <div id="bulkStep4" style="display: none;">
            <div style="text-align: center; margin-bottom: 24px;">
                <div style="width: 60px; height: 60px; background: rgba(16, 185, 129, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                    <i class="fas fa-check" style="font-size: 1.5rem; color: #10b981;"></i>
                </div>
                <h4>Bulk Raport Selesai!</h4>
            </div>
            <div id="bulkResultList" style="display: flex; flex-direction: column; gap: 8px; max-height: 300px; overflow-y: auto;">
                <!-- Daftar hasil raport diisi via JavaScript -->
            </div>
            <div style="display: flex; justify-content: flex-end; margin-top: 24px;">
                <button type="button" class="btn-primary" onclick="closeBulkReportModal()">Tutup</button>
            </div>
        </div>
    </div>
</div>
```

---

### Tahap 3: Tambah Route Baru di Backend

**File:** `routes/web.php`

**Apa yang dilakukan:**  
Tambahkan 2 route baru di dalam group `SuperAdminMiddleware`. 

**PENTING:** Taruh route ini **SEBELUM** baris `Route::resource('/admin/participants', ...)` (line 71) agar tidak ditangkap oleh resource route sebagai parameter `{participant}`.

```php
// Bulk Raport
Route::get('/admin/participants/bulk-report/sessions', [\App\Http\Controllers\Admin\ParticipantController::class, 'getBulkReportSessions'])->name('admin.participants.bulk-report-sessions');
Route::post('/admin/participants/bulk-report/generate', [\App\Http\Controllers\Admin\ParticipantController::class, 'bulkGenerateReport'])->name('admin.participants.bulk-generate-report');
```

---

### Tahap 4: Tambah Method di Controller

**File:** `app/Http/Controllers/Admin/ParticipantController.php`

**Apa yang dilakukan:**  
Tambahkan 2 method baru di dalam class `ParticipantController`:

#### Method 1: `getBulkReportSessions`

Method ini menerima daftar `user_ids[]` via query string, lalu mengembalikan sesi ujian yang **semua** user tersebut pernah ikuti (irisan/intersection).

```php
/**
 * Ambil daftar sesi ujian yang SEMUA user terpilih pernah ikuti.
 * Dipanggil via GET dengan query: ?user_ids[]=1&user_ids[]=2&user_ids[]=3
 */
public function getBulkReportSessions(Request $request)
{
    $validator = Validator::make($request->all(), [
        'user_ids'   => 'required|array|min:1',
        'user_ids.*' => 'integer|exists:users,id',
    ]);

    if ($validator->fails()) {
        return $this->validationResponse($validator->errors());
    }

    $userIds = $request->user_ids;

    // Untuk setiap user, ambil daftar exam_session_id yang pernah dikerjakan
    $sessionSets = [];
    foreach ($userIds as $userId) {
        $sessionIds = ExamSessionParticipant::where('user_id', $userId)
            ->whereNotNull('finished_at')
            ->pluck('exam_session_id')
            ->unique()
            ->toArray();
        $sessionSets[] = $sessionIds;
    }

    // Cari irisan (intersection) dari semua set
    // Ini agar hanya sesi yang SEMUA peserta pernah ikuti yang tampil
    $commonSessionIds = array_values(array_intersect(...$sessionSets));

    // Ambil data nama sesi
    $sessions = \App\Models\ExamSession::whereIn('id', $commonSessionIds)
        ->get()
        ->map(function ($session) {
            return [
                'id'   => $session->id,
                'name' => $session->name,
            ];
        });

    return $this->successResponse([
        'sessions' => $sessions,
    ]);
}
```

#### Method 2: `bulkGenerateReport`

Method ini menerima daftar `user_ids` dan `session_ids`, lalu membuat `ReportCard` dan dispatch `GenerateReportCardJob` untuk SETIAP user.

```php
/**
 * Bulk generate raport untuk beberapa peserta sekaligus.
 * Menerima: { user_ids: [1,2,3], session_ids: [5,6] }
 * Mengembalikan: array of report_card_id yang baru dibuat
 */
public function bulkGenerateReport(Request $request)
{
    $validator = Validator::make($request->all(), [
        'user_ids'      => 'required|array|min:1',
        'user_ids.*'    => 'integer|exists:users,id',
        'session_ids'   => 'required|array|min:1',
        'session_ids.*' => 'integer|exists:exam_sessions,id',
    ]);

    if ($validator->fails()) {
        return $this->validationResponse($validator->errors());
    }

    $reportCards = [];

    foreach ($request->user_ids as $userId) {
        $reportCard = ReportCard::create([
            'user_id'      => $userId,
            'generated_by' => auth()->id(),
            'session_ids'  => $request->session_ids,
            'status'       => 'processing',
        ]);

        GenerateReportCardJob::dispatch($reportCard->id);

        $user = \App\Models\User::find($userId);
        $reportCards[] = [
            'report_card_id' => $reportCard->id,
            'user_id'        => $userId,
            'user_name'      => $user->name,
        ];
    }

    return $this->successResponse([
        'report_cards' => $reportCards,
    ], 'Bulk raport sedang diproses.');
}
```

> **Catatan:** Jangan lupa tambahkan `use App\Models\ExamSession;` di bagian atas controller jika belum ada.

---

### Tahap 5: Tambah JavaScript di View

**File:** `resources/views/admin/participants/index.blade.php`

**Apa yang dilakukan:**  
Tambahkan blok JavaScript baru di dalam `@push('scripts')`, SEBELUM tag penutup `</script>`. Kode ini mengelola semua interaksi modal Bulk Raport.

**Berikut pseudocode yang harus diimplementasikan sebagai JavaScript:**

```
VARIABLE: selectedUserIds = []  // Array user_id yang dipilih
VARIABLE: bulkReportCardIds = []  // Array report_card_id yang sedang diproses

FUNCTION openBulkReportModal():
    1. Reset semua state (semua step hidden kecuali step 1)
    2. Tampilkan modal (add class 'active')
    3. Ambil SEMUA peserta dari tabel HTML yang sudah ada di halaman
       - Loop setiap <tr> di tbody tabel peserta
       - Untuk setiap baris, ambil:
         - user_id dari onclick attribute tombol edit: editParticipant(ID)
         - Nama dari kolom pertama
       - Buat checkbox item untuk setiap peserta
    4. Tampilkan di container #bulkParticipantList
    
    ALTERNATIF (lebih baik):
    - Buat API endpoint baru GET /admin/participants/all-basic 
      yang mengembalikan semua peserta (tanpa pagination)
    - Atau gunakan data dari halaman saat ini saja (sesuai pagination)
    
    CATATAN: Karena halaman participants sudah pakai pagination,
    sebaiknya buat endpoint baru yang mengembalikan SEMUA user role 'basic'
    agar admin bisa pilih dari semua peserta, bukan hanya yang di halaman ini.

FUNCTION closeBulkReportModal():
    1. Sembunyikan modal (remove class 'active')

FUNCTION filterBulkParticipants():
    1. Ambil nilai input search
    2. Filter daftar peserta yang tampil berdasarkan nama

FUNCTION toggleSelectAllParticipants():
    1. Jika checkbox "Select All" dicentang, centang semua
    2. Jika di-uncheck, uncheck semua
    3. Update counter "X peserta dipilih"

FUNCTION updateSelectedCount():
    1. Hitung jumlah checkbox yang dicentang
    2. Update text #bulkSelectedCount

FUNCTION bulkGoToStep2():
    1. Kumpulkan semua user_id yang dicentang
    2. Jika kosong, tampilkan showToast('Pilih minimal 1 peserta', 'error')
    3. Simpan ke selectedUserIds
    4. Sembunyikan Step 1, tampilkan Step 2
    5. Tampilkan loading
    6. Fetch GET /admin/participants/bulk-report/sessions?user_ids[]=1&user_ids[]=2...
    7. Parse response, tampilkan daftar sesi sebagai checkbox
    8. Jika sesi kosong, tampilkan pesan "Tidak ada sesi ujian yang dikerjakan semua peserta"
    9. Sembunyikan loading, tampilkan daftar sesi dan tombol action

FUNCTION bulkBackToStep1():
    1. Sembunyikan Step 2, tampilkan Step 1

FUNCTION bulkSubmitGenerate():
    1. Kumpulkan semua session_id yang dicentang
    2. Jika kosong, tampilkan showToast('Pilih minimal 1 sesi', 'error')
    3. Sembunyikan Step 2, tampilkan Step 3
    4. Buat progress item per peserta (nama + spinner)
    5. Fetch POST /admin/participants/bulk-report/generate
       Body: { user_ids: selectedUserIds, session_ids: [...] }
    6. Dari response, ambil array report_cards
    7. Simpan ke bulkReportCardIds
    8. Mulai polling status untuk semua report card

FUNCTION pollBulkReportStatus():
    1. Setiap 2 detik, loop semua report_card_id
    2. Untuk setiap ID, fetch GET /admin/report-cards/{id}/status
       (GUNAKAN ENDPOINT YANG SUDAH ADA, tidak perlu buat baru!)
    3. Jika status = 'completed':
       - Update UI progress item peserta tsb menjadi centang hijau
    4. Jika status = 'failed':
       - Update UI progress item peserta tsb menjadi silang merah
    5. Jika SEMUA sudah selesai (completed/failed):
       - Stop polling
       - Sembunyikan Step 3, tampilkan Step 4
       - Tampilkan daftar hasil dengan link "Lihat Raport"
```

**Contoh implementasi JavaScript (bisa langsung dicopy):**

Implementasi akan mengikuti pola yang sudah ada di `openReportModal()`, `submitGenerateReport()`, dan `pollReportStatus()` di file yang sama. Bedanya:
- Alih-alih 1 user, kita memproses BANYAK user
- Polling dilakukan untuk BANYAK report card sekaligus
- Ada 4 step alih-alih 4 state

---

### Tahap 6 (Opsional tapi Direkomendasikan): Endpoint List All Participants

**File:** `app/Http/Controllers/Admin/ParticipantController.php`  
**File:** `routes/web.php`

Karena halaman peserta sudah pakai **pagination** (hanya tampil ~15 peserta per halaman), modal bulk raport perlu bisa mengambil SEMUA peserta. Ada 2 opsi:

**Opsi A (Lebih Simpel):** Ambil data dari tabel HTML di halaman saat ini saja.  
- Pro: Tidak perlu endpoint baru  
- Kontra: Admin hanya bisa pilih peserta yang tampil di halaman saat ini  

**Opsi B (Direkomendasikan):** Buat endpoint API baru.

Route (taruh SEBELUM resource route):
```php
Route::get('/admin/participants/all-basic', [\App\Http\Controllers\Admin\ParticipantController::class, 'allBasicParticipants'])->name('admin.participants.all-basic');
```

Method:
```php
public function allBasicParticipants()
{
    $users = \App\Models\User::where('role', 'basic')
        ->orderBy('name')
        ->get(['id', 'name', 'email']);

    return $this->successResponse([
        'participants' => $users,
    ]);
}
```

---

## Checklist Sebelum Merge

- [ ] Tombol "Bulk Raport" muncul di samping "Tambah Peserta" 
- [ ] Modal Step 1 menampilkan daftar peserta dengan checkbox
- [ ] Fitur search/filter di Step 1 berfungsi
- [ ] Tombol "Pilih Semua" berfungsi
- [ ] Step 2 menampilkan sesi ujian yang valid (irisan dari semua peserta terpilih)
- [ ] Klik "Generate Raport" membuat report card per peserta
- [ ] Progress per peserta ditampilkan di Step 3
- [ ] Polling status berjalan dan UI terupdate per peserta
- [ ] Step 4 menampilkan daftar raport yang sukses dengan link "Lihat"
- [ ] Error handling: jika ada raport gagal, tetap tampilkan yang sukses
- [ ] Raport yang digenerate bisa dilihat di History Raport masing-masing peserta

---

## File yang Akan Diubah/Ditambah

| File | Aksi | Deskripsi |
|------|------|-----------|
| `resources/views/admin/participants/index.blade.php` | MODIFY | Tambah tombol, modal HTML, dan JavaScript |
| `app/Http/Controllers/Admin/ParticipantController.php` | MODIFY | Tambah 2-3 method baru |
| `routes/web.php` | MODIFY | Tambah 2-3 route baru |

**TIDAK perlu** membuat migration baru karena tabel `report_cards` yang sudah ada mendukung fitur ini sepenuhnya (1 record per user per generate).

---

## Tips untuk Implementor

1. **Mulai dari backend dulu** (route + controller method), test pakai Postman/curl
2. **Lalu buat UI** (tombol + modal HTML) 
3. **Terakhir JavaScript** (interaksi modal)
4. Selalu lihat **pola kode yang sudah ada** di file yang sama — jangan buat pola baru
5. Gunakan `showToast()` untuk notifikasi (sudah ada di layout admin)
6. Gunakan `customConfirm()` untuk konfirmasi (sudah ada di layout admin)
7. Semua fetch request harus kirim header `'X-CSRF-TOKEN'` dan `'Accept': 'application/json'`
8. Endpoint polling status (`/admin/report-cards/{id}/status`) sudah ada, **jangan buat baru**
