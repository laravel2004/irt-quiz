@extends('layouts.app')

@section('title', 'Pembahasan: ' . $registration->examSession->name)

@section('content')
<div class="container" style="padding: 40px 20px; max-width: 1000px; margin: 0 auto;">
    <div style="margin-bottom: 32px; display: flex; justify-content: space-between; align-items: center;">
        <a href="{{ route('participant.session.show', $registration->exam_session_id) }}" style="color: #475569; text-decoration: none; display: flex; align-items: center; gap: 8px; font-size: 0.9rem;">
            <i class="fas fa-arrow-left"></i> Kembali ke Detail Sesi
        </a>
        <div style="text-align: right;">
            <h4 style="font-family: 'Outfit', sans-serif; margin-bottom: 4px;">{{ $registration->examSession->name }}</h4>
            <span class="badge" style="background: rgba(234, 179, 8, 0.1); color: #eab308;">Fitur Premium: Laporan & Pembahasan</span>
        </div>
    </div>

    @php
        $totalQuestions = $registration->questions->count();
        $totalAnswered = $registration->userAnswers->count();
        $totalCorrect = $registration->userAnswers->where('is_correct', true)->count();
        $totalIncorrect = $totalAnswered - $totalCorrect;
        $totalBlank = $totalQuestions - $totalAnswered;
        
        // Calculate ratio scaled raw score
        $rawScore = 0;
        foreach ($registration->examSession->sessionCategories as $sessionCategory) {
            $catId = $sessionCategory->category_id;
            $catQuestions = $registration->questions->where('category_id', $catId);
            $maxPossiblePoints = $catQuestions->sum('score_correct');
            
            $participantPoints = $registration->userAnswers->filter(function($ans) use ($catQuestions) {
                return $catQuestions->pluck('id')->contains($ans->question_bank_id) && $ans->is_correct;
            })->sum('score');
            
            if ($maxPossiblePoints > 0) {
                $scaledScore = ($participantPoints / $maxPossiblePoints) * $sessionCategory->max_score_raw;
                $rawScore += max(0, min($scaledScore, $sessionCategory->max_score_raw));
            }
        }
        $rawScore = number_format($rawScore, 2);
    @endphp

    <!-- SCORE OVERVIEW -->
    <div class="glass animate-fade-in" style="padding: 32px; border-radius: 24px; text-align: center; margin-bottom: 32px; display: flex; flex-direction: column; align-items: center;">
        <div style="font-size: 0.85rem; color: #475569; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px;">Total Skor Mentah (Raw Score)</div>
        <div style="font-size: 3.5rem; font-weight: 800; font-family: 'Outfit', sans-serif; color: #0f172a; line-height: 1;">
            {{ $rawScore }}
        </div>
        
        <div style="display: flex; gap: 24px; margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--glass-border); width: 100%; justify-content: center;">
            <div style="text-align: center;">
                <div style="font-size: 0.75rem; color: #10b981; margin-bottom: 4px;">BENAR</div>
                <div style="font-size: 1.25rem; font-weight: 700; color: #10b981;">{{ $totalCorrect }}</div>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 0.75rem; color: #ef4444; margin-bottom: 4px;">SALAH</div>
                <div style="font-size: 1.25rem; font-weight: 700; color: #ef4444;">{{ $totalIncorrect }}</div>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 0.75rem; color: #475569; margin-bottom: 4px;">KOSONG</div>
                <div style="font-size: 1.25rem; font-weight: 700; color: #0f172a;">{{ $totalBlank }}</div>
            </div>
        </div>
    </div>

    <!-- AI ANALYSIS -->
    <div class="glass animate-fade-in" style="padding: 32px; border-radius: 24px; margin-bottom: 32px; text-align: left;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px;">
            <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #8b5cf6, #d946ef); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #0f172a;">
                <i class="fas fa-robot"></i>
            </div>
            <h3 style="font-family: 'Outfit', sans-serif; margin: 0; color: #0f172a;">AI Smart Analysis</h3>
        </div>

        @if($registration->privilege === 'premium')
            @if($registration->result && $registration->result->ai_analysis)
                @php 
                    $aiData = $registration->result->ai_analysis;
                    $analysis = is_string($aiData) ? json_decode($aiData, true) : $aiData; 
                @endphp
                <div style="display: grid; grid-template-columns: 1fr; gap: 16px;">
                    <div style="padding: 20px; border-radius: 12px; border-left: 4px solid #10b981; background: #ffffff;">
                        <h4 style="color: #10b981; margin-bottom: 8px; font-size: 0.9rem; text-transform: uppercase;"><i class="fas fa-plus-circle"></i> Kelebihan</h4>
                        <p style="font-size: 0.95rem; color: #475569; line-height: 1.5; margin: 0;">{{ $analysis['kelebihan'] ?? '' }}</p>
                    </div>
                    <div style="padding: 20px; border-radius: 12px; border-left: 4px solid #ef4444; background: #ffffff;">
                        <h4 style="color: #ef4444; margin-bottom: 8px; font-size: 0.9rem; text-transform: uppercase;"><i class="fas fa-minus-circle"></i> Kekurangan</h4>
                        <p style="font-size: 0.95rem; color: #475569; line-height: 1.5; margin: 0;">{{ $analysis['kekurangan'] ?? '' }}</p>
                    </div>
                    <div style="padding: 20px; border-radius: 12px; border-left: 4px solid var(--accent); background: rgba(var(--accent-rgb), 0.05);">
                        <h4 style="color: var(--accent); margin-bottom: 8px; font-size: 0.9rem; text-transform: uppercase;"><i class="fas fa-lightbulb"></i> Rekomendasi</h4>
                        <p style="font-size: 0.95rem; color: #475569; line-height: 1.5; margin: 0;">{{ $analysis['rekomendasi'] ?? '' }}</p>
                    </div>
                </div>
            @else
                <div id="aiPlaceholder" style="text-align: center; padding: 40px; background: #ffffff; border: 1px dashed var(--glass-border); border-radius: 20px;">
                    <p style="color: #475569; margin-bottom: 24px;">Dapatkan analisis mendalam dari AI mengenai performa Anda secara otomatis.</p>
                    <button onclick="generateAIAnalysis()" id="aiBtn" class="btn-primary" style="background: linear-gradient(135deg, #8b5cf6, #d946ef); border: none;">
                        <i class="fas fa-sparkles"></i> Generate Analisis AI
                    </button>
                </div>
                <div id="aiLoading" style="display: none; text-align: center; padding: 40px;">
                    <div style="width: 50px; height: 50px; border: 3px solid rgba(139, 92, 246, 0.3); border-top-color: #8b5cf6; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
                    <p style="color: #475569;">AI sedang menganalisis hasil Anda, mohon tunggu sebentar...</p>
                </div>
            @endif
        @else
            <div style="text-align: center; padding: 40px; background: #ffffff; border: 1px dashed rgba(234, 179, 8, 0.3); border-radius: 20px;">
                <div style="width: 60px; height: 60px; background: rgba(234, 179, 8, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                    <i class="fas fa-lock" style="font-size: 1.5rem; color: #eab308;"></i>
                </div>
                <h4 style="color: #0f172a; margin-bottom: 8px;">Fitur Eksklusif Premium</h4>
                <p style="color: #475569; margin-bottom: 0;">Analisis AI hanya tersedia untuk peserta dengan akses Premium. Silakan hubungi Admin untuk upgrade akun Anda.</p>
            </div>
        @endif
    </div>

    <!-- STATISTIK JAWABAN (CHART) -->
    <div class="chart-section glass" style="margin-top: 40px; margin-bottom: 40px; padding: 24px; border-radius: 16px;">
        <h3 style="font-family: 'Outfit', sans-serif; margin-bottom: 24px; text-align: center;">Statistik Jawaban per Pelajaran</h3>
        
        <!-- Wadah Grafik -->
        <div class="chart-container-responsive">
            <div class="chart-wrapper">
                <canvas id="mapelChart"></canvas>
            </div>
            <div id="subChartsContainer" style="display: grid; grid-template-columns: 1fr; gap: 32px; width: 100%;">
                <!-- Dynamic Sub Charts -->
            </div>
        </div>
    </div>

    <!-- PEMBAHASAN LIST -->
    @php
        $questionsByCategory = $registration->questions->groupBy('category_id');
    @endphp

    <div id="pembahasan-hub">
        <h3 style="font-family: 'Outfit', sans-serif; color: #0f172a; margin-bottom: 24px; border-bottom: 1px solid var(--glass-border); padding-bottom: 12px;">Pilih Rincian Pembahasan per Mata Pelajaran</h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; margin-bottom: 32px;">
            @foreach($questionsByCategory as $categoryId => $categoryQuestions)
                @php 
                    $categoryName = $categoryQuestions->first()->category->name; 
                    $catTotal = $categoryQuestions->count();
                    
                    $catCorrect = 0;
                    $catScore = 0;
                    foreach ($categoryQuestions as $q) {
                        $ans = $registration->userAnswers->where('question_bank_id', $q->id)->first();
                        if ($ans && $ans->is_correct) {
                            $catCorrect++;
                            $catScore += $ans->score;
                        }
                    }
                    $catAnswered = 0;
                    foreach ($categoryQuestions as $q) {
                        if ($registration->userAnswers->where('question_bank_id', $q->id)->first()) {
                            $catAnswered++;
                        }
                    }
                    $catIncorrect = $catAnswered - $catCorrect;
                    $catBlank = $catTotal - $catAnswered;
                @endphp
                <a href="{{ route('participant.review.category', [$registration->id, $categoryId]) }}" class="btn-primary" style="background: #f8fafc; color: #0f172a; border: 1px solid #e2e8f0; padding: 20px; display: flex; flex-direction: column; gap: 12px; cursor: pointer; transition: all 0.3s ease; text-decoration: none; border-radius: 16px; height: auto;">
                    <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                        <span style="font-family: 'Outfit', sans-serif; font-size: 1.1rem; font-weight: 600;">{{ $categoryName }}</span>
                        <span style="font-size: 1.2rem; font-weight: 700; color: var(--accent); font-family: 'Outfit', sans-serif;">{{ $catScore }}</span>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; width: 100%;">
                        <div style="background: rgba(16, 185, 129, 0.1); padding: 8px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 0.65rem; color: #10b981; margin-bottom: 4px;">BENAR</div>
                            <div style="font-size: 1rem; font-weight: 600; color: #10b981;">{{ $catCorrect }}</div>
                        </div>
                        <div style="background: rgba(239, 68, 68, 0.1); padding: 8px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 0.65rem; color: #ef4444; margin-bottom: 4px;">SALAH</div>
                            <div style="font-size: 1rem; font-weight: 600; color: #ef4444;">{{ $catIncorrect }}</div>
                        </div>
                        <div style="background: #eff6ff; padding: 8px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 0.65rem; color: #475569; margin-bottom: 4px;">KOSONG</div>
                            <div style="font-size: 1rem; font-weight: 600; color: #0f172a;">{{ $catBlank }}</div>
                        </div>
                    </div>
                    
                    <div style="width: 100%; text-align: center; margin-top: 8px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.05);">
                        <span style="font-size: 0.8rem; color: #475569;">Lihat Pembahasan <i class="fas fa-arrow-right" style="margin-left: 4px;"></i></span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: data.message || 'Gagal generate analisis AI',
                    background: '#ffffff',
                    color: '#0f172a',
                    confirmButtonColor: '#3b82f6'
                });
                placeholder.style.display = 'block';
                loading.style.display = 'none';
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire({
                icon: 'error',
                title: 'Error sistem',
                text: 'Terjadi kesalahan sistem, silakan coba lagi.',
                background: '#ffffff',
                color: '#0f172a',
                confirmButtonColor: '#3b82f6'
            });
            placeholder.style.display = 'block';
            loading.style.display = 'none';
        });
    }

    document.addEventListener("DOMContentLoaded", function() {
        Chart.defaults.color = '#94a3b8';
        Chart.defaults.font.family = "'Inter', sans-serif";

        const mapelData = @json($chartDataMapel);

        const ctxMapel = document.getElementById('mapelChart').getContext('2d');
        const mapelWrapper = document.getElementById('mapelChart').parentElement;
        mapelWrapper.style.height = Math.max(mapelData.labels.length * 60 + 100, 300) + 'px';

        new Chart(ctxMapel, {
            type: 'bar',
            data: {
                labels: mapelData.labels,
                datasets: [
                    { label: 'Benar', data: mapelData.benar, backgroundColor: 'rgba(16, 185, 129, 0.8)' },
                    { label: 'Salah', data: mapelData.salah, backgroundColor: 'rgba(239, 68, 68, 0.8)' }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true, 
                maintainAspectRatio: false,
                plugins: { 
                    title: { display: true, text: 'Grafik Mata Pelajaran (Utama)', color: '#0f172a', font: {size: 16, family: "'Outfit', sans-serif"} },
                    legend: { labels: { color: '#0f172a' } }
                },
                scales: {
                    y: { grid: { color: 'rgba(255, 255, 255, 0.1)' } },
                    x: { ticks: { precision: 0 }, grid: { color: 'rgba(255, 255, 255, 0.1)' } }
                }
            }
        });

        // 2. Inisialisasi Grafik Sub Mapel (Dinamis per Mata Pelajaran)
        const subContainer = document.getElementById('subChartsContainer');
        
        Object.keys(mapelData.details).forEach((mapelName, index) => {
            const subMapels = mapelData.details[mapelName].subMapel;
            const subLabels = Object.keys(subMapels);
            if (subLabels.length === 0) return;
            
            const subBenar = subLabels.map(l => subMapels[l].benar);
            const subSalah = subLabels.map(l => subMapels[l].salah);
            
            const wrapper = document.createElement('div');
            wrapper.className = 'chart-wrapper';
            wrapper.style.height = Math.max(subLabels.length * 60 + 100, 300) + 'px';
            
            const canvas = document.createElement('canvas');
            canvas.id = 'subChart_' + index;
            wrapper.appendChild(canvas);
            subContainer.appendChild(wrapper);
            
            new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: subLabels,
                    datasets: [
                        { label: 'Benar', data: subBenar, backgroundColor: 'rgba(5, 150, 105, 0.9)' },
                        { label: 'Salah', data: subSalah, backgroundColor: 'rgba(220, 38, 38, 0.9)' }
                    ]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true, 
                    maintainAspectRatio: false,
                    plugins: { 
                        title: { display: true, text: 'Rincian Sub Bab: ' + mapelName, color: '#0f172a', font: {size: 16, family: "'Outfit', sans-serif"} },
                        legend: { labels: { color: '#0f172a' } }
                    },
                    scales: {
                        y: { grid: { color: 'rgba(255, 255, 255, 0.1)' } },
                        x: { ticks: { precision: 0 }, grid: { color: 'rgba(255, 255, 255, 0.1)' } }
                    }
                }
            });
        });
    });
</script>

<style>
    @keyframes spin { to { transform: rotate(360deg); } }
    
    .chart-container-responsive {
        display: grid;
        grid-template-columns: 1fr;
        gap: 32px;
    }
    .chart-wrapper {
        position: relative;
        height: 400px;
        width: 100%;
    }

    @media (max-width: 768px) {
        .chart-wrapper {
            height: 250px;
        }
    }
</style>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.renderMathInElement) {
            document.querySelectorAll('.review-question-content, .review-option-content, .review-explanation').forEach(function(el) {
                renderMathInElement(el, {
                    delimiters: [
                        {left: '$$', right: '$$', display: true},
                        {left: '$', right: '$', display: false},
                        {left: '\\(', right: '\\)', display: false},
                        {left: '\\[', right: '\\]', display: true}
                    ],
                    throwOnError: false
                });
            });
        }
    });
</script>
@endpush
