<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Syarat & Ketentuan Ujian - {{ $session->name }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: var(--bg-dark);
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .terms-container {
            max-width: 800px;
            width: 100%;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            position: relative;
            overflow: hidden;
        }
        .terms-container::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }
        .terms-content {
            background: rgba(0,0,0,0.2);
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .terms-content h4 {
            color: var(--accent);
            margin-top: 0;
            margin-bottom: 12px;
        }
        .terms-content p, .terms-content li {
            color: var(--text-secondary);
            line-height: 1.6;
            font-size: 0.95rem;
            margin-bottom: 8px;
        }
        .terms-content ul {
            padding-left: 20px;
        }
    </style>
</head>
<body>

    <div class="terms-container animate-fade-in">
        <div style="text-align: center; margin-bottom: 24px;">
            <div style="width: 64px; height: 64px; background: rgba(59, 130, 246, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                <i class="fas fa-clipboard-list" style="font-size: 1.8rem; color: var(--primary);"></i>
            </div>
            <h2 style="font-family: 'Outfit', sans-serif; margin-bottom: 8px;">Syarat & Ketentuan Ujian</h2>
            <p style="color: var(--text-secondary);">Sesi: <strong>{{ $session->name }}</strong></p>
        </div>

        <div class="terms-content">
            <h4>1. Sistem Ujian Per Mata Pelajaran</h4>
            <p>Ujian ini menggunakan sistem modular (per mata pelajaran). Anda akan dihadapkan pada daftar mata pelajaran yang diujikan.</p>
            <ul>
                <li>Anda dapat memilih untuk mengerjakan mata pelajaran mana saja terlebih dahulu.</li>
                <li>Waktu ujian dihitung secara <strong>terpisah untuk setiap mata pelajaran</strong>.</li>
                <li>Waktu akan mulai berjalan saat Anda menekan tombol "Mulai" pada suatu mata pelajaran.</li>
            </ul>

            <h4 style="margin-top: 24px;">2. Aturan Pengerjaan</h4>
            <ul>
                <li>Jika waktu pada suatu mata pelajaran habis, jawaban Anda akan disimpan secara otomatis dan Anda tidak bisa lagi mengubah jawaban pada mata pelajaran tersebut.</li>
                <li>Anda <strong>tidak dapat</strong> kembali ke mata pelajaran yang sudah diselesaikan (dikumpulkan).</li>
                <li>Sesi ujian secara keseluruhan baru dianggap selesai jika Anda telah mengerjakan <strong>semua mata pelajaran</strong> dan menekan tombol "Akhiri Ujian Sesi".</li>
            </ul>

            <h4 style="margin-top: 24px;">3. Kejujuran dan Pelanggaran</h4>
            <ul>
                <li>Kerjakan soal dengan jujur dan mandiri.</li>
                <li>Segala bentuk kecurangan dapat mengakibatkan pembatalan hasil ujian.</li>
                <li>Pastikan koneksi internet Anda stabil selama proses pengerjaan.</li>
            </ul>
        </div>

        <form action="{{ route('exam.agree', $session->code) }}" method="POST">
            @csrf
            <label style="display: flex; gap: 12px; align-items: flex-start; margin-top: 16px; padding: 16px; background: rgba(255,255,255,0.03); border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                <input type="checkbox" name="agree_terms" required style="margin-top: 4px; width: 18px; height: 18px; accent-color: #3b82f6;">
                <span style="color: var(--text-secondary); line-height: 1.6; font-size: 0.95rem;">Saya telah membaca seluruh syarat dan ketentuan ujian, memahami aturan yang berlaku, dan setuju untuk melanjutkan ujian sesuai ketentuan tersebut.</span>
            </label>
            <div style="display: flex; gap: 16px; margin-top: 32px;">
                <a href="{{ route('participant.session.show', $session->id) }}" class="btn-primary" style="flex: 1; background: transparent; border: 1px solid var(--glass-border); color: var(--text-secondary); text-align: center; text-decoration: none;">
                    Kembali ke Detail
                </a>
                <button type="submit" class="btn-primary" style="flex: 2; font-weight: 600;">
                    Saya Setuju & Lanjutkan <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
                </button>
            </div>
        </form>
    </div>

</body>
</html>
