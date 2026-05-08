# IRT Exam System

## Project Overview

IRT Exam System adalah aplikasi web berbasis Laravel yang digunakan untuk mengelola bank soal dan pelaksanaan ujian online menggunakan metode penilaian Item Response Theory (IRT).

Sistem ini memiliki 2 role utama:
- Admin
- User

Admin bertugas untuk mengelola bank soal, sesi ujian, dan melakukan penilaian menggunakan metode IRT. Sedangkan user bertugas untuk mengikuti ujian secara online sesuai sesi yang tersedia.

---

# Main Goals

Tujuan utama aplikasi:

- Menyediakan sistem bank soal terstruktur
- Mendukung ujian online berbasis sesi
- Mengimplementasikan penilaian menggunakan IRT
- Menyediakan auto save jawaban menggunakan LocalStorage
- Menyediakan countdown timer ujian
- Mendukung auto submit ketika waktu habis

---

# Tech Stack

## Backend
- Laravel
- PHP 8+
- MySQL

## Frontend
- Blade Template
- JavaScript
- AJAX

## Storage
- Browser LocalStorage

---

# Roles

## Admin

Admin memiliki fitur:

- CRUD Bank Soal
- CRUD Kategori Soal
- CRUD Sesi Ujian
- Enrollment Soal ke Sesi
- Melihat Peserta
- Menutup Sesi
- Menjalankan Penilaian IRT

---

## User

User memiliki fitur:

- Registrasi
- Login
- Mengikuti Ujian
- Menjawab Soal
- Auto Save Jawaban
- Auto Submit

---

# Business Flow

## 1. Registrasi User

User melakukan registrasi menggunakan form:

- Nama
- No HP
- Email
- Usia
- Alamat

Data user akan masuk ke dashboard admin.

---

## 2. Kode Ujian

Setiap peserta atau sesi memiliki kode ujian.

Kode ini digunakan untuk identifikasi ujian.

---

## 3. Pembuatan Bank Soal

Admin membuat bank soal berdasarkan kategori seperti:

- IPA
- MTK
- Kimia
- Bahasa Inggris
- dan kategori lainnya

---

## 4. Pembuatan Sesi

Admin membuat sesi ujian seperti:

- Sesi 1
- Sesi 2
- Sesi 3

---

## 5. Enrollment Soal

Admin melakukan enrollment soal berdasarkan kategori ke sesi tertentu.

Contoh:
- Sesi 1 → IPA
- Sesi 2 → MTK

---

## 6. Link Pendaftaran

Setiap sesi memiliki link pendaftaran tersendiri.

User menggunakan link tersebut untuk mengikuti sesi.

---

## 7. Pengaturan Waktu

Sesi memiliki:

- start_date
- end_date
- start_time
- end_time

User hanya dapat mengakses ujian pada waktu aktif.

---

## 8. Penutupan Sesi

Admin dapat menutup sesi ujian.

Jika sesi ditutup:
- user tidak dapat mengakses ujian
- jawaban tidak dapat diubah

---

## 9. Penilaian IRT

Setelah sesi selesai:

Admin dapat menjalankan:
- perhitungan nilai menggunakan IRT

Tujuan:
- mengukur kemampuan peserta
- analisis tingkat kesulitan soal
- evaluasi hasil ujian

---

## 10. Pengerjaan Ujian

Saat user mengerjakan soal:

- terdapat countdown timer
- jawaban otomatis tersimpan di LocalStorage
- waktu ujian juga tersimpan
- refresh browser tidak menghilangkan jawaban

Jika waktu habis:
- sistem otomatis submit jawaban

---

# Main Features

## Admin Features

- Dashboard Admin
- CRUD Kategori
- CRUD Soal
- CRUD Sesi
- Enrollment Soal
- Penilaian IRT
- Manajemen User

---

## User Features

- Registrasi
- Login
- Dashboard Ujian
- Countdown Timer
- Auto Save
- Auto Submit

---

# Main Database Entities

## users
Menyimpan data user dan admin.

---

## categories
Menyimpan kategori soal.

---

## question_banks
Menyimpan bank soal.

---

## sessions
Menyimpan sesi ujian.

---

## session_questions
Relasi soal dengan sesi.

---

## user_answers
Menyimpan jawaban user.

---

## exam_results
Menyimpan hasil ujian.

---

# Coding Convention

## API Response

Semua response menggunakan Traits.

Contoh:
- successResponse()
- errorResponse()
- validationResponse()

---

# Suggested Folder Structure

```bash
app/
├── Http/
├── Models/
├── Traits/
├── Services/
├── Repositories/
├── Helpers/
├── Actions/
└── DTOs/
```

---

# Suggested Architecture

Menggunakan pattern:

- Service Repository Pattern
- Trait Response Pattern
- Modular Feature Structure

---

# LocalStorage Usage

Digunakan untuk:

- menyimpan jawaban sementara
- menyimpan countdown timer

Tujuan:
- menghindari kehilangan data saat refresh browser

---

# Future Improvements

- Randomisasi Soal
- Statistik Ujian
- Export PDF
- Dashboard Analytics
- Real-time Monitoring
- Adaptive Test
- Email Notification
- Ranking Peserta

---

# Development Notes

## Naming Convention

Gunakan naming yang konsisten:

### Controller
- SessionController
- QuestionBankController

### Service
- SessionService
- QuestionBankService

### Repository
- SessionRepository
- QuestionBankRepository

---

# Recommended Root Project Name

```bash
irt-exam-system
```

---

# Summary

IRT Exam System adalah aplikasi ujian online berbasis Laravel yang mendukung pengelolaan bank soal, sesi ujian, serta penilaian menggunakan metode Item Response Theory (IRT) dengan fitur auto save dan auto submit untuk meningkatkan reliability sistem ujian.