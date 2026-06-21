@extends('layouts.app')

@section('title', 'Detail Sesi Ujian')

@section('content')
<div class="container" style="padding: 40px 20px;">
    <div style="margin-bottom: 24px;">
        <a href="{{ route('participant.dashboard') }}" style="color: var(--text-secondary); text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 0.95rem;">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>

    <div class="glass animate-fade-in" style="padding: 32px; border-radius: 24px; margin-bottom: 24px;">
        <div class="flex-stack-mobile" style="display: flex; justify-content: space-between; gap: 24px; align-items: flex-start; margin-bottom: 28px;">
            <div>
                <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 12px; flex-wrap: wrap;">
                    <span class="badge {{ $session->is_active ? 'active' : '' }}" style="font-size: 0.75rem;">
                        {{ $session->is_active ? 'Aktif' : 'Tidak Aktif' }}
                    </span>
                    @if($isBeforeStart)
                        <span class="badge" style="font-size: 0.75rem; background: rgba(59, 130, 246, 0.1); color: #3b82f6;">Belum Dimulai</span>
                    @elseif($isPastEnd)
                        <span class="badge" style="font-size: 0.75rem; background: rgba(239, 68, 68, 0.1); color: #ef4444;">Sudah Berakhir</span>
                    @else
                        <span class="badge" style="font-size: 0.75rem; background: rgba(16, 185, 129, 0.1); color: #10b981;">Dalam Periode Ujian</span>
                    @endif
                </div>
                <h1 style="font-family: 'Outfit', sans-serif; font-size: 2rem; margin-bottom: 10px; color: white;">{{ $session->name }}</h1>
                <p style="color: var(--text-secondary); margin: 0; line-height: 1.6;">Baca detail sesi, materi, durasi, dan instruksi sebelum memulai ujian.</p>
            </div>

            <div style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: flex-end;">
                @if($latestRegistration->finished_at)
                    <a href="{{ route('participant.result', $latestRegistration->id) }}" class="btn-primary" style="height: 44px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none;">
                        Lihat Hasil <i class="fas fa-chart-bar" style="margin-left: 8px;"></i>
                    </a>
                    <a href="{{ route('participant.statistics', $session->id) }}" class="btn-primary" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.2); height: 44px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none;">
                        <i class="fas fa-list-ol" style="margin-right: 8px;"></i> {{ $isClosed ? 'Statistik Final' : 'Statistik Sementara' }}
                    </a>
                    @if($latestRegistration->privilege === 'premium' && $registrations->count() >= 2 && !$isClosed && !$isBeforeStart)
                        <form action="{{ route('participant.retake', $session->id) }}" method="POST" class="retake-form" style="margin: 0;">
                            @csrf
                            <button type="submit" class="btn-primary" style="background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); height: 44px;">
                                <i class="fas fa-redo-alt"></i> Kerjakan Ulang
                            </button>
                        </form>
                        <button onclick="showAggregateAnalysis({{ $session->id }})" class="btn-primary" style="background: linear-gradient(135deg, #8b5cf6, #d946ef); border: none; height: 44px; font-size: 0.9rem;">
                            <i class="fas fa-sparkles"></i> Analisis Perkembangan AI
                        </button>
                    @endif
                @elseif(!$isClosed && !$latestRegistration->finished_at && !$isBeforeStart)
                    <a href="{{ route('exam.terms', $session->code) }}" class="btn-primary" style="height: 44px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none;">
                        Lanjut ke Term <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
                    </a>
                @else
                    <button class="btn-primary" disabled style="height: 44px; opacity: 0.55; cursor: not-allowed;">
                        Ujian Belum Bisa Dimulai
                    </button>
                @endif
            </div>
        </div>

        <div class="responsive-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 28px;">
            <div style="background: rgba(255,255,255,0.03); border-radius: 16px; padding: 18px;">
                <div style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 8px;"><i class="fas fa-calendar-check" style="color: #10b981; margin-right: 8px;"></i>Mulai</div>
                <div style="color: white; font-weight: 700;">{{ $start->translatedFormat('d M Y, H:i') }} WIB</div>
            </div>
            <div style="background: rgba(255,255,255,0.03); border-radius: 16px; padding: 18px;">
                <div style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 8px;"><i class="fas fa-calendar-times" style="color: #ef4444; margin-right: 8px;"></i>Berakhir</div>
                <div style="color: white; font-weight: 700;">{{ $end->translatedFormat('d M Y, H:i') }} WIB</div>
            </div>
            <div style="background: rgba(255,255,255,0.03); border-radius: 16px; padding: 18px;">
                <div style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 8px;"><i class="fas fa-clock" style="color: #3b82f6; margin-right: 8px;"></i>Total Durasi</div>
                <div style="color: white; font-weight: 700;">{{ $totalDuration }} menit</div>
            </div>
            <div style="background: rgba(255,255,255,0.03); border-radius: 16px; padding: 18px;">
                <div style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 8px;"><i class="fas fa-list-check" style="color: var(--accent); margin-right: 8px;"></i>Total Soal</div>
                <div style="color: white; font-weight: 700;">{{ $totalQuestions }} soal</div>
            </div>
        </div>

        @if($isBeforeStart)
            <div style="padding: 16px; border-radius: 14px; background: rgba(59, 130, 246, 0.1); border-left: 4px solid #3b82f6; color: #bfdbfe; margin-bottom: 24px;">
                Ujian belum dimulai. Silakan kembali pada {{ $start->translatedFormat('d F Y, H:i') }} WIB.
            </div>
        @elseif($isClosed)
            <div style="padding: 16px; border-radius: 14px; background: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444; color: #fecaca; margin-bottom: 24px;">
                Sesi ujian tidak aktif atau periode ujian sudah berakhir.
            </div>
        @endif

        @php
            $hasAttemptHistory = $registrations->contains(fn($reg) => $reg->started_at || $reg->finished_at);
            $shouldShowAttemptHistory = $latestRegistration->privilege === 'premium' && $hasAttemptHistory;
        @endphp

        @php
            $finishedAttempts = $registrations->filter(fn($reg) => $reg->finished_at && $reg->result);
            $showAttemptScoreChart = $latestRegistration->privilege === 'premium' && $finishedAttempts->count() >= 2;
        @endphp

        @if($showAttemptScoreChart)
        <div style="margin-bottom: 28px;">
            <h3 style="font-family: 'Outfit', sans-serif; margin-bottom: 16px; color: white;">Grafik Nilai Percobaan</h3>
            <div style="background: rgba(255,255,255,0.03); border-radius: 16px; padding: 20px;">
                <p style="color: var(--text-secondary); margin: 0 0 16px 0; line-height: 1.6;">Section ini hanya untuk user sesi premium dan menampilkan perkembangan nilai dari setiap percobaan yang sudah selesai.</p>
                <div style="position: relative; height: 320px;">
                    <canvas id="attemptScoreChart"></canvas>
                </div>
            </div>
        </div>
        @endif

        @if($shouldShowAttemptHistory)
        <div style="margin-bottom: 28px;">
            <h3 style="font-family: 'Outfit', sans-serif; margin-bottom: 16px; color: white;">Riwayat Pengerjaan</h3>
            <div style="background: rgba(255,255,255,0.03); border-radius: 16px; padding: 18px; display: flex; flex-direction: column; gap: 10px;">
                @foreach($registrations as $index => $reg)
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; background: rgba(0,0,0,0.18); padding: 12px 14px; border-radius: 12px;">
                        <div>
                            <div style="color: white; font-weight: 600; font-size: 0.95rem;">Percobaan {{ $index + 1 }}</div>
                            <div style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 4px;">
                                @if($reg->finished_at)
                                    Selesai pada {{ \Carbon\Carbon::parse($reg->finished_at)->translatedFormat('d M Y, H:i') }} WIB
                                @elseif($reg->started_at)
                                    Sedang berlangsung sejak {{ \Carbon\Carbon::parse($reg->started_at)->translatedFormat('d M Y, H:i') }} WIB
                                @else
                                    Belum dikerjakan
                                @endif
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            @if($reg->finished_at)
                                <a href="{{ route('participant.result', $reg->id) }}" class="btn-primary" style="background: rgba(255,255,255,0.06); color: white; border: 1px solid rgba(255,255,255,0.08); height: 40px; display: inline-flex; align-items: center; text-decoration: none;">
                                    <i class="fas fa-chart-bar" style="margin-right: 8px;"></i> Hasil
                                </a>
                                <a href="{{ route('participant.review', $reg->id) }}" class="btn-primary" style="background: rgba(59, 130, 246, 0.1); color: #bfdbfe; border: 1px solid rgba(59, 130, 246, 0.18); height: 40px; display: inline-flex; align-items: center; text-decoration: none;">
                                    <i class="fas fa-book-open" style="margin-right: 8px;"></i> Laporan
                                </a>
                            @else
                                <a href="{{ route('exam.terms', $session->code) }}" class="btn-primary" style="height: 40px; display: inline-flex; align-items: center; text-decoration: none;">
                                    <i class="fas fa-play" style="margin-right: 8px;"></i> Lanjutkan Ujian
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <div style="margin-bottom: 28px;">
            <h3 style="font-family: 'Outfit', sans-serif; margin-bottom: 16px; color: white;">Materi yang Diujikan</h3>
            <div style="display: flex; flex-direction: column; gap: 14px;">
                @forelse($sessionCategories as $sessionCategory)
                    <div style="background: rgba(0,0,0,0.18); border: 1px solid rgba(255,255,255,0.05); border-radius: 16px; padding: 18px;">
                        <div style="display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 10px;">
                            <h4 style="font-family: 'Outfit', sans-serif; margin: 0; color: white;">{{ $sessionCategory->category->name ?? 'Materi tidak diketahui' }}</h4>
                            <div style="display: flex; gap: 10px; color: var(--text-secondary); font-size: 0.85rem; flex-wrap: wrap;">
                                <span><i class="fas fa-clock"></i> {{ $sessionCategory->duration }} menit</span>
                                <span><i class="fas fa-question-circle"></i> {{ $sessionCategory->total_questions }} soal</span>
                            </div>
                        </div>

                        @if($sessionCategory->subCategories->isNotEmpty())
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                @foreach($sessionCategory->subCategories as $subCategory)
                                    <span style="background: rgba(59, 130, 246, 0.1); color: #bfdbfe; border: 1px solid rgba(59, 130, 246, 0.18); border-radius: 999px; padding: 6px 10px; font-size: 0.8rem;">
                                        {{ $subCategory->subCategory->name ?? 'Sub materi' }} - {{ $subCategory->percentage }}%
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <p style="color: var(--text-secondary); margin: 0; font-size: 0.9rem;">Tidak ada rincian sub materi.</p>
                        @endif
                    </div>
                @empty
                    <div style="color: var(--text-secondary); padding: 20px; text-align: center; background: rgba(255,255,255,0.03); border-radius: 14px;">
                        Materi ujian belum tersedia.
                    </div>
                @endforelse
            </div>
        </div>

        <div style="background: rgba(255,255,255,0.03); border-radius: 16px; padding: 20px;">
            <h3 style="font-family: 'Outfit', sans-serif; margin-bottom: 12px; color: white;">Instruksi Khusus</h3>
            <p style="color: var(--text-secondary); line-height: 1.7; margin: 0;">
                Tidak ada instruksi khusus tambahan untuk sesi ini. Ikuti syarat dan ketentuan yang akan ditampilkan sebelum ujian dimulai.
            </p>
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
            <div id="aggregateLoading" style="display: flex; flex-direction: column; gap: 18px; align-items: center; text-align: center; padding: 40px;">
                <div style="width: 50px; height: 50px; border: 3px solid rgba(139, 92, 246, 0.3); border-top-color: #8b5cf6; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
                <p style="color: var(--text-secondary);">AI sedang membaca pola peningkatan nilai, konsistensi jawaban, dan peluang perbaikan dari seluruh percobaan Anda.</p>
            </div>
            <div id="aggregateData" style="display: none; flex-direction: column; gap: 24px;"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chartCanvas = document.getElementById('attemptScoreChart');
    if (!chartCanvas) {
        return;
    }

    const labels = {!! json_encode($finishedAttempts->values()->map(fn($reg, $index) => 'Percobaan ' . ($index + 1))->all()) !!};
    const rawScores = {!! json_encode($finishedAttempts->values()->map(fn($reg) => round((float) $reg->result->score, 2))->all()) !!};
    const irtScores = {!! json_encode($finishedAttempts->values()->map(fn($reg) => round((float) $reg->result->irt_score, 2))->all()) !!};

    new Chart(chartCanvas, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Skor Raw',
                    data: rawScores,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.18)',
                    tension: 0.35,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6
                },
                {
                    label: 'Skor IRT',
                    data: irtScores,
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.12)',
                    tension: 0.35,
                    fill: false,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        color: '#e5e7eb'
                    }
                }
            },
            scales: {
                x: {
                    ticks: { color: '#9ca3af' },
                    grid: { color: 'rgba(255,255,255,0.06)' }
                },
                y: {
                    ticks: { color: '#9ca3af' },
                    grid: { color: 'rgba(255,255,255,0.06)' }
                }
            }
        }
    });
});

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
            if (res.status === 'success') {
                dataDiv.style.display = 'flex';
                dataDiv.innerHTML = `
                    <div style="padding: 20px; border-radius: 12px; border-left: 4px solid #3b82f6; background: rgba(255,255,255,0.02);">
                        <h4 style="color: #3b82f6; margin-bottom: 8px; font-size: 0.9rem; text-transform: uppercase;"><i class="fas fa-chart-line"></i> Ringkasan Progres</h4>
                        <p style="font-size: 0.95rem; color: var(--text-secondary); line-height: 1.6; margin: 0;">${res.data.analisis_progres}</p>
                    </div>
                    <div style="padding: 20px; border-radius: 12px; border-left: 4px solid #ef4444; background: rgba(255,255,255,0.02);">
                        <h4 style="color: #ef4444; margin-bottom: 8px; font-size: 0.9rem; text-transform: uppercase;"><i class="fas fa-exclamation-triangle"></i> Pola yang Masih Menghambat</h4>
                        <p style="font-size: 0.95rem; color: var(--text-secondary); line-height: 1.6; margin: 0;">${res.data.pola_kekurangan}</p>
                    </div>
                    <div style="padding: 20px; border-radius: 12px; border-left: 4px solid #10b981; background: rgba(255,255,255,0.02);">
                        <h4 style="color: #10b981; margin-bottom: 8px; font-size: 0.9rem; text-transform: uppercase;"><i class="fas fa-road"></i> Rekomendasi Langkah Berikutnya</h4>
                        <p style="font-size: 0.95rem; color: var(--text-secondary); line-height: 1.6; margin: 0;">${res.data.strategi_lanjutan}</p>
                    </div>
                `;
            } else {
                dataDiv.style.display = 'block';
                dataDiv.innerHTML = `<div style="color: #ef4444; text-align: center; padding: 20px;"><i class="fas fa-times-circle" style="font-size: 2rem; margin-bottom: 12px;"></i><br>${res.message || 'Analisis belum bisa dimuat saat ini.'}</div>`;
            }
        })
        .catch(() => {
            loading.style.display = 'none';
            dataDiv.style.display = 'block';
            dataDiv.innerHTML = `<div style="color: #ef4444; text-align: center; padding: 20px;">Terjadi kendala saat memuat analisis. Silakan coba lagi beberapa saat.</div>`;
        });
}

function closeAggregateModal() {
    document.getElementById('aggregateModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('aggregateModal');
    if (event.target === modal) {
        closeAggregateModal();
    }
}
</script>
@endsection