# Rencana Implementasi: Role Baru `admin` (Viewer / Pengawas Sesi Read-Only)

Dokumen ini disusun sebagai panduan spesifikasi dan teknis implementasi langkah-demi-langkah bagi **Junior Programmer** atau **Model AI (Cost-effective / Budget AI)** untuk menambahkan role baru bernama `admin` pada sistem JagoBelajar Tryout IRT.

---

## 1. Ringkasan & Definisi Kebutuhan

### 1.1. Latar Belakang
Saat ini sistem memiliki 2 level admin:
1. `superadmin`: Memiliki akses penuh ke seluruh sistem (Bank Soal, Modul Quiz, Kategori, Quiz Settings, Manajemen User/Peserta, Cetak Raport, dan Sesi Ujian).
2. `admin_sesi`: Hanya memiliki akses ke modul Sesi Ujian yang ditugaskan kepadanya. Pada sesi tersebut, `admin_sesi` dapat melihat nilai, melihat soal, serta **menambahkan / mendaftarkan peserta** ke sesi ujian.

### 1.2. Kebutuhan Role Baru: `admin`
Dibutuhkan satu role baru dengan kode role: **`admin`**.
Karakteristik role `admin`:
- **Hampir sama dengan `admin_sesi`**:
  - Tampilan dashboard dan layout minimalis (sidebar admin master soal/peserta disembunyikan, langsung diarahkan ke daftar Sesi Ujian).
  - Hanya dapat melihat sesi ujian yang **ditugaskan / di-assign** kepadanya (melalui relasi `exam_session_participants`).
  - Dapat membuka halaman detail sesi (`/admin/sessions/{id}`), melihat daftar peserta terdaftar, melihat nilai/hasil ujian peserta, preview soal, serta export hasil ujian.
- **Perbedaan Utama (Read-Only pada Peserta):**
  - Role `admin` **HANYA BISA LIHAT-LIHAT SAJA** pada sesi ujian.
  - Role `admin` **TIDAK BISA** menambahkan siswa/peserta ke dalam sesi ujian (baik memilih user yang sudah ada maupun membuat user baru).
  - Role `admin` **TIDAK BISA** menghapus siswa dari sesi ujian.
  - Role `admin` **TIDAK BISA** mengubah privilege (General / Premium) peserta di sesi ujian.
- **Batasan Sistem Lainnya**:
  - Tidak memiliki akses ke menu Superadmin (`/admin/categories`, `/admin/questions`, `/admin/quizzes`, `/admin/participants`, dll.).
  - Tidak boleh muncul atau masuk ke dalam perhitungan Leaderboard Mingguan Peserta (`QuizService`).

---

## 2. Matriks Perbandingan Hak Akses Role Admin

| Fitur / Tindakan | `superadmin` | `admin_sesi` | `admin` (Role Baru) |
| :--- | :---: | :---: | :---: |
| Akses Menu Master (Soal, Kategori, Quiz, Raport, User) | ✅ Ya | ❌ Tidak (403) | ❌ Tidak (403) |
| Melihat Semua Sesi Ujian | ✅ Semua | ❌ Hanya yang ditugaskan | ❌ Hanya yang ditugaskan |
| Buat / Edit / Hapus Sesi Ujian | ✅ Ya | ❌ Tidak | ❌ Tidak |
| Melihat Detail Sesi, Soal, & Hasil Nilai Ujian | ✅ Ya | ✅ Ya | ✅ Ya |
| Export Hasil Ujian (CSV) | ✅ Ya | ✅ Ya | ✅ Ya |
| **Menambah Peserta ke Sesi Ujian (User Lama)** | ✅ Ya | ✅ Ya | ❌ **DILARANG (403 & Tombol Hidden)** |
| **Membuat Peserta Baru ke Sesi Ujian** | ✅ Ya | ✅ Ya | ❌ **DILARANG (403 & Tombol Hidden)** |
| **Hapus Peserta dari Sesi Ujian** | ✅ Ya | ✅ Ya | ❌ **DILARANG (403 & Tombol Hidden)** |
| **Ubah Privilege Peserta (General / Premium)** | ✅ Ya | ✅ Ya | ❌ **DILARANG (403 & Input Disabled)** |
| Muncul di Leaderboard Peserta | ❌ Tidak | ❌ Tidak | ❌ Tidak |

---

## 3. Daftar File yang Terlibat

| No | File | Jenis Perubahan | Deskripsi |
|---|---|---|---|
| 1 | `app/Models/User.php` | Edit | Menambahkan `'admin'` ke method helper `isAdmin()` |
| 2 | `app/Http/Controllers/AuthController.php` | Edit | Redirect user setelah login jika role adalah `'admin'` |
| 3 | `routes/web.php` | Edit | Redirect pada route root `/` jika user yang login ber-role `'admin'` |
| 4 | `app/Http/Controllers/Admin/ExamSessionController.php` | Edit | Filter sesi yang ditugaskan untuk `'admin'` di `index()` & tambahkan `'admin'` ke daftar user yang dapat di-assign di `show()` |
| 5 | `app/Http/Controllers/Admin/ExamSessionParticipantController.php` | Edit | Proteksi backend 403 Forbidden untuk role `'admin'` pada endpoint penambahan/penghapusan peserta |
| 6 | `resources/views/layouts/admin.blade.php` | Edit | Menyesuaikan tampilan layout (hide sidebar) untuk role `'admin'` sama seperti `admin_sesi` |
| 7 | `resources/views/admin/sessions/show.blade.php` | Edit | Sembunyikan tombol tambah peserta, modal tambah peserta, tombol hapus, dan disable dropdown privilege jika role adalah `'admin'` |
| 8 | `app/Http/Controllers/Admin/ParticipantController.php` | Edit | Menambahkan role `'admin'` pada query index dan validasi form manajemen user superadmin |
| 9 | `resources/views/admin/participants/index.blade.php` | Edit | Menambahkan opsi role `Admin` pada dropdown modal & styling badge role |
| 10 | `app/Services/QuizService.php` | Edit | Memastikan role `'admin'` di-exclude dari query leaderboard mingguan |
| 11 | `tests/Feature/AdminRolePermissionTest.php` | Baru | Unit/Feature test untuk memastikan seluruh hak akses role `admin` bekerja sesuai spesifikasi |

---

## 4. Rincian Langkah Implementasi (Step-by-Step)

### Langkah 1: Update Model `app/Models/User.php`
Buka file `app/Models/User.php`. Cari method `isAdmin()`:

**Sebelum:**
```php
public function isAdmin()
{
    return in_array($this->role, ['superadmin', 'admin_sesi']);
}
```

**Sesudah:**
```php
public function isAdmin()
{
    return in_array($this->role, ['superadmin', 'admin_sesi', 'admin']);
}
```
> **Penjelasan:** Method ini digunakan oleh `AdminMiddleware` untuk mengizinkan user masuk ke prefix `/admin`. Dengan memasukkan `'admin'`, user dengan role baru ini diakui sebagai admin level sistem.

---

### Langkah 2: Update Autentikasi & Routing Redirect
User dengan role `admin` harus langsung diarahkan ke halaman daftar sesi ujian (`admin.sessions.index`) ketika berhasil login.

#### 2.1. File `app/Http/Controllers/AuthController.php`
Cari method `login()` sekitar baris 56-66:

**Sebelum:**
```php
if ($user->role === 'superadmin') {
    return redirect()->intended(route('admin.dashboard'));
}

if ($user->role === 'admin_sesi') {
    return redirect()->intended(route('admin.sessions.index'));
}

if ($user->role === 'basic') {
    return redirect()->intended(route('participant.dashboard'));
}
```

**Sesudah:**
```php
if ($user->role === 'superadmin') {
    return redirect()->intended(route('admin.dashboard'));
}

if (in_array($user->role, ['admin_sesi', 'admin'])) {
    return redirect()->intended(route('admin.sessions.index'));
}

if ($user->role === 'basic') {
    return redirect()->intended(route('participant.dashboard'));
}
```

#### 2.2. File `routes/web.php`
Cari route root `/` pada baris 5-13:

**Sebelum:**
```php
Route::get('/', function() {
    if (auth()->check()) {
        $role = auth()->user()->role;
        if ($role === 'superadmin') return redirect()->route('admin.dashboard');
        if ($role === 'admin_sesi') return redirect()->route('admin.sessions.index');
        return redirect()->route('participant.dashboard');
    }
    return view('participant.auth.login');
})->name('login');
```

**Sesudah:**
```php
Route::get('/', function() {
    if (auth()->check()) {
        $role = auth()->user()->role;
        if ($role === 'superadmin') return redirect()->route('admin.dashboard');
        if (in_array($role, ['admin_sesi', 'admin'])) return redirect()->route('admin.sessions.index');
        return redirect()->route('participant.dashboard');
    }
    return view('participant.auth.login');
})->name('login');
```

---

### Langkah 3: Update `app/Http/Controllers/Admin/ExamSessionController.php`
Ada dua penyesuaian di controller ini:

#### 3.1. Filter Sesi di Method `index()` (Baris ~39)
User dengan role `admin` hanya boleh melihat sesi yang ditugaskan kepadanya, sama seperti `admin_sesi`.

**Sebelum:**
```php
if (auth()->user() && auth()->user()->role === 'admin_sesi') {
    $query->whereHas('participants', function ($q) {
        $q->where('user_id', auth()->id());
    });
}
```

**Sesudah:**
```php
if (auth()->user() && in_array(auth()->user()->role, ['admin_sesi', 'admin'])) {
    $query->whereHas('participants', function ($q) {
        $q->where('user_id', auth()->id());
    });
}
```

#### 3.2. Daftar User yang Dapat Ditugaskan di Method `show()` (Baris ~97)
Agar Superadmin dapat menugaskan user ber-role `admin` ke suatu sesi ujian:

**Sebelum:**
```php
$availableParticipants = User::whereIn('role', ['basic', 'premium', 'user', 'admin_sesi'])->orderBy('name')->get();
```

**Sesudah:**
```php
$availableParticipants = User::whereIn('role', ['basic', 'premium', 'user', 'admin_sesi', 'admin'])->orderBy('name')->get();
```

---

### Langkah 4: Proteksi Backend di `app/Http/Controllers/Admin/ExamSessionParticipantController.php`
Ini adalah proteksi paling krusial. Meskipun tombol di frontend disembunyikan, endpoint backend **wajib memblokir** user role `admin` dengan status kode `403 Forbidden`.

Buka file `app/Http/Controllers/Admin/ExamSessionParticipantController.php`:

Tambahkan pengecekan hak akses di awal 4 method berikut:
1. `store(Request $request)` - Menambah peserta yang sudah ada
2. `storeNewUser(Request $request)` - Membuat user baru dan menambahkannya ke sesi
3. `destroy($id)` - Menghapus peserta dari sesi
4. `updatePrivilege(Request $request, $id)` - Mengubah status privilege peserta

**Contoh Implementasi pada Masing-Masing Method:**

```php
public function store(Request $request)
{
    if (auth()->user()->role === 'admin') {
        return $this->errorResponse('Akses Ditolak. Role Admin hanya memiliki izin melihat data dan tidak dapat menambahkan peserta.', 403);
    }
    
    // ... kode yang sudah ada ...
}

public function storeNewUser(Request $request)
{
    if (auth()->user()->role === 'admin') {
        return $this->errorResponse('Akses Ditolak. Role Admin tidak diizinkan membuat akun atau menambahkan peserta baru.', 403);
    }

    // ... kode yang sudah ada ...
}

public function destroy($id)
{
    if (auth()->user()->role === 'admin') {
        return $this->errorResponse('Akses Ditolak. Role Admin tidak memiliki izin menghapus peserta.', 403);
    }

    // ... kode yang sudah ada ...
}

public function updatePrivilege(Request $request, $id)
{
    if (auth()->user()->role === 'admin') {
        return $this->errorResponse('Akses Ditolak. Role Admin tidak memiliki izin mengubah privilege peserta.', 403);
    }

    // ... kode yang sudah ada ...
}
```

> **Catatan:** Jika response web biasa (non-JSON), controller dapat memanggil `abort(403, 'Akses Ditolak...')`. Namun karena controller ini menggunakan trait `ResponseTrait` dan dipanggil via `fetch()` (AJAX), `$this->errorResponse(..., 403)` atau `abort(403)` akan mengembalikan JSON error 403 yang tepat.

---

### Langkah 5: Update Layout Admin `resources/views/layouts/admin.blade.php`
Buka file `resources/views/layouts/admin.blade.php`.
Cari blok style khusus `admin_sesi` pada baris 27-37:

**Sebelum:**
```blade
@if(auth()->user() && auth()->user()->role === 'admin_sesi')
    <style>
        .sidebar, .mobile-header, .sidebar-overlay { display: none !important; }
        .main-content { margin-left: 0 !important; max-width: 100%; margin: 0 auto; width: 100%; padding: 40px 32px; }
        @media (max-width: 768px) {
            .main-content { padding: 20px 16px; }
            header { flex-direction: column; align-items: flex-start !important; gap: 20px; }
            .search-container { max-width: 100% !important; }
        }
    </style>
@endif
```

**Sesudah:**
```blade
@if(auth()->user() && in_array(auth()->user()->role, ['admin_sesi', 'admin']))
    <style>
        .sidebar, .mobile-header, .sidebar-overlay { display: none !important; }
        .main-content { margin-left: 0 !important; max-width: 100%; margin: 0 auto; width: 100%; padding: 40px 32px; }
        @media (max-width: 768px) {
            .main-content { padding: 20px 16px; }
            header { flex-direction: column; align-items: flex-start !important; gap: 20px; }
            .search-container { max-width: 100% !important; }
        }
    </style>
@endif
```

---

### Langkah 6: Update Halaman Detail Sesi `resources/views/admin/sessions/show.blade.php`
Pada halaman ini, kita perlu memastikan elemen interaktif untuk menambah/mengubah/menghapus peserta dinonaktifkan jika yang login adalah role `admin`.

#### 6.1. Sembunyikan Tombol "Buat Peserta Baru" dan "Tambah Peserta"
Cari baris ~178-184:

**Sebelum:**
```blade
<button class="btn-primary" onclick="openNewUserModal()" style="height: 38px; font-size: 0.85rem; background: transparent; border: 1px solid var(--accent); color: var(--accent);">
    <i class="fas fa-plus"></i> Buat Peserta Baru
</button>
<button class="btn-primary" onclick="openParticipantModal()" style="height: 38px; font-size: 0.85rem;">
    <i class="fas fa-user-plus"></i> Tambah Peserta
</button>
```

**Sesudah:**
```blade
@if(auth()->user()->role !== 'admin')
<button class="btn-primary" onclick="openNewUserModal()" style="height: 38px; font-size: 0.85rem; background: transparent; border: 1px solid var(--accent); color: var(--accent);">
    <i class="fas fa-plus"></i> Buat Peserta Baru
</button>
<button class="btn-primary" onclick="openParticipantModal()" style="height: 38px; font-size: 0.85rem;">
    <i class="fas fa-user-plus"></i> Tambah Peserta
</button>
@endif
```

#### 6.2. Atur Tampilan Kolom Privilege & Aksi pada Tabel Peserta (SSR Blade)
Cari baris ~197-231:
- Jika user ber-role `'admin'`, dropdown privilege diganti menjadi teks / badge non-editable (disable atau plain text).
- Tombol delete peserta disembunyikan.

**Sesudah:**
```blade
<!-- Di thead baris ~197: -->
@if(auth()->user()->role !== 'admin')
<th style="width: 80px; text-align: center;">AKSI</th>
@endif

<!-- Di tbody baris ~220-231: -->
<td>
    @if(auth()->user()->role === 'admin')
        <span class="badge" style="font-size: 0.75rem; background: {{ $participant->privilege === 'premium' ? 'rgba(234, 179, 8, 0.1)' : 'rgba(59, 130, 246, 0.1)' }}; color: {{ $participant->privilege === 'premium' ? '#eab308' : '#3b82f6' }}; font-weight: 600;">
            {{ ucfirst($participant->privilege ?? 'general') }}
        </span>
    @else
        <select onchange="updateParticipantPrivilege({{ $participant->id }}, this.value)" class="form-input" style="padding: 4px 8px; font-size: 0.8rem; border-radius: 6px; margin-bottom: 0; background-color: {{ $participant->privilege === 'premium' ? 'rgba(234, 179, 8, 0.1)' : 'rgba(59, 130, 246, 0.1)' }}; color: {{ $participant->privilege === 'premium' ? '#eab308' : '#3b82f6' }}; font-weight: 600;">
            <option value="general" {{ $participant->privilege === 'general' ? 'selected' : '' }} style="background: #ffffff; color: #0f172a;">General</option>
            <option value="premium" {{ $participant->privilege === 'premium' ? 'selected' : '' }} style="background: #ffffff; color: #0f172a;">Premium</option>
        </select>
    @endif
</td>
<td style="color: var(--text-secondary); font-size: 0.85rem;">{{ data_get($participant, 'address') ?? '-' }}</td>
@if(auth()->user()->role !== 'admin')
<td style="text-align: center;">
    <button class="btn-icon delete" onclick="deleteParticipant({{ $participant->id }})" title="Hapus">
        <i class="fas fa-trash"></i>
    </button>
</td>
@endif
```

#### 6.3. Badge Role untuk Peserta di Tabel (SSR & JS)
Tambahkan penanganan warna badge untuk role `'admin'`:
```php
if($roleName === 'superadmin') { $bgRole = 'rgba(234, 179, 8, 0.1)'; $colorRole = '#eab308'; }
elseif($roleName === 'admin_sesi') { $bgRole = 'rgba(139, 92, 246, 0.1)'; $colorRole = '#8b5cf6'; }
elseif($roleName === 'admin') { $bgRole = 'rgba(6, 182, 212, 0.1)'; $colorRole = '#06b6d4'; }
```

#### 6.4. Sinkronisasi di Javascript Table Rendering (`renderTable`)
Di bagian script `resources/views/admin/sessions/show.blade.php`:
Definisikan variable role di script Blade:
```javascript
const isReadOnlyAdmin = @json(auth()->user()->role === 'admin');
```
Pada fungsi `renderTable(data)`:
- Jika `isReadOnlyAdmin` bernilai `true`, render kolom privilege sebagai static badge, dan **jangan** render kolom tombol delete.
- Pastikan colspan untuk state kosong disesuaikan (`isReadOnlyAdmin ? 6 : 7`).

#### 6.5. Proteksi Render Modal
Bungkus elemen modal HTML `#participantModal` dan `#newUserModal` dengan:
```blade
@if(auth()->user()->role !== 'admin')
    <!-- Modal Tambah Peserta & Buat User Baru -->
@endif
```

---

### Langkah 7: Update Manajemen User Superadmin (`ParticipantController.php` & `index.blade.php`)
Superadmin harus bisa membuat user baru atau mengubah user yang sudah ada menjadi role `admin`.

#### 7.1. File `app/Http/Controllers/Admin/ParticipantController.php`
Perbarui 3 tempat:
1. Method `index()` (Baris 22):
   ```php
   $query = User::whereIn('role', ['basic', 'admin_sesi', 'superadmin', 'admin'])->latest();
   ```
2. Method `store()` (Baris 48):
   ```php
   'role' => 'required|in:basic,admin_sesi,superadmin,admin',
   ```
3. Method `update()` (Baris 81):
   ```php
   'role' => 'required|in:basic,admin_sesi,superadmin,admin',
   ```

#### 7.2. File `resources/views/admin/participants/index.blade.php`
1. **Dropdown Pilihan Role (Baris ~180):**
   ```blade
   <select name="role" id="pRole" class="form-input" required>
       <option value="basic">Basic (Peserta Ujian)</option>
       <option value="admin">Admin (Viewer Sesi Ujian)</option>
       <option value="admin_sesi">Admin Sesi (Kelola Sesi & Peserta)</option>
       <option value="superadmin">Superadmin</option>
   </select>
   ```
2. **Badge Role di Tabel (Baris ~56):**
   ```blade
   @php
       $bgRole = 'rgba(59, 130, 246, 0.1)';
       $colorRole = '#3b82f6';
       if($user->role === 'superadmin') { $bgRole = 'rgba(234, 179, 8, 0.1)'; $colorRole = '#eab308'; }
       elseif($user->role === 'admin_sesi') { $bgRole = 'rgba(139, 92, 246, 0.1)'; $colorRole = '#8b5cf6'; }
       elseif($user->role === 'admin') { $bgRole = 'rgba(6, 182, 212, 0.1)'; $colorRole = '#06b6d4'; }
   @endphp
   ```

---

### Langkah 8: Filter Leaderboard Mingguan di `app/Services/QuizService.php`
Pastikan user dengan role `admin` tidak masuk ke daftar leaderboard mingguan peserta.

Buka `app/Services/QuizService.php`:
Cari pemanggilan `whereNotIn('role', ['superadmin', 'admin_sesi'])` pada baris 291, 306, dan 312:

Ubah ketiga baris tersebut menjadi:
```php
// Baris ~291
$query->whereNotIn('role', ['superadmin', 'admin_sesi', 'admin']);

// Baris ~306
if ($currentUser && ! in_array($currentUser->role, ['superadmin', 'admin_sesi', 'admin'])) {

// Baris ~312
$query->whereNotIn('role', ['superadmin', 'admin_sesi', 'admin']);
```

---

## 5. Rencana Pengujian Otomatis (Automated Testing)

Buat file test baru di `tests/Feature/AdminRolePermissionTest.php`.

Test suite ini wajib mencakup skenario-skenario berikut:

```php
<?php

namespace Tests\Feature;

use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $adminSesiUser;
    protected User $superAdmin;
    protected ExamSession $assignedSession;
    protected ExamSession $unassignedSession;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['role' => 'superadmin']);
        $this->adminUser = User::factory()->create(['role' => 'admin']);
        $this->adminSesiUser = User::factory()->create(['role' => 'admin_sesi']);

        $this->assignedSession = ExamSession::factory()->create(['name' => 'Sesi Ditugaskan']);
        $this->unassignedSession = ExamSession::factory()->create(['name' => 'Sesi Rahasia']);

        // Tugaskan adminUser ke assignedSession
        ExamSessionParticipant::create([
            'exam_session_id' => $this->assignedSession->id,
            'user_id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'access_code' => 'ADMIN01',
        ]);
    }

    /** 1. Test redirect login untuk role admin */
    public function test_admin_redirects_to_sessions_index_after_login(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/');
        $response->assertRedirect(route('admin.sessions.index'));
    }

    /** 2. Test role admin hanya melihat sesi yang ditugaskan */
    public function test_admin_only_sees_assigned_sessions(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.sessions.index'));
        $response->assertOk();
        $response->assertSee('Sesi Ditugaskan');
        $response->assertDontSee('Sesi Rahasia');
    }

    /** 3. Test role admin dapat melihat detail sesi yang ditugaskan */
    public function test_admin_can_view_assigned_session_details(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.sessions.show', $this->assignedSession->id));
        $response->assertOk();
        $response->assertSee('Daftar Peserta');
    }

    /** 4. Test role admin TIDAK BISA melihat tombol Tambah Peserta di halaman detail */
    public function test_admin_does_not_see_add_participant_buttons(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.sessions.show', $this->assignedSession->id));
        $response->assertOk();
        $response->assertDontSee('openParticipantModal');
        $response->assertDontSee('openNewUserModal');
    }

    /** 5. Test proteksi backend: admin DITOLAK saat menambahkan user lama (POST /admin/session-participants) */
    public function test_admin_cannot_store_existing_participant(): void
    {
        $student = User::factory()->create(['role' => 'basic']);

        $response = $this->actingAs($this->adminUser)->postJson(route('admin.session-participants.store'), [
            'exam_session_id' => $this->assignedSession->id,
            'user_ids' => [$student->id],
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('exam_session_participants', [
            'exam_session_id' => $this->assignedSession->id,
            'user_id' => $student->id,
        ]);
    }

    /** 6. Test proteksi backend: admin DITOLAK saat membuat user baru (POST /admin/session-participants/new) */
    public function test_admin_cannot_store_new_user_participant(): void
    {
        $response = $this->actingAs($this->adminUser)->postJson(route('admin.session-participants.store-new'), [
            'exam_session_id' => $this->assignedSession->id,
            'name' => 'Siswa Baru',
            'email' => 'siswabaru@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('users', ['email' => 'siswabaru@test.com']);
    }

    /** 7. Test proteksi backend: admin DITOLAK saat menghapus peserta */
    public function test_admin_cannot_delete_participant(): void
    {
        $student = User::factory()->create(['role' => 'basic']);
        $participant = ExamSessionParticipant::create([
            'exam_session_id' => $this->assignedSession->id,
            'user_id' => $student->id,
            'name' => $student->name,
            'access_code' => 'STU001',
        ]);

        $response = $this->actingAs($this->adminUser)->deleteJson(route('admin.session-participants.destroy', $participant->id));
        $response->assertStatus(403);
        $this->assertDatabaseHas('exam_session_participants', ['id' => $participant->id]);
    }

    /** 8. Test proteksi backend: admin DITOLAK saat mengubah privilege peserta */
    public function test_admin_cannot_update_participant_privilege(): void
    {
        $student = User::factory()->create(['role' => 'basic']);
        $participant = ExamSessionParticipant::create([
            'exam_session_id' => $this->assignedSession->id,
            'user_id' => $student->id,
            'name' => $student->name,
            'access_code' => 'STU002',
            'privilege' => 'general',
        ]);

        $response = $this->actingAs($this->adminUser)->patchJson(route('admin.session-participants.update-privilege', $participant->id), [
            'privilege' => 'premium',
        ]);

        $response->assertStatus(403);
        $this->assertEquals('general', $participant->fresh()->privilege);
    }

    /** 9. Test role admin DITOLAK saat mencoba akses menu Superadmin */
    public function test_admin_cannot_access_superadmin_menus(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.categories.index'));
        $response->assertForbidden();

        $responseQuestions = $this->actingAs($this->adminUser)->get(route('admin.questions.index'));
        $responseQuestions->assertForbidden();

        $responseParticipants = $this->actingAs($this->adminUser)->get(route('admin.participants.index'));
        $responseParticipants->assertForbidden();
    }
}
```

---

## 6. Checklist Verifikasi Manual (QA Acceptance Criteria)

Lakukan verifikasi manual berikut sebelum menandai pekerjaan selesai:

1. [ ] **Manajemen User Superadmin:**
   - Login sebagai `superadmin`, buka menu **Manajemen Peserta**.
   - Buka modal "Tambah Peserta Baru". Pastikan ada opsi **Admin (Viewer Sesi Ujian)** pada dropdown Role.
   - Buat satu user baru dengan role `admin` (misal `admin_test@jagobelajar.com`).
   - Pastikan badge pada tabel menampilkan nama `Admin` dengan warna badge yang jelas.
2. [ ] **Assign Admin ke Sesi Ujian:**
   - Masuk ke salah satu Sesi Ujian sebagai `superadmin`.
   - Klik "Tambah Peserta", cari akun `admin_test@jagobelajar.com` yang baru dibuat, lalu tambahkan ke sesi ujian.
3. [ ] **Login sebagai Admin Baru:**
   - Logout dari superadmin, login menggunakan akun `admin_test@jagobelajar.com`.
   - Pastikan diarahkan ke `/admin/sessions`.
   - Pastikan sidebar sebelah kiri tidak muncul (layout minimalis seperti `admin_sesi`).
   - Pastikan hanya sesi ujian yang ditugaskan tadi yang terlihat.
4. [ ] **Verifikasi Halaman Detail Sesi:**
   - Klik "Kelola Sesi" pada sesi yang muncul.
   - Pastikan tombol **"Buat Peserta Baru"** dan **"Tambah Peserta"** **TIDAK ADA**.
   - Pada tabel peserta, pastikan **TIDAK ADA tombol ikon sampah (Hapus)**.
   - Pastikan kolom **Privilege** tidak bisa diubah (berupa badge biasa, bukan dropdown select).
   - Pastikan fitur view (Preview Soal, Export CSV Nilai, Lihat Tab Nilai/Hasil) tetap bisa diakses secara normal.
5. [ ] **Uji Coba Penetrasi API (Backend Security):**
   - Buka Developer Tools / Console Browser saat login sebagai `admin`.
   - Jalankan `fetch('/admin/session-participants', { method: 'POST', ... })` atau kirim request penambahan peserta manual.
   - Pastikan response mengembalikan **HTTP 403 Forbidden**.
6. [ ] **Verifikasi Leaderboard:**
   - Pastikan user `admin_test@jagobelajar.com` tidak muncul di leaderboard mingguan peserta.
7. [ ] **Automated Test Lulus:**
   - Jalankan `php artisan test --filter=AdminRolePermissionTest`. Semua test harus berwarna hijau (**PASS**).
