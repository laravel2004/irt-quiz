# 🐛 Issue: User Tiba-tiba Logout Saat Sedang Mengerjakan Soal / Admin Input Soal

**Severity:** High  
**Type:** Bug  
**Module:** Authentication / Session Management  
**Reporter:** Project Owner  
**Assignee:** Junior Developer / AI Model  

---

## 📋 Deskripsi Masalah

User (peserta ujian) atau admin yang sedang aktif menggunakan aplikasi tiba-tiba ter-logout setelah beberapa saat tidak melakukan request langsung ke server. Ini terjadi dalam dua skenario:

1. **Skenario 1 – Peserta ujian:** User login → masuk ke halaman ujian → sedang mengerjakan soal → setelah beberapa menit tiba-tiba di-redirect ke halaman login.
2. **Skenario 2 – Admin:** Admin login → membuka form input/edit soal → mengisi soal dengan perlahan → setelah agak lama klik submit, tapi sudah ter-redirect ke login.

Ini sangat merugikan karena progress ujian atau input soal bisa hilang.

---

## 🔍 Root Cause Analysis (Dugaan Awal)

Berdasarkan investigasi awal terhadap kode dan konfigurasi:

### 1. Session Berbasis Redis dengan Lifetime Pendek
- File: `config/session.php` dan `.env`
- `SESSION_DRIVER=redis` dan `SESSION_LIFETIME=120` (2 menit)
- Ini berarti session akan **expire setelah 120 menit** tapi ada juga kemungkinan masalah **idle timeout** jika Redis evict key lebih cepat.

### 2. Tidak Ada Mekanisme "Keep-Alive" / Refresh Session di Frontend
- Tidak ditemukan kode di sisi frontend (Blade views) yang secara aktif mem-"ping" server saat user idle (misal: sedang membaca soal tanpa klik apapun).
- Laravel session akan expire jika tidak ada request masuk dalam durasi `SESSION_LIFETIME`.

### 3. Auth Guard Hanya Berbasis Session (Tidak Ada Refresh Token)
- File: `config/auth.php` — guard yang digunakan adalah `web` dengan driver `session`.
- Tidak ada implementasi token-based auth (Sanctum/Passport), sehingga tidak ada mekanisme "refresh token" seperti di API.
- Saat session Redis expire → `Auth::check()` di middleware mengembalikan `false` → redirect ke login.

### 4. Middleware Langsung Redirect Tanpa Feedback yang Baik
- File: `app/Http/Middleware/AdminMiddleware.php`
- Saat session habis, middleware langsung redirect tanpa menyimpan intended URL, sehingga user harus login ulang dan kembali navigasi manual.

---

## 🎯 Tujuan Implementasi

1. **Investigasi** dan pastikan root cause yang sebenarnya.
2. **Implementasi session keep-alive** agar session tidak expire selama user masih aktif di halaman.
3. **Perpanjang SESSION_LIFETIME** ke nilai yang lebih masuk akal untuk konteks ujian.
4. **Perbaiki UX saat session expire** — user mendapat peringatan sebelum di-logout, bukan langsung di-redirect.
5. **(Opsional)** Simpan `intended URL` agar setelah re-login user kembali ke halaman yang sama.

---

## 📁 File yang Perlu Diinvestigasi

| File | Tujuan Investigasi |
|------|-------------------|
| `.env` | Cek `SESSION_LIFETIME`, `SESSION_DRIVER` |
| `config/session.php` | Cek konfigurasi session |
| `config/auth.php` | Cek auth guard yang digunakan |
| `app/Services/AuthService.php` | Cek apakah ada logout logic yang tidak terduga |
| `app/Http/Middleware/AdminMiddleware.php` | Cek behavior redirect saat auth gagal |
| `app/Http/Middleware/SuperAdminMiddleware.php` | Sama seperti di atas |
| `routes/web.php` | Cek middleware yang diterapkan ke setiap route |
| `app/Http/Controllers/ExamController.php` | Cek apakah ada manual session invalidation |
| `resources/views/` | Cek apakah ada JS polling / keep-alive di view ujian |

---

## 🛠️ Tahapan Implementasi (Step by Step)

---

### TAHAP 1 – INVESTIGASI & AUDIT KONFIGURASI

> **Estimasi waktu:** 30–60 menit  
> **Tujuan:** Pahami kondisi saat ini sebelum mengubah apapun.

#### Step 1.1 — Cek Nilai SESSION_LIFETIME di `.env`

Buka file `.env` dan catat:
```
SESSION_DRIVER=redis
SESSION_LIFETIME=120   ← ini dalam satuan MENIT
```

**Apa artinya?** Session akan invalid setelah 120 menit sejak **request terakhir**. Jika user sedang membaca soal panjang tanpa interaksi lebih dari 120 menit, mereka akan ter-logout.

**Pertanyaan yang perlu dijawab:**
- Apakah 120 menit sudah cukup? (Biasanya ujian bisa 2–3 jam)
- Apakah ada kemungkinan Redis memory penuh sehingga session dibuang lebih cepat? (Redis eviction policy)

#### Step 1.2 — Cek Redis Eviction Policy

Jalankan command berikut untuk mengecek konfigurasi Redis:

```bash
# Masuk ke container Redis (kalau pakai Docker)
docker exec -it <nama-container-redis> redis-cli

# Atau kalau Redis langsung di host:
redis-cli

# Lalu jalankan:
CONFIG GET maxmemory-policy
```

**Hasil yang AMAN:** `noeviction` atau `allkeys-lru` (tapi ini bisa hapus session aktif!)

**Jika hasilnya `allkeys-lru` atau `volatile-lru`**, ini bisa jadi penyebab user tiba-tiba logout karena Redis membuang session lama/jarang diakses saat memory penuh.

#### Step 1.3 — Trace Semua Middleware di Route Ujian

Buka `routes/web.php` dan perhatikan:

```php
Route::middleware(['auth'])->group(function() {
    // Routes peserta ujian
    Route::get('/exam/{code}/category/{id}', ...)
    ...
});
```

Middleware `auth` adalah bawaan Laravel yang memanggil `Authenticate` middleware. Cek file `vendor/laravel/framework/src/Illuminate/Auth/Middleware/Authenticate.php` untuk memahami apa yang terjadi saat autentikasi gagal.

#### Step 1.4 — Cek Apakah Ada Auto-Logout di JavaScript

Lakukan pencarian di seluruh folder views:

```bash
# Dari root project
grep -r "logout" resources/views/ --include="*.blade.php" -l
grep -r "setTimeout" resources/views/ --include="*.blade.php" -l
grep -r "setInterval" resources/views/ --include="*.blade.php" -l
```

Jika ditemukan `setTimeout` yang memanggil logout, itu bisa jadi penyebabnya.

#### Step 1.5 — Cek Log Laravel

```bash
# Lihat log terbaru
tail -n 100 storage/logs/laravel.log

# Atau kalau banyak, cari error auth
grep -i "session\|auth\|unauthenticated" storage/logs/laravel.log | tail -50
```

Perhatikan timestamp error — apakah terjadi persis setelah 120 menit?

---

### TAHAP 2 – FIX KONFIGURASI SESSION

> **Estimasi waktu:** 15–30 menit  
> **File yang diubah:** `.env`, `config/session.php`

#### Step 2.1 — Perpanjang SESSION_LIFETIME di `.env`

Edit file `.env`:

```diff
- SESSION_LIFETIME=120
+ SESSION_LIFETIME=480
```

> **Penjelasan:** `480` = 8 jam. Ini lebih masuk akal untuk sesi ujian atau kerja admin seharian. Sesuaikan dengan kebutuhan bisnis.

#### Step 2.2 — Pastikan Session Tidak Expire saat Ditutup Browser

Di `config/session.php`, cek baris ini:

```php
'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),
```

Pastikan nilainya `false` (sudah benar secara default). Jika `true`, session akan hilang begitu browser ditutup.

#### Step 2.3 — Jalankan Perintah Ini Setelah Mengubah .env

```bash
php artisan config:clear
php artisan cache:clear
```

Jangan lupa restart queue worker jika ada:

```bash
php artisan queue:restart
```

---

### TAHAP 3 – IMPLEMENTASI SESSION KEEP-ALIVE DI FRONTEND

> **Estimasi waktu:** 1–2 jam  
> **File yang dibuat/diubah:** Layout Blade utama (cek `resources/views/layouts/`)

Ini adalah solusi utama. Kita akan menambahkan JavaScript yang secara berkala "ping" ke server agar session tidak expire selama user masih membuka halaman.

#### Step 3.1 — Buat Endpoint Keep-Alive di Routes

Buka `routes/web.php` dan tambahkan route berikut **di dalam** group middleware `auth`:

```php
// Tambahkan di dalam Route::middleware(['auth'])->group(function() { ... })
Route::post('/keep-alive', function () {
    // Cukup touch session agar timestamp diperbarui
    session()->put('last_activity', now()->timestamp);
    return response()->json(['status' => 'ok', 'user' => auth()->id()]);
})->name('keep-alive');
```

> **Kenapa `POST`?** Karena `POST` memperbarui session di Laravel lebih andal dibanding `GET`, dan juga lebih aman dari CSRF.

#### Step 3.2 — Tambahkan JavaScript Keep-Alive di Layout Blade

Cari file layout utama yang digunakan oleh halaman ujian dan admin. Kemungkinan di:
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/admin.blade.php`
- `resources/views/layouts/participant.blade.php`

Tambahkan script berikut **sebelum tag `</body>`**:

```blade
{{-- Keep-Alive Script: Prevents session expiry while user is on the page --}}
@auth
<script>
(function() {
    // Ping server setiap 10 menit (600.000 ms) untuk menjaga session tetap aktif
    // Ganti angka ini jika SESSION_LIFETIME diubah (gunakan < setengah SESSION_LIFETIME)
    var KEEP_ALIVE_INTERVAL_MS = 10 * 60 * 1000; // 10 menit

    function pingServer() {
        fetch('{{ route("keep-alive") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                    ? document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    : ''
            },
            credentials: 'same-origin'
        })
        .then(function(response) {
            if (response.status === 401 || response.redirected) {
                // Session sudah expire, tampilkan peringatan
                showSessionExpiredAlert();
            }
        })
        .catch(function(err) {
            console.warn('[KeepAlive] Ping failed:', err);
        });
    }

    function showSessionExpiredAlert() {
        if (confirm('Sesi Anda telah berakhir. Klik OK untuk login kembali.')) {
            window.location.href = '/';
        }
    }

    // Mulai interval ping
    var keepAliveTimer = setInterval(pingServer, KEEP_ALIVE_INTERVAL_MS);

    // Bersihkan timer saat halaman ditutup
    window.addEventListener('beforeunload', function() {
        clearInterval(keepAliveTimer);
    });
})();
</script>
@endauth
```

> **Catatan penting:** Pastikan di `<head>` layout sudah ada meta CSRF token:
> ```blade
> <meta name="csrf-token" content="{{ csrf_token() }}">
> ```

#### Step 3.3 — Tambahkan Peringatan Visual Sebelum Session Expire (Opsional tapi Direkomendasikan)

Untuk UX yang lebih baik, tambahkan warning modal menggunakan CSS murni (tidak butuh library tambahan). Tambahkan di layout, sebelum `</body>`:

```html
@auth
<!-- Session Expiry Warning Modal -->
<div id="session-warning-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:99999; align-items:center; justify-content:center;">
    <div style="background:white; padding:30px; border-radius:12px; max-width:400px; text-align:center; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
        <h3 style="color:#e53e3e; margin-bottom:10px;">⚠️ Sesi Hampir Berakhir</h3>
        <p style="color:#4a5568; margin-bottom:20px;">Sesi Anda akan segera berakhir. Klik "Lanjutkan" untuk tetap login.</p>
        <button onclick="document.getElementById('session-warning-modal').style.display='none'; pingServer();" style="background:#3182ce; color:white; border:none; padding:10px 24px; border-radius:6px; cursor:pointer; margin-right:10px;">Lanjutkan</button>
        <button onclick="window.location.href='/'" style="background:#e53e3e; color:white; border:none; padding:10px 24px; border-radius:6px; cursor:pointer;">Logout</button>
    </div>
</div>
@endauth
```

---

### TAHAP 4 – PERBAIKI UX MIDDLEWARE (SIMPAN INTENDED URL)

> **Estimasi waktu:** 20–30 menit  
> **File yang diubah:** `app/Http/Middleware/AdminMiddleware.php`, `app/Http/Middleware/SuperAdminMiddleware.php`

Saat ini, saat session expire dan user coba akses halaman yang dilindungi, middleware hanya redirect ke login tanpa menyimpan URL tujuan. Setelah login ulang, user harus navigasi manual.

#### Step 4.1 — Update AdminMiddleware

Buka `app/Http/Middleware/AdminMiddleware.php`.

**Sebelum:**
```php
if (!Auth::check()) {
    return redirect()->route('login')->with('error', 'Please login to access this page.');
}
```

**Sesudah (gunakan `redirect()->guest()`):**
```php
if (!Auth::check()) {
    // redirect()->guest() otomatis menyimpan intended URL di session
    return redirect()->guest(route('login'))
        ->with('error', 'Sesi Anda telah berakhir. Silakan login kembali.');
}
```

> **Penjelasan `redirect()->guest()`:** Helper bawaan Laravel yang secara otomatis menyimpan URL tujuan di session (`url.intended`), sehingga setelah login, user bisa di-redirect ke halaman yang semula ingin diakses.

Lakukan hal yang sama untuk `app/Http/Middleware/SuperAdminMiddleware.php`.

#### Step 4.2 — Update AuthController untuk Menggunakan redirect()->intended()

Buka `app/Http/Controllers/AuthController.php`, method `login`.

**Sebelum:**
```php
if ($user->role === 'superadmin') {
    return redirect()->route('admin.dashboard');
}
if ($user->role === 'admin_sesi') {
    return redirect()->route('admin.sessions.index');
}
if ($user->role === 'basic') {
    return redirect()->route('participant.dashboard');
}
```

**Sesudah:**
```php
// redirect()->intended() akan redirect ke URL yang disimpan oleh guest(),
// jika tidak ada, akan fallback ke URL yang diberikan sebagai parameter.
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

---

### TAHAP 5 – TESTING & VERIFIKASI

> **Estimasi waktu:** 30–60 menit

#### Step 5.1 — Test Manual: Simulasi Session Expire

Untuk menguji dengan cepat tanpa harus menunggu 120 menit:

1. Ubah `SESSION_LIFETIME` ke `1` (1 menit) di `.env`
2. Jalankan `php artisan config:clear`
3. Login sebagai user/admin
4. Tunggu lebih dari 1 menit tanpa melakukan apa-apa (pastikan keep-alive script dinonaktifkan sementara untuk test ini, atau set interval-nya ke 5 menit agar bisa test expire)
5. Coba klik link/navigasi — pastikan:
   - Muncul peringatan (jika sudah diimplementasi di Step 3.3)
   - TIDAK langsung redirect brutal ke login tanpa pesan
   - Kalau redirect ke login, ada pesan error yang jelas

#### Step 5.2 — Test Keep-Alive Script

1. Buka browser DevTools → tab **Network**
2. Login dan buka halaman ujian atau admin
3. Tunggu 10 menit (atau sesuai interval yang diset di script)
4. Pastikan ada request `POST /keep-alive` muncul di Network tab secara berkala
5. Pastikan response-nya `200 OK` dengan body `{"status":"ok"}`

#### Step 5.3 — Test Skenario Admin Input Soal Lama

1. Login sebagai admin
2. Buka form input soal (`/admin/questions/create`)
3. Tunggu selama melebihi session lifetime lama (untuk test, set lifetime ke 2 menit)
4. Isi soal dan klik submit — pastikan soal berhasil tersimpan, BUKAN redirect ke login

#### Step 5.4 — Test Redirect Setelah Re-Login (Intended URL)

1. Logout terlebih dahulu
2. Buka URL `/admin/questions/create` langsung tanpa login (di browser URL bar)
3. Sistem harus redirect ke `/login` — ini normal
4. Login dengan akun admin
5. Sistem harus redirect otomatis kembali ke `/admin/questions/create`

Jika step 5 berhasil, berarti implementasi `redirect()->guest()` dan `redirect()->intended()` sudah benar.

#### Step 5.5 — Kembalikan SESSION_LIFETIME ke Nilai Normal

Setelah semua test selesai, kembalikan:
```
SESSION_LIFETIME=480
```

Dan jalankan lagi:
```bash
php artisan config:clear
php artisan cache:clear
```

---

### TAHAP 6 – MONITORING (OPSIONAL)

> **Estimasi waktu:** 30 menit  
> **Tujuan:** Deteksi dini jika ada masalah session di production.

#### Step 6.1 — Tambahkan Logging di Keep-Alive Endpoint

```php
Route::post('/keep-alive', function () {
    \Illuminate\Support\Facades\Log::info('KeepAlive ping', [
        'user_id'    => auth()->id(),
        'session_id' => session()->getId(),
        'ip'         => request()->ip(),
    ]);
    session()->put('last_activity', now()->timestamp);
    return response()->json(['status' => 'ok']);
})->name('keep-alive');
```

#### Step 6.2 — Cek Redis Memory & Session Count

```bash
# Cek memori Redis yang digunakan
redis-cli INFO memory | grep used_memory_human

# Cek jumlah key session
redis-cli KEYS "*" | grep -i session | wc -l
```

Jika memory Redis hampir penuh dan eviction policy adalah `allkeys-lru`, pertimbangkan:
- Meningkatkan memory Redis
- Gunakan dedicated Redis instance untuk session (pisah dari cache)
- Set eviction policy ke `noeviction` khusus untuk Redis session store

---

## ✅ Checklist Implementasi

Gunakan checklist ini untuk memastikan semua sudah dikerjakan:

- [x] **Tahap 1:** Audit selesai — catat temuan root cause yang sebenarnya
- [x] **Tahap 1.2:** Redis eviction policy sudah dicek dan aman
- [x] **Tahap 1.5:** Log Laravel sudah direview, tidak ada auto-logout tersembunyi
- [x] **Tahap 2:** `SESSION_LIFETIME` di `.env` sudah diubah ke `480`
- [x] **Tahap 2:** `php artisan config:clear && php artisan cache:clear` sudah dijalankan
- [x] **Tahap 3.1:** Route `/keep-alive` sudah ditambahkan di `routes/web.php` di dalam group `auth`
- [x] **Tahap 3.2:** Script keep-alive sudah ditambahkan di semua layout Blade yang relevan
- [x] **Tahap 3.2:** Meta CSRF token ada di `<head>` semua layout
- [x] **Tahap 4.1:** `AdminMiddleware` sudah menggunakan `redirect()->guest()`
- [x] **Tahap 4.1:** `SuperAdminMiddleware` sudah menggunakan `redirect()->guest()`
- [x] **Tahap 4.2:** `AuthController::login()` sudah menggunakan `redirect()->intended()`
- [ ] **Tahap 5.1:** Test manual session expire sudah dilakukan ✓
- [ ] **Tahap 5.2:** Test keep-alive di DevTools sudah dilakukan ✓
- [ ] **Tahap 5.3:** Test skenario admin input soal lama sudah dilakukan ✓
- [ ] **Tahap 5.4:** Test redirect setelah re-login (intended URL) sudah dilakukan ✓
- [ ] **Tahap 5.5:** `SESSION_LIFETIME` dikembalikan ke `480` setelah testing ✓

---

## ⚠️ Hal yang Perlu Diperhatikan

1. **Jangan hapus atau reset semua session Redis saat production sedang berjalan** — ini akan menyebabkan semua user ter-logout sekaligus.
2. **Keep-alive endpoint WAJIB di dalam group middleware `auth`** — jangan jadikan endpoint ini public.
3. **Interval keep-alive harus lebih kecil dari SESSION_LIFETIME** — jika `SESSION_LIFETIME=480` menit, interval keep-alive `10` menit sudah sangat aman.
4. **Setelah mengubah `.env`, wajib jalankan `php artisan config:clear`** — tanpa ini Laravel masih pakai konfigurasi lama.
5. **Jika menggunakan Docker**, restart container Redis setelah mengubah konfigurasi Redis:
   ```bash
   docker-compose restart redis
   ```

---

## 📚 Referensi Dokumentasi

- [Laravel Session Documentation](https://laravel.com/docs/session)
- [Laravel Authentication Documentation](https://laravel.com/docs/authentication)
- [Redis Eviction Policies](https://redis.io/docs/reference/eviction/)
