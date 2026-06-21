# Planning Implementasi Dashboard User untuk Sesi Ujian

## Ringkasan Kebutuhan

Perlu dibuat alur lengkap untuk user yang login agar bisa melihat sesi ujian yang ditugaskan, membuka detail sesi, membaca term, lalu memulai ujian dari sesi yang dipilih.

Fokus utama fitur ini:
1. Dashboard role user menampilkan card sesi ujian yang sudah di-assign ke user.
2. Card menampilkan informasi inti: nama sesi, tanggal ujian, dan status aktif / tidak aktif.
3. Card punya action untuk masuk ke detail sesi.
4. Detail sesi menampilkan informasi lebih lengkap seperti materi, durasi, dan instruksi khusus.
5. Dari detail sesi, user bisa menuju halaman ujian sesuai sesi yang dipilih.
6. Sebelum memulai ujian, user harus melihat dan menyetujui term terlebih dahulu.

Dokumen ini sengaja dibuat sebagai planning yang bisa langsung dikerjakan oleh junior programmer atau model AI yang lebih murah.

## Tujuan Implementasi

- User mudah melihat sesi ujian yang memang menjadi tanggung jawabnya.
- Alur dari dashboard ke detail sesi ke halaman ujian menjadi jelas dan terkontrol.
- User tidak bisa langsung masuk ke ujian tanpa membaca term.
- Setiap sesi ujian yang dipilih selalu membawa konteks data yang benar.

## Ruang Lingkup

Yang termasuk dalam pekerjaan ini:
- Dashboard user untuk daftar sesi ujian yang di-assign.
- Card sesi ujian dengan informasi ringkas.
- Halaman detail sesi ujian.
- Halaman term sebelum mulai ujian.
- Tombol mulai ujian yang mengarah ke halaman ujian sesuai sesi.
- Validasi akses agar user hanya bisa membuka sesi miliknya.

Yang tidak perlu dikerjakan dulu kecuali sudah ada di sistem:
- Perubahan besar pada engine pengerjaan soal.
- Perubahan struktur penilaian ujian.
- Perubahan admin panel untuk assign sesi, kecuali memang dibutuhkan untuk menampilkan data.

## Alur yang Diinginkan

1. User login dan masuk ke dashboard role user.
2. Sistem menampilkan daftar card sesi ujian yang di-assign ke user tersebut.
3. User klik action pada salah satu card untuk melihat detail sesi.
4. Halaman detail menampilkan informasi lengkap sesi.
5. User klik tombol untuk mulai ujian.
6. Sebelum ujian dimulai, user melihat halaman term.
7. User menyetujui term.
8. User diarahkan ke halaman ujian sesuai sesi yang dipilih.

## Data yang Harus Ditampilkan

### Di Dashboard User

Setiap card sesi ujian minimal berisi:
- Nama sesi ujian
- Tanggal ujian
- Status sesi, misalnya `aktif` atau `tidak aktif`
- Action untuk melihat detail

Jika tersedia, card juga boleh menampilkan data tambahan seperti:
- Jam ujian
- Ringkasan materi
- Label deadline atau periode akses

### Di Halaman Detail Sesi

Halaman detail sesi minimal menampilkan:
- Nama sesi ujian
- Tanggal ujian
- Status sesi
- Materi yang akan diujikan
- Durasi ujian
- Instruksi khusus jika ada
- Tombol lanjut ke term / mulai ujian

### Di Halaman Term

Halaman term minimal menampilkan:
- Judul term
- Isi term / aturan ujian
- Checkbox atau tombol persetujuan
- Tombol untuk lanjut ke ujian setelah setuju
- Tombol kembali ke detail sesi jika user batal

## Tahapan Implementasi

### Tahap 1: Pahami alur data user dan sesi ujian

Langkah pertama adalah memeriksa bagaimana sistem saat ini menyimpan dan mengambil data sesi ujian yang di-assign ke user.

Yang perlu dicek:
- Model yang menghubungkan user dengan sesi ujian
- Tabel relasi assignment jika ada
- Route dashboard user saat ini
- Controller yang merender dashboard user
- View yang dipakai untuk dashboard role user

Output tahap ini:
- Developer tahu data apa yang bisa dipakai tanpa membuat struktur baru yang tidak perlu.
- Developer tahu endpoint mana yang perlu diubah atau ditambah.

### Tahap 2: Siapkan query daftar sesi untuk user

Setelah alur data dipahami, buat query yang mengambil sesi ujian khusus untuk user login.

Kriteria query:
- Hanya ambil sesi yang benar-benar di-assign ke user tersebut.
- Sertakan data penting yang dibutuhkan card dashboard.
- Pastikan status aktif / tidak aktif ikut terbawa.
- Jika ada relasi materi atau instruksi, eager load supaya view tidak banyak query.

Hal yang perlu diperhatikan:
- Jangan ambil semua sesi dari database.
- Jangan tampilkan sesi milik user lain.
- Jika ada sesi duplikat dari assignment yang tidak normal, tangani dengan filter yang aman.

Output tahap ini:
- Controller atau service sudah punya data siap tampil untuk dashboard user.

### Tahap 3: Bangun tampilan card sesi ujian di dashboard

Buat atau sesuaikan komponen tampilan agar daftar sesi terlihat sebagai card yang mudah dibaca.

Setiap card harus punya struktur jelas:
- Judul di bagian atas
- Informasi tanggal ujian
- Status aktif / tidak aktif sebagai badge
- Tombol atau link action ke detail sesi

Catatan implementasi:
- Jika sesi tidak aktif, tampilkan badge yang jelas agar user tahu statusnya.
- Jika tidak ada sesi yang di-assign, tampilkan empty state yang informatif.
- UI harus tetap rapi pada layar mobile dan desktop.

Output tahap ini:
- Dashboard user menampilkan daftar sesi dalam bentuk card yang mudah dipahami.

### Tahap 4: Buat action dari card ke halaman detail sesi

Tambahkan action pada card untuk membuka halaman detail sesi ujian.

Hal yang harus dipastikan:
- Action membawa identifier sesi yang benar.
- Route detail hanya bisa diakses oleh user yang berhak.
- Jika user mencoba membuka sesi milik orang lain, sistem harus menolak dengan respons yang aman, misalnya 403 atau redirect.

Output tahap ini:
- Card di dashboard sudah bisa dipakai untuk masuk ke detail sesi.

### Tahap 5: Buat halaman detail sesi ujian

Halaman detail adalah tempat user membaca seluruh informasi sesi sebelum memulai ujian.

Isi halaman detail minimal:
- Nama sesi
- Status sesi
- Tanggal ujian
- Materi yang akan diujikan
- Durasi ujian
- Instruksi khusus
- Tombol lanjut ke term / mulai ujian

Saran implementasi:
- Susun informasi dari yang paling penting ke yang paling rinci.
- Jika instruksi khusus kosong, tampilkan teks default seperti `Tidak ada instruksi khusus`.
- Jika materi berupa daftar, tampilkan dalam list yang mudah dibaca.

Output tahap ini:
- User bisa memahami isi sesi sebelum menekan tombol mulai.

### Tahap 6: Tambahkan halaman term sebelum ujian

Sebelum user masuk ke halaman ujian, tampilkan halaman term terlebih dahulu.

Isi yang disarankan:
- Ringkasan aturan ujian
- Durasi akses / waktu pengerjaan
- Larangan umum jika memang ada
- Konfirmasi bahwa user sudah membaca dan menyetujui term

Perilaku yang diharapkan:
- User belum boleh masuk ke ujian sebelum menyetujui term.
- Tombol mulai ujian aktif hanya setelah checkbox atau aksi persetujuan dilakukan.
- Jika user menolak term, user tetap berada di halaman term atau kembali ke detail sesi.

Output tahap ini:
- Ada lapisan konfirmasi sebelum ujian dimulai.

### Tahap 7: Buat tombol mulai ujian yang mengarah ke sesi yang dipilih

Setelah term disetujui, user menekan tombol mulai ujian.

Yang harus dilakukan tombol ini:
- Membawa user ke route halaman ujian yang benar.
- Mengirim identifier sesi yang dipilih.
- Menjamin halaman ujian hanya menerima sesi yang sesuai dengan detail sebelumnya.

Hal yang perlu dijaga:
- Jangan hardcode session id di view.
- Gunakan route parameter atau mekanisme yang konsisten dengan struktur aplikasi.
- Jika sesi belum boleh diakses, tampilkan pesan yang jelas.

Output tahap ini:
- User bisa masuk ke halaman ujian dari sesi yang dipilih tanpa salah konteks.

### Tahap 8: Tambahkan validasi akses dan keamanan sederhana

Agar fitur aman dan tidak mudah disalahgunakan, tambahkan validasi dasar berikut:
- User hanya bisa melihat sesi yang di-assign ke dirinya.
- User hanya bisa memulai ujian dari sesi yang valid.
- Halaman term dan halaman ujian harus memeriksa hak akses lagi di backend.
- Jangan mengandalkan validasi dari frontend saja.

Kalau session assignment atau status sesi tidak valid:
- Tampilkan error yang ramah user.
- Jangan tampilkan data sensitif.

Output tahap ini:
- Alur lebih aman dan tidak mudah dibypass.

### Tahap 9: Tambahkan state kosong, error, dan loading

Supaya UI lebih matang, siapkan beberapa state penting:
- Saat user tidak punya sesi di-assign
- Saat data gagal dimuat
- Saat sesi tidak aktif
- Saat user belum menyetujui term

Ini penting agar fitur terasa jelas dan tidak membingungkan.

Output tahap ini:
- UI lebih informatif pada semua kondisi.

### Tahap 10: Uji alur end-to-end

Setelah implementasi selesai, lakukan pengecekan manual atau test sederhana untuk memastikan alurnya benar.

Skenario yang wajib diuji:
- User login dan melihat daftar sesi yang tepat.
- Card menampilkan nama sesi, tanggal, dan status dengan benar.
- Link detail membuka sesi yang sesuai.
- Detail sesi menampilkan materi, durasi, dan instruksi.
- Term tampil sebelum ujian.
- User hanya bisa lanjut setelah menyetujui term.
- Tombol mulai ujian mengarah ke halaman ujian yang sesuai.
- User tidak bisa akses sesi milik user lain.

## Checklist Implementasi untuk Junior Programmer

- [ ] Cari file controller / view dashboard user.
- [ ] Identifikasi relasi user ke sesi ujian.
- [ ] Buat query untuk mengambil sesi milik user login.
- [ ] Tampilkan daftar sesi dalam bentuk card.
- [ ] Tambahkan action ke halaman detail sesi.
- [ ] Buat halaman detail sesi ujian.
- [ ] Tambahkan halaman term sebelum mulai ujian.
- [ ] Tambahkan tombol setuju term dan mulai ujian.
- [ ] Pastikan route dan controller memvalidasi hak akses.
- [ ] Test semua alur dari dashboard sampai halaman ujian.

## Catatan Implementasi

- Prioritaskan perubahan kecil tapi konsisten dengan struktur project yang sudah ada.
- Jika ada pola view / controller / service yang sudah dipakai di project, ikuti pola tersebut.
- Jangan membuat struktur baru yang terlalu kompleks jika kebutuhan ini bisa diselesaikan dengan komponen yang sudah ada.
- Bila data materi, durasi, atau instruksi belum tersedia, koordinasikan field mana yang perlu dipakai dari database atau model yang sudah ada.

## Definisi Selesai

Fitur dianggap selesai jika:
- User bisa melihat sesi ujian yang di-assign di dashboard.
- User bisa membuka detail sesi dari card.
- User bisa membaca term sebelum masuk ujian.
- User hanya bisa mulai ujian setelah menyetujui term.
- User diarahkan ke halaman ujian yang sesuai dengan sesi yang dipilih.
- Validasi akses dasar sudah aman di backend.
