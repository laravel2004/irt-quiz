@extends('layouts.app')

@section('title', 'Dashboard Peserta')

@section('content')
<div class="container" style="padding: 40px 20px;">
    <div class="flex-stack-mobile" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; gap: 20px;">
        <div>
            <h1 style="font-family: 'Outfit', sans-serif; font-size: 2.2rem; margin-bottom: 8px;">Selamat Datang, {{ auth()->user()->name }}</h1>
            <div style="display: flex; gap: 12px; align-items: center;">
                <span class="badge" style="background: {{ auth()->user()->role === 'premium' ? 'rgba(234, 179, 8, 0.1)' : 'rgba(59, 130, 246, 0.1)' }}; color: {{ auth()->user()->role === 'premium' ? '#eab308' : '#3b82f6' }}; padding: 4px 12px; border-radius: 20px; font-weight: 600; font-size: 0.8rem;">
                    <i class="fas {{ auth()->user()->role === 'premium' ? 'fa-crown' : 'fa-user' }}" style="margin-right: 4px;"></i>
                    Peserta {{ ucfirst(auth()->user()->role) }}
                </span>
                <span style="color: var(--text-secondary); font-size: 0.9rem;">{{ auth()->user()->email }}</span>
            </div>
        </div>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn-primary" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid #ef4444;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </form>
    </div>

    @if(session('error'))
        <div class="glass animate-fade-in" style="margin-bottom: 24px; padding: 16px; background: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444;">
            <p style="color: #ef4444; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
            </p>
        </div>
    @endif

    <div class="glass animate-fade-in" style="padding: 32px;">
        <h3 style="font-family: 'Outfit', sans-serif; margin-bottom: 24px;">Ujian Saya</h3>
        
        <div class="responsive-grid" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px;">
            @forelse($groupedRegistrations as $sessionId => $sessionRegistrations)
            @php 
                $session = $sessionRegistrations->first()->examSession; 
                $latestReg = $sessionRegistrations->last();
                
                $now = now();
                $start = \Carbon\Carbon::parse($session->start_date . ' ' . $session->start_time);
                $end = \Carbon\Carbon::parse($session->end_date . ' ' . $session->end_time);
                $isPastEnd = $now->gt($end);
                $isBeforeStart = $now->lt($start);
                $isClosed = !$session->is_active || $isPastEnd;
            @endphp
            <div class="glass card-hover" style="padding: 24px; border-radius: 16px; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <span class="badge {{ $session->is_active ? 'active' : '' }}" style="font-size: 0.7rem;">
                                {{ $session->is_active ? 'Sesi Terbuka' : 'Sesi Tertutup' }}
                            </span>
                            @if($latestReg->privilege === 'premium')
                                <span class="badge" style="font-size: 0.7rem; background: rgba(234, 179, 8, 0.1); color: #eab308; border: 1px solid rgba(234, 179, 8, 0.2);">
                                    <i class="fas fa-star" style="margin-right: 4px;"></i> Premium
                                </span>
                            @else
                                <span class="badge" style="font-size: 0.7rem; background: rgba(255,255,255,0.05); color: var(--text-secondary);">
                                    General
                                </span>
                            @endif
                        </div>
                        <code style="color: var(--accent); font-size: 0.8rem; font-weight: 600;">{{ $session->code }}</code>
                    </div>
                    <h4 style="font-family: 'Outfit', sans-serif; font-size: 1.2rem; margin-bottom: 12px; color: white;">{{ $session->name }}</h4>
                    
                    <div style="background: rgba(255,255,255,0.03); border-radius: 12px; padding: 16px; margin-bottom: 20px;">
                        <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px; font-size: 0.85rem; color: var(--text-secondary);">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-calendar-check" style="width: 16px; color: #10b981;"></i> 
                                <span><strong>Mulai:</strong> {{ \Carbon\Carbon::parse($session->start_date . ' ' . $session->start_time)->translatedFormat('d M Y, H:i') }} WIB</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-calendar-times" style="width: 16px; color: #ef4444;"></i> 
                                <span><strong>Berakhir:</strong> {{ \Carbon\Carbon::parse($session->end_date . ' ' . $session->end_time)->translatedFormat('d M Y, H:i') }} WIB</span>
                            </div>
                        </div>

                        <div style="border-top: 1px solid rgba(255,255,255,0.05); padding-top: 12px;">
                            <div style="font-size: 0.8rem; font-weight: 600; color: white; margin-bottom: 8px;">Riwayat Pengerjaan ({{ $sessionRegistrations->count() }} Percobaan):</div>
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                @foreach($sessionRegistrations as $index => $reg)
                                    <div style="display: flex; align-items: center; justify-content: space-between; background: rgba(0,0,0,0.2); padding: 8px 12px; border-radius: 8px; font-size: 0.8rem;">
                                        <span>Percobaan {{ $index + 1 }}</span>
                                        @if($reg->finished_at)
                                            <div style="display: flex; gap: 6px;">
                                                <a href="{{ route('participant.result', $reg->id) }}" style="color: var(--accent); text-decoration: none;"><i class="fas fa-chart-bar"></i> Hasil</a>
                                                <span style="color: var(--glass-border);">|</span>
                                                <a href="{{ route('participant.review', $reg->id) }}" style="color: #3b82f6; text-decoration: none;"><i class="fas fa-book-open"></i> Laporan</a>
                                            </div>
                                        @else
                                            <a href="{{ route('exam.terms', $session->code) }}" style="color: #10b981; font-weight: 600; text-decoration: none;"><i class="fas fa-play"></i> Lanjutkan</a>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px;">
                    @if($sessionRegistrations->count() >= 2 && $latestReg->privilege === 'premium' && $latestReg->finished_at)
                        <button onclick="showAggregateAnalysis({{ $session->id }})" class="btn-primary" style="background: linear-gradient(135deg, #8b5cf6, #d946ef); border: none; width: 100%; height: 44px; font-size: 0.9rem;">
                            <i class="fas fa-sparkles"></i> Analisis Perkembangan AI
                        </button>
                    @endif

                    @if($latestReg->finished_at)
                        @if(!$isClosed)
                            <a href="{{ route('participant.statistics', $session->id) }}" class="btn-primary" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.2); width: 100%; height: 44px; display: flex; align-items: center; justify-content: center; text-decoration: none; font-size: 0.9rem;">
                                <i class="fas fa-list-ol" style="margin-right: 8px;"></i> Statistik Sementara
                            </a>
                        @else
                            <a href="{{ route('participant.statistics', $session->id) }}" class="btn-primary" style="background: rgba(234, 179, 8, 0.1); color: #eab308; border: 1px solid rgba(234, 179, 8, 0.2); width: 100%; height: 44px; display: flex; align-items: center; justify-content: center; text-decoration: none; font-size: 0.9rem;">
                                <i class="fas fa-trophy" style="margin-right: 8px;"></i> Statistik Final
                            </a>
                        @endif
                    @endif

                    @if(!$isClosed && $latestReg->finished_at && $latestReg->privilege === 'premium')
                        <form action="{{ route('participant.retake', $session->id) }}" method="POST" class="retake-form">
                            @csrf
                            <button type="submit" class="btn-primary" style="background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); width: 100%; height: 44px;">
                                <i class="fas fa-redo-alt"></i> Kerjakan Ulang
                            </button>
                        </form>
                    @elseif(!$isClosed && !$latestReg->finished_at && !$isBeforeStart)
                         <a href="{{ route('exam.terms', $session->code) }}" class="btn-primary" style="width: 100%; height: 44px; display: flex; align-items: center; justify-content: center; text-decoration: none;">
                            Lanjutkan Ujian <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
                        </a>
                    @endif

                    @if($isClosed && $session->discussion_pdf && $latestReg->privilege === 'premium')
                        <a href="{{ asset('storage/' . $session->discussion_pdf) }}" target="_blank" class="btn-primary" style="background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); width: 100%; height: 44px; display: flex; align-items: center; justify-content: center; text-decoration: none;">
                            <i class="fas fa-file-pdf" style="margin-right: 8px;"></i> Download PDF Pembahasan
                        </a>
                    @endif
                </div>
            </div>
            @empty
            <div style="grid-column: 1 / -1; text-align: center; padding: 60px; color: var(--text-secondary);">
                <i class="fas fa-book-open" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.2;"></i>
                <p>Anda belum terdaftar di sesi ujian manapun.</p>
            @endforelse
        </div>
    </div>
</div>

<!-- Modal Aggregate Analysis -->
<div id="aggregateModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.8); backdrop-filter: blur(5px);">
    <div class="modal-content glass animate-fade-in" style="margin: 5% auto; padding: 0; border-radius: 24px; width: 90%; max-width: 800px; border: 1px solid var(--glass-border); overflow: hidden;">
        <div style="background: linear-gradient(135deg, #8b5cf6, #d946ef); padding: 24px 32px; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="color: white; margin: 0; font-family: 'Outfit', sans-serif;"><i class="fas fa-chart-line" style="margin-right: 12px;"></i> Analisis Perkembangan AI</h2>
            <button onclick="closeAggregateModal()" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <div id="aggregateContent" style="padding: 32px;">
            <div id="aggregateLoading" style="text-align: center; padding: 40px;">
                <div style="width: 50px; height: 50px; border: 3px solid rgba(139, 92, 246, 0.3); border-top-color: #8b5cf6; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
                <p style="color: var(--text-secondary);">AI sedang menganalisis seluruh riwayat percobaan Anda, mohon tunggu sebentar...</p>
            </div>
            <div id="aggregateData" style="display: none; display: flex; flex-direction: column; gap: 24px;">
                <!-- Filled by JS -->
            </div>
        </div>
    </div>
</div>

<script>
function showAggregateAnalysis(sessionId) {
    const modal = document.getElementById('aggregateModal');
    const loading = document.getElementById('aggregateLoading');
    const dataDiv = document.getElementById('aggregateData');
    
    modal.style.display = 'block';
    loading.style.display = 'block';
    dataDiv.style.display = 'none';

    fetch(`/dashboard/aggregate-analysis/${sessionId}`)
        .then(res => res.json())
        .then(res => {
            loading.style.display = 'none';
            if(res.status === 'success') {
                dataDiv.style.display = 'flex';
                dataDiv.innerHTML = `
                    <div style="padding: 20px; border-radius: 12px; border-left: 4px solid #3b82f6; background: rgba(255,255,255,0.02);">
                        <h4 style="color: #3b82f6; margin-bottom: 8px; font-size: 0.9rem; text-transform: uppercase;"><i class="fas fa-chart-line"></i> Tren & Perkembangan</h4>
                        <p style="font-size: 0.95rem; color: var(--text-secondary); line-height: 1.6; margin: 0;">${res.data.analisis_progres}</p>
                    </div>
                    <div style="padding: 20px; border-radius: 12px; border-left: 4px solid #ef4444; background: rgba(255,255,255,0.02);">
                        <h4 style="color: #ef4444; margin-bottom: 8px; font-size: 0.9rem; text-transform: uppercase;"><i class="fas fa-exclamation-triangle"></i> Pola Kekurangan</h4>
                        <p style="font-size: 0.95rem; color: var(--text-secondary); line-height: 1.6; margin: 0;">${res.data.pola_kekurangan}</p>
                    </div>
                    <div style="padding: 20px; border-radius: 12px; border-left: 4px solid #10b981; background: rgba(255,255,255,0.02);">
                        <h4 style="color: #10b981; margin-bottom: 8px; font-size: 0.9rem; text-transform: uppercase;"><i class="fas fa-road"></i> Strategi Lanjutan</h4>
                        <p style="font-size: 0.95rem; color: var(--text-secondary); line-height: 1.6; margin: 0;">${res.data.strategi_lanjutan}</p>
                    </div>
                `;
            } else {
                dataDiv.style.display = 'block';
                dataDiv.innerHTML = `<div style="color: #ef4444; text-align: center; padding: 20px;"><i class="fas fa-times-circle" style="font-size: 2rem; margin-bottom: 12px;"></i><br>${res.message || 'Gagal memuat analisis.'}</div>`;
            }
        })
        .catch(err => {
            loading.style.display = 'none';
            dataDiv.style.display = 'block';
            dataDiv.innerHTML = `<div style="color: #ef4444; text-align: center; padding: 20px;">Terjadi kesalahan saat memuat data.</div>`;
        });
}

function closeAggregateModal() {
    document.getElementById('aggregateModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('aggregateModal');
    if (event.target === modal) {
        closeAggregateModal();
    }
}

document.querySelectorAll('.retake-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Kerjakan Ulang?',
            text: "Anda akan memulai percobaan baru. Lanjutkan?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'Ya, Lanjutkan',
            cancelButtonText: 'Batal',
            background: 'rgba(15, 23, 42, 0.95)',
            color: '#fff'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});
</script>

@endsection
