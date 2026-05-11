@extends('layouts.app')

@section('title', 'Hasil Ujian: ' . $registration->examSession->name)

@section('content')
<div class="container" style="padding: 40px 20px; max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 32px;">
        <a href="{{ route('participant.dashboard') }}" style="color: var(--text-secondary); text-decoration: none; display: flex; align-items: center; gap: 8px; font-size: 0.9rem;">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>

    <div class="glass animate-fade-in" style="padding: 40px; border-radius: 24px; text-align: center;">
        <div style="margin-bottom: 40px;">
            <div style="width: 80px; height: 80px; background: rgba(var(--accent-rgb), 0.1); border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
                <i class="fas fa-chart-line" style="font-size: 2.5rem; color: var(--accent);"></i>
            </div>
            <h1 style="font-family: 'Outfit', sans-serif; font-size: 2rem; margin-bottom: 8px;">Hasil Ujian Anda</h1>
            <p style="color: var(--text-secondary);">{{ $registration->examSession->name }}</p>
        </div>

        <div class="score-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 40px;">
            <div class="glass" style="padding: 32px; border-radius: 20px; background: rgba(255, 255, 255, 0.03);">
                <div style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 12px; text-transform: uppercase; letter-spacing: 1px;">Skor Raw</div>
                <div class="score-value" style="font-size: 3rem; font-weight: 800; font-family: 'Outfit', sans-serif; color: white;">
                    {{ number_format($registration->result->score, 1) }}
                </div>
                <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 8px;">Maksimal: {{ $registration->examSession->max_score_raw }}</div>
            </div>
            <div class="glass" style="padding: 32px; border-radius: 20px; background: rgba(var(--accent-rgb), 0.05); border-color: rgba(var(--accent-rgb), 0.2);">
                <div style="font-size: 0.85rem; color: var(--accent); margin-bottom: 12px; text-transform: uppercase; letter-spacing: 1px;">Skor IRT</div>
                <div class="score-value" style="font-size: 3.5rem; font-weight: 800; font-family: 'Outfit', sans-serif; color: var(--accent);">
                    {{ round($registration->result->irt_score) }}
                </div>
                <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 8px;">Maksimal: {{ $registration->examSession->max_score_irt }}</div>
            </div>
        </div>

        <div class="stats-grid" style="background: rgba(255, 255, 255, 0.03); border-radius: 16px; padding: 24px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
            <div>
                <div style="font-size: 0.75rem; color: #10b981; margin-bottom: 4px;">BENAR</div>
                <div style="font-size: 1.25rem; font-weight: 700; color: #10b981;">{{ $registration->result->total_correct }}</div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: #ef4444; margin-bottom: 4px;">SALAH</div>
                <div style="font-size: 1.25rem; font-weight: 700; color: #ef4444;">{{ $registration->result->total_incorrect }}</div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 4px;">KOSONG</div>
                <div style="font-size: 1.25rem; font-weight: 700; color: white;">{{ $registration->result->total_blank }}</div>
            </div>
        </div>

        <div style="margin-top: 48px; padding-top: 32px; border-top: 1px solid var(--glass-border); color: var(--text-secondary); font-size: 0.85rem;">
            <p>Hasil ini bersifat final dan telah divalidasi menggunakan metode Item Response Theory (IRT).</p>
            <div class="btn-group" style="display: flex; gap: 12px; justify-content: center; margin-top: 24px;">
                @if($registration->examSession->discussion_pdf && auth()->user()->role === 'premium')
                <a href="{{ asset('storage/' . $registration->examSession->discussion_pdf) }}" target="_blank" class="btn-primary" style="height: 40px; font-size: 0.85rem; background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid #10b981; text-decoration: none;">
                    <i class="fas fa-file-download"></i> Download Pembahasan (PDF)
                </a>
                @endif
                
                @if(auth()->user()->role === 'premium')
                <a href="{{ route('participant.review', $registration->id) }}" class="btn-primary" style="height: 40px; font-size: 0.85rem; background: rgba(var(--accent-rgb), 0.1); color: var(--accent); border: 1px solid var(--accent); text-decoration: none;">
                    <i class="fas fa-key"></i> Kunci Jawaban
                </a>
                @endif
            </div>
        </div>

        @if(auth()->user()->role === 'premium')
        <div style="margin-top: 40px; border-top: 1px solid var(--glass-border); padding-top: 40px; text-align: left;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px;">
                <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #8b5cf6, #d946ef); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white;">
                    <i class="fas fa-robot"></i>
                </div>
                <h3 style="font-family: 'Outfit', sans-serif; margin: 0;">AI Smart Analysis</h3>
            </div>

            @if($registration->result->ai_analysis)
                @php $analysis = json_decode($registration->result->ai_analysis, true); @endphp
                <div class="responsive-grid" style="grid-template-columns: 1fr; gap: 20px;">
                    <div class="glass" style="padding: 24px; border-radius: 16px; border-left: 4px solid #10b981;">
                        <h4 style="color: #10b981; margin-bottom: 12px; font-size: 0.9rem; text-transform: uppercase;"><i class="fas fa-plus-circle"></i> Kelebihan</h4>
                        <p style="font-size: 0.95rem; color: var(--text-secondary); line-height: 1.6;">{{ $analysis['kelebihan'] }}</p>
                    </div>
                    <div class="glass" style="padding: 24px; border-radius: 16px; border-left: 4px solid #ef4444;">
                        <h4 style="color: #ef4444; margin-bottom: 12px; font-size: 0.9rem; text-transform: uppercase;"><i class="fas fa-minus-circle"></i> Kekurangan</h4>
                        <p style="font-size: 0.95rem; color: var(--text-secondary); line-height: 1.6;">{{ $analysis['kekurangan'] }}</p>
                    </div>
                    <div class="glass" style="padding: 24px; border-radius: 16px; border-left: 4px solid var(--accent); background: rgba(var(--accent-rgb), 0.03);">
                        <h4 style="color: var(--accent); margin-bottom: 12px; font-size: 0.9rem; text-transform: uppercase;"><i class="fas fa-lightbulb"></i> Rekomendasi</h4>
                        <p style="font-size: 0.95rem; color: var(--text-secondary); line-height: 1.6;">{{ $analysis['rekomendasi'] }}</p>
                    </div>
                </div>
            @else
                <div id="aiPlaceholder" style="text-align: center; padding: 40px; background: rgba(255,255,255,0.02); border: 1px dashed var(--glass-border); border-radius: 20px;">
                    <p style="color: var(--text-secondary); margin-bottom: 24px;">Dapatkan analisis mendalam dari AI mengenai performa tryout Anda secara otomatis.</p>
                    <button onclick="generateAIAnalysis()" id="aiBtn" class="btn-primary" style="background: linear-gradient(135deg, #8b5cf6, #d946ef); border: none;">
                        <i class="fas fa-sparkles"></i> Generate Analisis AI
                    </button>
                </div>
                <div id="aiLoading" style="display: none; text-align: center; padding: 40px;">
                    <div style="width: 50px; height: 50px; border: 3px solid rgba(139, 92, 246, 0.3); border-top-color: #8b5cf6; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
                    <p style="color: var(--text-secondary);">AI sedang menganalisis hasil Anda, mohon tunggu sebentar...</p>
                </div>
            @endif
        </div>
        @endif
    </div>
</div>

<script>
    function generateAIAnalysis() {
        const btn = document.getElementById('aiBtn');
        const placeholder = document.getElementById('aiPlaceholder');
        const loading = document.getElementById('aiLoading');

        placeholder.style.display = 'none';
        loading.style.display = 'block';

        fetch("{{ route('participant.analyze', $registration->id) }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            } else {
                alert(data.message || 'Gagal generate analisis AI');
                placeholder.style.display = 'block';
                loading.style.display = 'none';
            }
        })
        .catch(err => {
            console.error(err);
            alert('Terjadi kesalahan sistem');
            placeholder.style.display = 'block';
            loading.style.display = 'none';
        });
    }
</script>

<style>
    @keyframes spin { to { transform: rotate(360deg); } }
    
    @media (max-width: 768px) {
        .container { padding: 20px 12px !important; }
        .glass { padding: 24px !important; }
        .score-grid { grid-template-columns: 1fr !important; gap: 16px !important; }
        .score-value { font-size: 2.5rem !important; }
        .stats-grid { grid-template-columns: 1fr !important; gap: 12px !important; padding: 16px !important; }
        .btn-group { flex-direction: column !important; width: 100% !important; }
        .btn-group a { width: 100% !important; justify-content: center !important; }
        h1 { font-size: 1.5rem !important; }
    }

    @media print {
        body { background: white !important; color: black !important; }
        .glass { background: white !important; border: 1px solid #ddd !important; box-shadow: none !important; }
        .btn-primary, a { display: none !important; }
        .container { max-width: 100% !important; padding: 0 !important; }
        h1, h2, h3, p, div { color: black !important; }
        .glass div[style*="color: white"] { color: black !important; }
    }
</style>
@endsection
