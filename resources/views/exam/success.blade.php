<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ujian Selesai - {{ $session->name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body {
            background-color: #f8fafc;
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .success-card {
            background: #ffffff;
            backdrop-filter: blur(12px);
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.10);
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .icon-circle {
            width: 100px;
            height: 100px;
            background: #dcfce7;
            border: 2px solid #86efac;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            color: #166534;
            font-size: 3rem;
            animation: popIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.2s both;
        }
        @keyframes popIn {
            0% { opacity: 0; transform: scale(0.5); }
            70% { transform: scale(1.1); }
            100% { opacity: 1; transform: scale(1); }
        }
        h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 2.5rem;
            margin-bottom: 12px;
            background: linear-gradient(135deg, #2563eb, #facc15);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        p.subtitle {
            color: #475569;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 32px;
        }
        .score-board {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 40px;
            animation: fadeIn 0.8s ease 0.4s both;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .score-item {
            background: #f8fafc;
            border: 1px solid #dbeafe;
            border-radius: 16px;
            padding: 24px;
            transition: transform 0.3s ease;
        }
        .score-item:hover {
            transform: translateY(-5px);
            background: #eff6ff;
        }
        .score-value {
            font-family: 'Outfit', sans-serif;
            font-size: 2.5rem;
            font-weight: 800;
            color: #2563eb;
            margin-bottom: 8px;
        }
        .score-label {
            color: #475569;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        .info-box {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 16px;
            border-radius: 8px;
            text-align: left;
            margin-bottom: 32px;
            font-size: 0.95rem;
            color: #475569;
            display: flex;
            gap: 16px;
            align-items: flex-start;
        }
        .info-box i {
            font-size: 1.2rem;
            color: #2563eb;
            margin-top: 2px;
        }
    </style>
</head>
<body>

    <div class="success-card">
        <div class="icon-circle">
            <i class="fas fa-check"></i>
        </div>
        
        <h1>Selamat!</h1>
        <p class="subtitle">Anda telah menyelesaikan seluruh rangkaian ujian<br><strong>{{ $session->name }}</strong>.</p>
        
        <div class="score-board">
            <div class="score-item">
                <div class="score-value">{{ $rawScore }}</div>
                <div class="score-label">Total Skor Raw</div>
            </div>
            <div class="score-item">
                <div class="score-value">{{ $answeredQuestions }} / {{ $totalQuestions }}</div>
                <div class="score-label">Soal Terjawab</div>
            </div>
        </div>

        <div style="text-align: left; margin-bottom: 24px;">
            <h3 style="font-family: 'Outfit', sans-serif; margin-bottom: 16px; font-size: 1.2rem; color: #0f172a;">Rincian Skor per Mata Pelajaran</h3>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                @foreach($categoryScores as $cs)
                    <div style="background: #f8fafc; border: 1px solid #dbeafe; border-radius: 12px; padding: 16px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 600; color: #0f172a; margin-bottom: 4px;">{{ $cs['name'] }}</div>
                            <div style="font-size: 0.85rem; color: #475569;">Terjawab: {{ $cs['answered'] }} / {{ $cs['total'] }} Soal</div>
                        </div>
                        <div style="font-family: 'Outfit', sans-serif; font-size: 1.5rem; font-weight: 700; color: #2563eb;">
                            {{ $cs['score'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>Perhatian:</strong> Skor yang ditampilkan di atas adalah <strong>Skor Mentah (Raw Score)</strong> berdasarkan jawaban benar dan salah biasa. Skor IRT (Item Response Theory) yang sebenarnya baru akan muncul di Dashboard Anda setelah ujian ini ditutup dan di-generate oleh Admin.
            </div>
        </div>

        <a href="{{ route('participant.dashboard') }}" class="btn-primary" style="width: 100%; height: 54px; font-size: 1.1rem; justify-content: center;">
            <i class="fas fa-home" style="margin-right: 8px;"></i> Kembali ke Dashboard
        </a>
    </div>

</body>
</html>
