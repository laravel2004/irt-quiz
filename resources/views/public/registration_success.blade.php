<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Berhasil</title>
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
        .success-card {
            max-width: 500px;
            width: 100%;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 48px;
            text-align: center;
        }
        .icon-circle {
            width: 80px;
            height: 80px;
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 32px;
        }
        .access-code-box {
            background: rgba(255, 255, 255, 0.03);
            border: 2px dashed rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 24px;
            margin: 32px 0;
        }
    </style>
</head>
<body>
    <div class="success-card animate-fade-in">
        <div class="icon-circle">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.8rem; margin-bottom: 12px; color: white;">Pendaftaran Berhasil!</h1>
        <p style="color: var(--text-secondary); line-height: 1.6;">Halo <strong>{{ data_get($participant, 'name') }}</strong>, pendaftaran Anda untuk sesi <strong>{{ $session->name }}</strong> telah berhasil disimpan.</p>

        <div class="access-code-box">
            <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 12px;">KODE AKSES UJIAN ANDA</div>
            <div style="font-size: 3rem; font-weight: 800; font-family: 'Outfit', sans-serif; color: #10b981; letter-spacing: 8px;">{{ data_get($participant, 'access_code') }}</div>
        </div>

        <div style="background: rgba(59, 130, 246, 0.1); padding: 16px; border-radius: 12px; text-align: left; margin-bottom: 32px;">
            <p style="font-size: 0.85rem; color: var(--accent); line-height: 1.5;">
                <i class="fas fa-exclamation-triangle" style="margin-right: 8px;"></i>
                Simpan kode akses ini dengan baik. Anda akan memerlukannya untuk masuk ke dalam sistem saat ujian dimulai.
            </p>
        </div>

        <p style="color: var(--text-secondary); font-size: 0.8rem;">
            IRT Exam System &bull; &copy; {{ date('Y') }}
        </p>
    </div>
</body>
</html>
