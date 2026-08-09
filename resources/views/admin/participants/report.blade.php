@extends('layouts.admin')

@section('title', 'Raport: ' . $reportCard->user->name)
@section('header_title', 'Raport Peserta')

@section('content')
<div class="glass animate-fade-in" style="padding: 32px; margin-bottom: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
        <div>
            <h3 style="font-family: 'Outfit', sans-serif; margin-bottom: 4px;">
                Raport: {{ $reportCard->user->name }}
            </h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">
                Digenerate pada {{ $reportCard->updated_at->format('d M Y, H:i') }} WIB
            </p>
        </div>
        <div style="display: flex; gap: 12px;">
            <a href="{{ route('admin.report-cards.print', $reportCard->id) }}" target="_blank" class="btn-primary" style="background: #ffffff; border: 1px solid var(--glass-border); color: var(--primary); text-decoration: none;">
                <i class="fas fa-eye"></i> Preview
            </a>
            <a href="{{ route('admin.report-cards.print', $reportCard->id) }}?print=1" target="_blank" class="btn-primary" style="background: var(--primary); color: white; text-decoration: none; border: none;">
                <i class="fas fa-file-pdf"></i> Cetak PDF
            </a>
            <a href="{{ route('admin.participants.index') }}" class="btn-primary" style="background: transparent; border: 1px solid var(--glass-border); color: var(--text-secondary); text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    @php $reportData = $reportCard->report_data; @endphp

    @if(!$reportData || count($reportData) === 0)
        <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
            Tidak ada data jawaban untuk ditampilkan.
        </div>
    @else
        @foreach($reportData as $category)
        <div style="margin-bottom: 32px; border: 1px solid var(--glass-border); border-radius: 16px; overflow: hidden;">
            <!-- Header Mata Pelajaran -->
            <div style="background: #f1f5f9; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
                <h4 style="font-family: 'Outfit', sans-serif; margin: 0;">
                    {{ $category['category_name'] }}
                </h4>
                <div style="display: flex; gap: 16px; font-size: 0.85rem;">
                    <span><strong>Total:</strong> {{ $category['total_soal'] }} soal</span>
                    <span style="color: #10b981;"><strong>Benar:</strong> {{ $category['total_benar'] }}</span>
                    <span style="color: #ef4444;"><strong>Salah:</strong> {{ $category['total_salah'] }}</span>
                </div>
            </div>

            <!-- Detail Sub Mata Pelajaran -->
            <div style="padding: 0;">
                <table class="data-table" style="margin: 0; border: none;">
                    <thead>
                        <tr>
                            <th>Sub Mata Pelajaran</th>
                            <th style="text-align: center; width: 100px;">Total Soal</th>
                            <th style="text-align: center; width: 100px;">Benar</th>
                            <th style="text-align: center; width: 100px;">Salah</th>
                            <th style="text-align: center; width: 120px;">Persentase</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($category['sub_categories'] as $sub)
                        @php
                            $persen = $sub['total_soal'] > 0 ? round(($sub['total_benar'] / $sub['total_soal']) * 100) : 0;
                            $barColor = $persen >= 70 ? '#10b981' : ($persen >= 40 ? '#eab308' : '#ef4444');
                        @endphp
                        <tr>
                            <td>{{ $sub['sub_category_name'] }}</td>
                            <td style="text-align: center;">{{ $sub['total_soal'] }}</td>
                            <td style="text-align: center; color: #10b981; font-weight: 600;">{{ $sub['total_benar'] }}</td>
                            <td style="text-align: center; color: #ef4444; font-weight: 600;">{{ $sub['total_salah'] }}</td>
                            <td style="text-align: center;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="flex: 1; height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden;">
                                        <div style="width: {{ $persen }}%; height: 100%; background: {{ $barColor }}; border-radius: 4px;"></div>
                                    </div>
                                    <span style="font-weight: 600; font-size: 0.85rem; color: {{ $barColor }};">{{ $persen }}%</span>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endforeach
    @endif
</div>

{{-- ============================================================ --}}
{{-- SECTION BARU: Analisis AI (di bawah raport) --}}
{{-- ============================================================ --}}
<div class="glass animate-fade-in" style="padding: 32px; margin-bottom: 24px;" id="aiAnalysisSection">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h3 style="font-family: 'Outfit', sans-serif; margin-bottom: 4px;">
                <i class="fas fa-robot" style="color: #8b5cf6;"></i> Analisis AI
            </h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">
                Analisis otomatis berdasarkan data raport di atas menggunakan AI.
            </p>
        </div>

        {{-- Tombol Generate hanya muncul jika belum ada analisis --}}
        @if($reportCard->ai_analysis_status !== 'completed')
            <button class="btn-primary" id="btnGenerateAi" onclick="generateAiAnalysis()" style="background: linear-gradient(135deg, #8b5cf6, #6366f1);">
                <i class="fas fa-magic"></i> Generate Analisis AI
            </button>
        @else
            <button class="btn-primary" id="btnRegenerateAi" onclick="generateAiAnalysis(true)" style="background: transparent; border: 1px solid var(--glass-border); color: var(--text-secondary);">
                <i class="fas fa-redo"></i> Re-generate
            </button>
        @endif
    </div>

    {{-- State 1: Loading / Processing --}}
    <div id="aiLoading" style="display: none; text-align: center; padding: 40px;">
        <div style="width: 50px; height: 50px; border: 3px solid rgba(139, 92, 246, 0.3); border-top-color: #8b5cf6; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 16px;"></div>
        <p style="color: var(--text-secondary); font-size: 0.95rem;">Sedang menganalisis raport dengan AI...</p>
        <p style="color: var(--text-secondary); font-size: 0.8rem;">Proses ini membutuhkan waktu 10-30 detik.</p>
    </div>

    {{-- State 2: Belum ada analisis --}}
    <div id="aiEmpty" style="{{ $reportCard->ai_analysis_status === 'completed' ? 'display: none;' : '' }} text-align: center; padding: 40px;">
        <div style="width: 60px; height: 60px; background: rgba(139, 92, 246, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
            <i class="fas fa-robot" style="font-size: 1.5rem; color: #8b5cf6;"></i>
        </div>
        <p style="color: var(--text-secondary);">Belum ada analisis AI. Klik tombol <strong>"Generate Analisis AI"</strong> untuk memulai.</p>
    </div>

    {{-- State 3: Hasil analisis AI --}}
    <div id="aiResult" style="{{ $reportCard->ai_analysis_status !== 'completed' ? 'display: none;' : '' }}">
        @if($reportCard->ai_analysis_status === 'completed' && $reportCard->ai_analysis)
            @php $ai = $reportCard->ai_analysis; @endphp

            {{-- Ringkasan --}}
            @if(!empty($ai['ringkasan']))
            <div style="margin-bottom: 24px; padding: 20px; background: rgba(59, 130, 246, 0.05); border-left: 4px solid #3b82f6; border-radius: 0 12px 12px 0;">
                <h4 style="margin: 0 0 12px 0; color: #3b82f6; font-family: 'Outfit', sans-serif;">
                    <i class="fas fa-chart-line"></i> Ringkasan
                </h4>
                <p style="color: var(--text-primary); line-height: 1.7; margin: 0; white-space: pre-line;">{{ $ai['ringkasan'] }}</p>
            </div>
            @endif

            {{-- Kelebihan --}}
            @if(!empty($ai['kelebihan']))
            <div style="margin-bottom: 24px; padding: 20px; background: rgba(16, 185, 129, 0.05); border-left: 4px solid #10b981; border-radius: 0 12px 12px 0;">
                <h4 style="margin: 0 0 12px 0; color: #10b981; font-family: 'Outfit', sans-serif;">
                    <i class="fas fa-star"></i> Kelebihan
                </h4>
                <p style="color: var(--text-primary); line-height: 1.7; margin: 0; white-space: pre-line;">{{ $ai['kelebihan'] }}</p>
            </div>
            @endif

            {{-- Kekurangan --}}
            @if(!empty($ai['kekurangan']))
            <div style="margin-bottom: 24px; padding: 20px; background: rgba(239, 68, 68, 0.05); border-left: 4px solid #ef4444; border-radius: 0 12px 12px 0;">
                <h4 style="margin: 0 0 12px 0; color: #ef4444; font-family: 'Outfit', sans-serif;">
                    <i class="fas fa-exclamation-triangle"></i> Kekurangan
                </h4>
                <p style="color: var(--text-primary); line-height: 1.7; margin: 0; white-space: pre-line;">{{ $ai['kekurangan'] }}</p>
            </div>
            @endif

            {{-- Rekomendasi --}}
            @if(!empty($ai['rekomendasi']))
            <div style="margin-bottom: 0; padding: 20px; background: rgba(139, 92, 246, 0.05); border-left: 4px solid #8b5cf6; border-radius: 0 12px 12px 0;">
                <h4 style="margin: 0 0 12px 0; color: #8b5cf6; font-family: 'Outfit', sans-serif;">
                    <i class="fas fa-lightbulb"></i> Rekomendasi
                </h4>
                <p style="color: var(--text-primary); line-height: 1.7; margin: 0; white-space: pre-line;">{{ $ai['rekomendasi'] }}</p>
            </div>
            @endif
        @endif
    </div>

    {{-- State 4: Gagal --}}
    <div id="aiFailed" style="display: none; text-align: center; padding: 40px;">
        <div style="width: 60px; height: 60px; background: rgba(239, 68, 68, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
            <i class="fas fa-times" style="font-size: 1.5rem; color: #ef4444;"></i>
        </div>
        <p style="color: #ef4444; font-weight: 600;">Gagal menghasilkan analisis AI.</p>
        <p style="color: var(--text-secondary); font-size: 0.85rem;">Silakan coba lagi dengan menekan tombol "Generate Analisis AI".</p>
    </div>
</div>

<style>
    @keyframes spin { to { transform: rotate(360deg); } }
</style>
@endsection

@push('scripts')
<script>
    const REPORT_CARD_ID = {{ $reportCard->id }};
    let aiPollingInterval = null;

    /**
     * Fungsi utama untuk memulai generate analisis AI.
     * @param {boolean} isRegenerate - true jika user klik "Re-generate"
     */
    function generateAiAnalysis(isRegenerate = false) {
        // Konfirmasi jika re-generate
        if (isRegenerate) {
            if (!confirm('Yakin ingin men-generate ulang analisis AI? Hasil sebelumnya akan ditimpa.')) {
                return;
            }
        }

        // Tampilkan state loading
        showAiState('loading');

        // Kirim POST request ke backend
        fetch(`/admin/report-cards/${REPORT_CARD_ID}/generate-ai-analysis`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    || document.querySelector('input[name="_token"]')?.value,
                'Accept': 'application/json'
            },
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                if (data.data.ai_analysis_status === 'completed' && data.data.ai_analysis) {
                    // Sudah selesai (mungkin dari cache)
                    renderAiResult(data.data.ai_analysis);
                    showAiState('result');
                } else {
                    // Mulai polling
                    startAiPolling();
                }
            } else {
                showToast(data.message || 'Gagal memulai analisis AI.', 'error');
                showAiState('empty');
            }
        })
        .catch(err => {
            console.error('Error generating AI analysis:', err);
            showToast('Terjadi kesalahan sistem.', 'error');
            showAiState('empty');
        });
    }

    /**
     * Polling status analisis AI setiap 3 detik.
     */
    function startAiPolling() {
        // Clear polling sebelumnya jika ada
        if (aiPollingInterval) clearInterval(aiPollingInterval);

        aiPollingInterval = setInterval(() => {
            fetch(`/admin/report-cards/${REPORT_CARD_ID}/ai-analysis-status`, {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                const status = data.data.ai_analysis_status;

                if (status === 'completed') {
                    clearInterval(aiPollingInterval);
                    aiPollingInterval = null;
                    renderAiResult(data.data.ai_analysis);
                    showAiState('result');
                    showToast('Analisis AI berhasil digenerate!');
                } else if (status === 'failed') {
                    clearInterval(aiPollingInterval);
                    aiPollingInterval = null;
                    showAiState('failed');
                    showToast('Gagal menghasilkan analisis AI.', 'error');
                }
                // Jika masih 'processing', polling lanjut
            })
            .catch(() => {
                clearInterval(aiPollingInterval);
                aiPollingInterval = null;
                showAiState('failed');
            });
        }, 3000); // Poll setiap 3 detik
    }

    /**
     * Render hasil analisis AI ke dalam DOM.
     * @param {object} analysis - Object dengan keys: ringkasan, kelebihan, kekurangan, rekomendasi
     */
    function renderAiResult(analysis) {
        if (!analysis) return;

        const sections = [
            { key: 'ringkasan',   icon: 'fa-chart-line',           color: '#3b82f6', bgColor: 'rgba(59, 130, 246, 0.05)',  label: 'Ringkasan' },
            { key: 'kelebihan',   icon: 'fa-star',                 color: '#10b981', bgColor: 'rgba(16, 185, 129, 0.05)',  label: 'Kelebihan' },
            { key: 'kekurangan',  icon: 'fa-exclamation-triangle', color: '#ef4444', bgColor: 'rgba(239, 68, 68, 0.05)',   label: 'Kekurangan' },
            { key: 'rekomendasi', icon: 'fa-lightbulb',            color: '#8b5cf6', bgColor: 'rgba(139, 92, 246, 0.05)',  label: 'Rekomendasi' },
        ];

        let html = '';
        sections.forEach((section, index) => {
            const content = analysis[section.key];
            if (content) {
                const isLast = index === sections.length - 1;
                html += `
                    <div style="margin-bottom: ${isLast ? '0' : '24px'}; padding: 20px; background: ${section.bgColor}; border-left: 4px solid ${section.color}; border-radius: 0 12px 12px 0;">
                        <h4 style="margin: 0 0 12px 0; color: ${section.color}; font-family: 'Outfit', sans-serif;">
                            <i class="fas ${section.icon}"></i> ${section.label}
                        </h4>
                        <p style="color: var(--text-primary); line-height: 1.7; margin: 0; white-space: pre-line;">${escapeHtml(content)}</p>
                    </div>
                `;
            }
        });

        document.getElementById('aiResult').innerHTML = html;

        // Update tombol: ganti Generate jadi Re-generate
        const btnGenerate = document.getElementById('btnGenerateAi');
        if (btnGenerate) {
            btnGenerate.outerHTML = `
                <button class="btn-primary" id="btnRegenerateAi" onclick="generateAiAnalysis(true)" style="background: transparent; border: 1px solid var(--glass-border); color: var(--text-secondary);">
                    <i class="fas fa-redo"></i> Re-generate
                </button>
            `;
        }
    }

    /**
     * Tampilkan state tertentu, sembunyikan yang lain.
     * @param {string} state - 'loading' | 'empty' | 'result' | 'failed'
     */
    function showAiState(state) {
        document.getElementById('aiLoading').style.display = state === 'loading' ? 'block' : 'none';
        document.getElementById('aiEmpty').style.display   = state === 'empty'   ? 'block' : 'none';
        document.getElementById('aiResult').style.display   = state === 'result'  ? 'block' : 'none';
        document.getElementById('aiFailed').style.display   = state === 'failed'  ? 'block' : 'none';
    }

    /**
     * Escape HTML untuk mencegah XSS.
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }
</script>
@endpush
