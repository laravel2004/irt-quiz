<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Ujian - IRT Exam System</title>
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
            margin: 0;
            overflow: hidden;
        }
        .login-card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 48px;
            width: 100%;
            max-width: 400px;
            text-align: center;
            position: relative;
            z-index: 10;
        }
        .logo-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--accent), #1d4ed8);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            margin: 0 auto 24px;
            box-shadow: 0 8px 16px rgba(59, 130, 246, 0.3);
        }
        .access-input {
            font-size: 1.5rem;
            text-align: center;
            letter-spacing: 8px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 16px;
            border-radius: 12px;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--glass-border);
            color: white;
            width: 100%;
            margin-bottom: 24px;
            outline: none;
            transition: all 0.3s ease;
        }
        .access-input:focus {
            border-color: var(--accent);
            background: rgba(255,255,255,0.08);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }
        .bg-glow {
            position: absolute;
            width: 300px;
            height: 300px;
            background: var(--accent);
            filter: blur(150px);
            opacity: 0.15;
            z-index: 1;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <div class="bg-glow" style="top: -100px; left: -100px;"></div>
    <div class="bg-glow" style="bottom: -100px; right: -100px; background: #8b5cf6;"></div>

    <div class="login-card animate-fade-in">
        <div class="logo-icon">
            <i class="fas fa-graduation-cap"></i>
        </div>
        <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.5rem; color: white; margin-bottom: 8px;">Portal Ujian</h1>
        <p style="color: var(--text-secondary); margin-bottom: 32px; font-size: 0.9rem;">Masukkan kode akses 6 digit Anda untuk memulai ujian.</p>

        @if(session('error'))
            <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.85rem;">
                <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('exam.login') }}" method="POST">
            @csrf
            <input type="text" name="access_code" maxlength="6" class="access-input" placeholder="••••••" autocomplete="off" required autofocus>
            <button type="submit" class="btn-primary" style="width: 100%; height: 52px; font-size: 1rem; font-weight: 600;">
                Masuk Sesi <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
            </button>
        </form>

        <p style="margin-top: 32px; font-size: 0.75rem; color: var(--text-secondary);">
            &copy; {{ date('Y') }} IRT Exam System &bull; V1.0
        </p>
    </div>

    <script>
        // Auto uppercase and limit input
        const input = document.querySelector('.access-input');
        input.addEventListener('input', (e) => {
            e.target.value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        });
    </script>
</body>
</html>
