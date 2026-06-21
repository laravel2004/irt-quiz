<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Sesi: {{ $session->name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .form-container {
            max-width: 600px;
            width: 100%;
        }
        .header-banner {
            height: 120px;
            background: linear-gradient(135deg, var(--accent), #1d4ed8);
            border-radius: 16px 16px 0 0;
            position: relative;
            overflow: hidden;
        }
        .header-banner::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url('https://www.transparenttextures.com/patterns/carbon-fibre.png');
            opacity: 0.1;
        }
        .registration-card {
            background: #ffffff;
            backdrop-filter: blur(20px);
            border: 1px solid #e2e8f0;
            border-radius: 0 0 16px 16px;
            padding: 40px;
        }
        .session-info {
            background: #f8fafc;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 32px;
            border-left: 4px solid var(--accent);
        }
        @media (max-width: 768px) {
            .registration-card {
                padding: 20px;
            }
            .session-info {
                padding: 16px;
            }
            body {
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="header-banner"></div>
        <div class="registration-card animate-fade-in">
            <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.8rem; margin-bottom: 8px; color: #0f172a;">{{ $session->name }}</h1>
            <p style="color: #475569; margin-bottom: 24px;">Silakan lengkapi formulir pendaftaran di bawah ini untuk mengikuti sesi ujian.</p>

            <div class="session-info">
                <div class="flex-stack-mobile" style="display: flex; gap: 16px; width: 100%;">
                    <div style="flex: 1;">
                        <div style="font-size: 0.75rem; color: #475569; text-transform: uppercase; letter-spacing: 1px;">Tanggal Pelaksanaan</div>
                        <div style="font-weight: 600; margin-top: 4px; color: #0f172a;">{{ $session->start_date }}</div>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-size: 0.75rem; color: #475569; text-transform: uppercase; letter-spacing: 1px;">Durasi Ujian</div>
                        <div style="font-weight: 600; margin-top: 4px; color: #0f172a;">{{ $session->duration }} Menit</div>
                    </div>
                </div>
            </div>
            <form action="{{ route('public.session.register', $session->code) }}" method="POST">
                @csrf
                <div class="flex-stack-mobile" style="display: flex; gap: 16px; width: 100%;">
                    <div class="form-group" style="flex: 1; margin-bottom: 0;">
                        <label>Nama Lengkap</label>
                        <input type="text" name="name" class="form-input" placeholder="Nama Lengkap" value="{{ old('name') }}" required>
                        @error('name') <small style="color: #ef4444;">{{ $message }}</small> @enderror
                    </div>
                    <div class="form-group" style="flex: 1; margin-bottom: 0;">
                        <label>Nomor WhatsApp</label>
                        <input type="text" name="whatsapp" class="form-input" placeholder="081234567890" value="{{ old('whatsapp') }}" required>
                        @error('whatsapp') <small style="color: #ef4444;">{{ $message }}</small> @enderror
                    </div>
                </div>

                <div class="flex-stack-mobile" style="display: flex; gap: 16px; width: 100%; margin-top: 16px;">
                    <div class="form-group" style="flex: 1; margin-bottom: 0;">
                        <label>Email (Untuk Login)</label>
                        <input type="email" name="email" class="form-input" placeholder="email@contoh.com" value="{{ old('email') }}" required>
                        @error('email') <small style="color: #ef4444;">{{ $message }}</small> @enderror
                    </div>
                    <div class="form-group" style="flex: 1; margin-bottom: 0;">
                        <label>Password Akun</label>
                        <input type="password" name="password" class="form-input" placeholder="Min. 6 Karakter" required>
                        @error('password') <small style="color: #ef4444;">{{ $message }}</small> @enderror
                    </div>
                </div>

                <div class="form-group" style="margin-top: 16px;">
                    <label>Alamat Domisili</label>
                    <textarea name="address" class="form-input" style="height: 80px;" placeholder="Masukkan alamat lengkap">{{ old('address') }}</textarea>
                    @error('address') <small style="color: #ef4444;">{{ $message }}</small> @enderror
                </div>

                <button type="submit" class="btn-primary" style="width: 100%; height: 52px; font-size: 1rem; margin-top: 16px;">
                    Daftar Sekarang <i class="fas fa-paper-plane" style="margin-left: 8px;"></i>
                </button>
            </form>

            <p style="text-align: center; color: #475569; font-size: 0.8rem; margin-top: 32px;">
                IRT Exam System &bull; &copy; {{ date('Y') }}
            </p>
        </div>
    </div>
</body>
</html>
