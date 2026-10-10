@extends('layouts.app')

@section('title', 'Dashboard Peserta')

@section('content')
<div class="container" style="padding: 40px 20px; max-width: 1200px; margin: 0 auto;">
    <!-- Logo Header -->
    <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 32px;">
        <div style="background: #dbeafe; padding: 12px; border-radius: 12px;">
            <img src="{{ asset('img/logo.png') }}" alt="Logo JagoBelajar" style="height: 40px; filter: none;">
        </div>
        <div>
            <div style="color: #0f172a; font-weight: 800; font-size: 24px; font-family: 'Outfit', sans-serif; letter-spacing: 0.5px;">JagoBelajar</div>
            <div style="color: var(--accent); font-weight: 600; font-size: 14px;">Bimbel & Try Out Online</div>
        </div>
    </div>
    <div class="flex-stack-mobile" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; gap: 20px;">
        <div>
            <h1 style="font-family: 'Outfit', sans-serif; font-size: 2.2rem; margin-bottom: 8px;">Selamat Datang, {{ auth()->user()->name }}</h1>
            <div style="display: flex; gap: 12px; align-items: center;">
                <span class="badge" style="background: {{ auth()->user()->role === 'premium' ? '#fef9c3' : '#dbeafe' }}; color: {{ auth()->user()->role === 'premium' ? '#854d0e' : '#1d4ed8' }}; padding: 4px 12px; border-radius: 20px; font-weight: 600; font-size: 0.8rem;">
                    <i class="fas {{ auth()->user()->role === 'premium' ? 'fa-crown' : 'fa-user' }}" style="margin-right: 4px;"></i>
                    Peserta {{ ucfirst(auth()->user()->role) }}
                </span>
                <span class="badge" style="background: #fdf4ff; color: #7c3aed; padding: 4px 12px; border-radius: 20px; font-weight: 600; font-size: 0.8rem;">
                    <i class="fas fa-star" style="margin-right: 4px; color: #f59e0b;"></i>
                    {{ number_format(auth()->user()->xp->total_xp ?? 0) }} XP
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

    @if(!empty($quizCard))
<style>
    @keyframes quizFloat { 0%,100% { transform: translateY(0) rotate(-4deg); } 50% { transform: translateY(-8px) rotate(4deg); } }
    @keyframes quizGlow  { 0%,100% { box-shadow: 0 0 0 0 rgba(255,255,255,.55); } 50% { box-shadow: 0 0 0 14px rgba(255,255,255,0); } }
    .quiz-today-icon { animation: quizFloat 3s ease-in-out infinite; }
    .quiz-today-cta  { animation: quizGlow 2s ease-in-out infinite; }
    @media (prefers-reduced-motion: reduce) { .quiz-today-icon, .quiz-today-cta { animation: none; } }
</style>
<div class="glass animate-fade-in flex-stack-mobile" style="padding: 28px 32px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap; background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%); border: none; color: #fff;">
    <div style="display: flex; align-items: center; gap: 20px;">
        <div class="quiz-today-icon" style="width: 64px; height: 64px; border-radius: 18px; background: rgba(255,255,255,.18); display: flex; align-items: center; justify-content: center; font-size: 1.8rem;">
            <i class="fas fa-bolt"></i>
        </div>
        <div>
            <h3 style="font-family: 'Outfit', sans-serif; margin: 0 0 4px; color: #fff;">Quiz Hari Ini</h3>
            <p style="margin: 0; opacity: .9; font-size: .95rem;">
                {{ $quizCard['pending'] }} soal menunggumu &middot; +{{ $quizCard['xp_per_correct'] }} XP tiap jawaban benar &middot; Total XP: {{ number_format($quizCard['total_xp']) }}
            </p>
            <p style="margin: 4px 0 0; opacity: .75; font-size: .8rem;">Kerjakan sebelum reset pukul {{ $quizCard['reset_at']->format('H:i') }}</p>
        </div>
    </div>
    <a href="{{ route('participant.quiz') }}" class="btn-primary quiz-today-cta" style="background: #fff; color: #2563eb; text-decoration: none; white-space: nowrap; font-weight: 700;">
        Mulai Quiz <i class="fas fa-arrow-right"></i>
    </a>
</div>
@endif

<div class="glass animate-fade-in" style="padding: 32px; margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 24px;">
            <div>
                <h3 style="font-family: 'Outfit', sans-serif; margin-bottom: 8px;">Grafik Nilai Sesi Ujian</h3>
                <p style="color: var(--text-secondary); margin: 0; font-size: 0.95rem;">Lihat perkembangan nilai dari setiap sesi ujian yang sudah kamu selesaikan.</p>
            </div>
            <span class="badge active" style="font-size: 0.75rem; white-space: nowrap;">Riwayat Nilai</span>
        </div>

        @if(!empty($scoreChartData['datasets']))
            <div style="height: 320px; position: relative;">
                <canvas id="scoreChart" aria-label="Grafik nilai sesi ujian" role="img"></canvas>
            </div>
        @else
            <div style="padding: 32px; border-radius: 16px; background: #f8fafc; text-align: center; color: var(--text-secondary);">
                <i class="fas fa-chart-line" style="font-size: 2rem; color: #2563eb; margin-bottom: 12px;"></i>
                <p style="margin: 0;">Grafik nilai akan muncul setelah kamu menyelesaikan sesi ujian.</p>
            </div>
        @endif
    </div>

    <div class="glass animate-fade-in" style="padding: 32px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
            <h3 style="font-family: 'Outfit', sans-serif; margin: 0;">Ujian Saya</h3>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="btn-primary" onclick="filterExam('all')" id="btn-filter-all" style="padding: 6px 16px; font-size: 0.85rem; border-radius: 99px;">Semua</button>
                <button type="button" class="btn-primary" onclick="filterExam('pending')" id="btn-filter-pending" style="background: #f1f5f9; color: #475569; padding: 6px 16px; font-size: 0.85rem; border-radius: 99px; border: 1px solid #cbd5e1;">Belum Dikerjakan</button>
                <button type="button" class="btn-primary" onclick="filterExam('done')" id="btn-filter-done" style="background: #f1f5f9; color: #475569; padding: 6px 16px; font-size: 0.85rem; border-radius: 99px; border: 1px solid #cbd5e1;">Sudah Selesai</button>
            </div>
        </div>
        
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
                $statusPengerjaan = $latestReg->finished_at ? 'done' : 'pending';
            @endphp
            <div class="glass card-hover exam-card" data-status="{{ $statusPengerjaan }}" style="padding: 24px; border-radius: 16px; display: flex; flex-direction: column; justify-content: space-between; position: relative;">
                <div style="position: absolute; top: 24px; right: 24px;">
                    @if($latestReg->privilege === 'premium')
                        <span class="badge" style="font-size: 0.7rem; background: #fef9c3; color: #854d0e; border: 1px solid #fde047;">
                            <i class="fas fa-star" style="margin-right: 4px;"></i> Premium
                        </span>
                    @else
                        <span class="badge" style="font-size: 0.7rem; background: #eff6ff; color: var(--text-secondary);">
                            General
                        </span>
                    @endif
                </div>
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; padding-right: 100px;">
                        <span class="badge {{ $session->is_active ? 'active' : '' }}" style="font-size: 0.7rem;">
                            {{ $session->is_active ? 'Sesi Terbuka' : 'Sesi Tertutup' }}
                        </span>
                    </div>
                    <h4 style="font-family: 'Outfit', sans-serif; font-size: 1.2rem; margin-bottom: 12px; color: #0f172a;">{{ $session->name }}</h4>
                    
                    <div style="background: #f8fafc; border-radius: 12px; padding: 16px; margin-bottom: 20px;">
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


                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <a href="{{ route('participant.session.show', $session->id) }}" class="btn-primary" style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; width: 100%; height: 44px; display: flex; align-items: center; justify-content: center; text-decoration: none; font-size: 0.9rem;">
                        <i class="fas fa-circle-info" style="margin-right: 8px;"></i> Detail Sesi
                    </a>





                    @if($isClosed && $session->discussion_pdf && $latestReg->privilege === 'premium')
                        <a href="{{ asset('storage/' . $session->discussion_pdf) }}" target="_blank" class="btn-primary" style="background: #dcfce7; color: #166534; border: 1px solid #86efac; width: 100%; height: 44px; display: flex; align-items: center; justify-content: center; text-decoration: none;">
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
<div id="aggregateModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(15, 23, 42, 0.18); backdrop-filter: blur(5px);">
    <div class="modal-content glass animate-fade-in" style="margin: 5% auto; padding: 0; border-radius: 24px; width: 90%; max-width: 800px; border: 1px solid var(--glass-border); overflow: hidden;">
        <div style="background: linear-gradient(135deg, #2563eb, #facc15); padding: 24px 32px; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="color: #ffffff; margin: 0; font-family: 'Outfit', sans-serif;"><i class="fas fa-chart-line" style="margin-right: 12px;"></i> Analisis Perkembangan AI</h2>
            <button onclick="closeAggregateModal()" style="background: none; border: none; color: #ffffff; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <div id="aggregateContent" style="padding: 32px;">
            <div id="aggregateLoading" style="text-align: center; padding: 40px;">
                <div style="width: 50px; height: 50px; border: 3px solid rgba(37, 99, 235, 0.18); border-top-color: #2563eb; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
                <p style="color: var(--text-secondary);">AI sedang menganalisis seluruh riwayat percobaan Anda, mohon tunggu sebentar...</p>
            </div>
            <div id="aggregateData" style="display: none; display: flex; flex-direction: column; gap: 24px;">
                <!-- Filled by JS -->
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const scoreChartData = @json($scoreChartData);

if (typeof Chart !== 'undefined' && document.getElementById('scoreChart') && scoreChartData.datasets.length) {
    const chartContext = document.getElementById('scoreChart');
    const isMobileChart = window.matchMedia('(max-width: 768px)').matches;

    new Chart(chartContext, {
        type: 'line',
        data: {
            labels: scoreChartData.labels,
            datasets: scoreChartData.datasets.map(dataset => ({
                ...dataset,
                backgroundColor: dataset.borderColor + '22',
                pointBackgroundColor: dataset.borderColor,
                pointBorderColor: '#ffffff',
                pointRadius: isMobileChart ? 3 : 4,
                pointHoverRadius: isMobileChart ? 5 : 6,
                borderWidth: isMobileChart ? 2 : 3,
                fill: true,
                tension: 0.3,
            }))
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: !isMobileChart,
                    labels: {
                        color: '#334155'
                    }
                },
                tooltip: {
                    callbacks: {
                        title: function(context) {
                            return context[0].label;
                        },
                        afterLabel: function(context) {
                            const predicate = scoreChartData.datasets[context.datasetIndex].predicates[context.dataIndex];
                            return predicate ? `Predikat: ${predicate}` : '';
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        color: '#475569',
                        maxRotation: 0,
                        autoSkip: true,
                        maxTicksLimit: isMobileChart ? 4 : 8,
                        callback: function(value) {
                            const label = this.getLabelForValue(value);
                            return isMobileChart && label.length > 8 ? label.slice(0, 8) + '...' : label;
                        }
                    },
                    grid: {
                        color: 'rgba(148, 163, 184, 0.12)'
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#475569',
                        maxTicksLimit: isMobileChart ? 5 : 8
                    },
                    grid: {
                        color: 'rgba(148, 163, 184, 0.12)'
                    }
                }
            }
        }
    });
}

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
                    <div style="padding: 20px; border-radius: 12px; border-left: 4px solid #2563eb; background: #f8fafc;">
                        <h4 style="color: #2563eb; margin-bottom: 8px; font-size: 0.9rem; text-transform: uppercase;"><i class="fas fa-chart-line"></i> Tren & Perkembangan</h4>
                        <p style="font-size: 0.95rem; color: var(--text-secondary); line-height: 1.6; margin: 0;">${res.data.analisis_progres}</p>
                    </div>
                    <div style="padding: 20px; border-radius: 12px; border-left: 4px solid #ef4444; background: #fef2f2;">
                        <h4 style="color: #ef4444; margin-bottom: 8px; font-size: 0.9rem; text-transform: uppercase;"><i class="fas fa-exclamation-triangle"></i> Pola Kekurangan</h4>
                        <p style="font-size: 0.95rem; color: var(--text-secondary); line-height: 1.6; margin: 0;">${res.data.pola_kekurangan}</p>
                    </div>
                    <div style="padding: 20px; border-radius: 12px; border-left: 4px solid #10b981; background: #f0fdf4;">
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
            background: '#ffffff',
            color: '#0f172a'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});

function filterExam(status) {
    document.querySelectorAll('.exam-card').forEach(card => {
        if (status === 'all' || card.dataset.status === status) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });

    const buttons = ['all', 'pending', 'done'];
    buttons.forEach(btn => {
        const el = document.getElementById('btn-filter-' + btn);
        if (!el) return;
        if (btn === status) {
            el.style.background = '#2563eb';
            el.style.color = '#fff';
            el.style.border = 'none';
        } else {
            el.style.background = '#f1f5f9';
            el.style.color = '#475569';
            el.style.border = '1px solid #cbd5e1';
        }
    });
}
</script>

@endsection


