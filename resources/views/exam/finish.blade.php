<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ujian Selesai - IRT Exam System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body {
            background: #020617;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .finish-card {
            max-width: 500px;
            width: 100%;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 48px;
            text-align: center;
        }
        .icon-box {
            width: 80px;
            height: 80px;
            background: rgba(59, 130, 246, 0.1);
            color: var(--accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 32px;
        }
    </style>
</head>
<body>
    <div class="finish-card animate-fade-in">
        <div class="icon-box">
            <i class="fas fa-heart"></i>
        </div>
        <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.8rem; margin-bottom: 12px; color: white;">Terima Kasih!</h1>
        <p style="color: var(--text-secondary); line-height: 1.6; margin-bottom: 32px;">
            Jawaban Anda telah berhasil kami simpan. Sesi ujian Anda telah berakhir. Anda dapat menutup halaman ini sekarang.
        </p>

        <a href="{{ route('exam.index') }}" class="btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
            <i class="fas fa-home"></i> Kembali ke Beranda
        </a>

        <p style="color: var(--text-secondary); font-size: 0.8rem; margin-top: 48px;">
            IRT Exam System &bull; &copy; {{ date('Y') }}
        </p>
    </div>
</body>
</html>
