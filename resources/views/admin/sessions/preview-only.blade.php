@extends('layouts.admin')

@section('title', 'Preview Soal: ' . $session->name)
@section('header_title', 'Preview Soal Sesi')

@section('content')

<style>
    .session-preview-content,
    .session-preview-option-html {
        min-width: 0;
        max-width: 100%;
        overflow-wrap: anywhere;
        word-break: break-word;
    }
    .session-preview-content img,
    .session-preview-option-html img {
        max-width: 100% !important;
        height: auto !important;
        display: block;
        object-fit: contain;
        border-radius: 10px;
        margin: 8px 0;
    }
    .session-preview-option {
        min-width: 0;
        overflow: hidden;
        background: #ffffff !important;
        border-color: var(--glass-border) !important;
    }
    .session-preview-option.correct {
        background: rgba(16, 185, 129, 0.08) !important;
        border-color: #10b981 !important;
    }
    .session-preview-option-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 12px;
    }
    .session-preview-bs-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 80px 80px;
        gap: 12px;
        align-items: start;
    }
    @media (max-width: 768px) {
        .session-preview-bs-row {
            grid-template-columns: 1fr;
        }
    }
    
    @media print {
        body {
            background: #fff !important;
        }
        .sidebar, .mobile-header, header, .no-print {
            display: none !important;
        }
        .main-content {
            margin: 0 !important;
            padding: 0 !important;
            max-width: 100% !important;
        }
        .glass {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            margin-bottom: 16px !important;
        }
        .question-container {
            break-inside: avoid;
            page-break-inside: avoid;
            margin-bottom: 24px !important;
            border-left: none !important;
            border-bottom: 1px solid #e2e8f0 !important;
            padding-bottom: 24px !important;
        }
        .session-preview-option {
            border: 1px solid #e2e8f0 !important;
            background: #fff !important;
            break-inside: avoid;
        }
        .session-preview-option-grid > div {
            border: 1px solid #e2e8f0 !important;
            background: #fff !important;
            break-inside: avoid;
        }
    }
</style>

<div class="no-print" style="margin-bottom: 24px;">
    <a href="{{ route('admin.sessions.show', $session->id) }}" style="color: var(--text-secondary); text-decoration: none; display: flex; align-items: center; gap: 8px; font-size: 0.9rem;">
        <i class="fas fa-arrow-left"></i> Kembali ke Detail Sesi
    </a>
</div>

<div class="glass animate-fade-in" style="padding: 32px; margin-bottom: 32px;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="font-family: 'Outfit', sans-serif; margin-bottom: 8px;">Daftar Soal Terpilih</h2>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Berikut adalah {{ $session->questions->count() }} butir soal yang telah di-generate untuk sesi <strong>{{ $session->name }}</strong>.</p>
        </div>
        <div style="text-align: right; display: flex; gap: 12px; align-items: center; justify-content: flex-end;" class="no-print">
            <button onclick="window.print()" style="background: #ffffff; color: var(--text-primary); border: 1px solid var(--glass-border); padding: 8px 16px; border-radius: 12px; font-weight: 600; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s;">
                <i class="fas fa-print"></i> Cetak PDF
            </button>
            <div style="background: rgba(var(--accent-rgb), 0.1); color: var(--accent); padding: 8px 16px; border-radius: 12px; font-weight: 600; font-size: 0.9rem;">
                <i class="fas fa-check-circle"></i> Soal Terkunci
            </div>
        </div>
    </div>
</div>

<div style="display: flex; flex-direction: column; gap: 24px;">
    @foreach($session->questions as $index => $question)
    <div class="glass animate-fade-in question-container" style="padding: 24px; border-left: 4px solid var(--accent);">
        <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
            <div style="display: flex; gap: 12px; align-items: center;">
                <span style="width: 32px; height: 32px; background: var(--accent); color: #0f172a; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-family: 'Outfit', sans-serif;">
                    {{ $index + 1 }}
                </span>
                <span class="badge" style="background: #eff6ff; color: var(--text-secondary);">
                    {{ data_get($question->category, 'name', 'Tanpa Kategori') }}
                </span>
                <span class="badge" style="background: #eff6ff; color: var(--text-secondary); text-transform: capitalize;">
                    {{ str_replace('_', ' ', $question->type) }}
                </span>
            </div>
            <div style="display: flex; gap: 16px; align-items: center; color: var(--text-secondary); font-size: 0.8rem;">
                @php $displayDifficulty = $question->pivot->difficulty ?? $question->difficulty; @endphp
                <div>Kesulitan: <span style="color: var(--accent); font-weight: 600;">{{ number_format((float)$displayDifficulty, 2) }}</span></div>
                <div style="width: 1px; height: 12px; background: #e2e8f0;"></div>
                <div>Skor: <span style="color: #10b981; font-weight: 600;">+{{ $question->score_correct }}</span> / <span style="color: #ef4444; font-weight: 600;">{{ $question->score_incorrect }}</span></div>
            </div>
        </div>

        <div class="session-preview-content" style="font-size: 1.1rem; line-height: 1.6; margin-bottom: 24px; color: #0f172a; overflow-x: auto;">
            {!! $question->question_text !!}
        </div>

        @if($question->question_image)
        <div style="margin-bottom: 24px;">
            <img src="{{ asset('storage/' . $question->question_image) }}" alt="Soal Image" style="max-width: 100%; border-radius: 12px; border: 1px solid var(--glass-border);">
        </div>
        @endif

        @php
    $options = (array) $question->options;
    $correct = (array) $question->correct_answer;
    $correctNormalized = array_map(function ($value) {
        return strtolower(trim((string) $value));
    }, $correct);
    $correctIndexSet = array_flip(array_map('strval', $correct));
    $correctLabelSet = array_flip(array_map(function ($value) {
        return strtoupper(trim((string) $value));
    }, $correct));
@endphp

        @if($question->type === 'multiple_benar_salah')
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <div class="session-preview-bs-row" style="padding: 0 16px; font-weight: 600; color: var(--text-secondary); font-size: 0.85rem;">
                    <div>PERNYATAAN</div>
                    <div style="text-align: center;">BENAR</div>
                    <div style="text-align: center;">SALAH</div>
                </div>
                @foreach($options as $optIndex => $option)
                    <div class="session-preview-option session-preview-bs-row" style="padding: 16px; border-radius: 10px; background: #ffffff; border: 1px solid var(--glass-border);">
                        <div class="session-preview-option-html" style="font-size: 0.95rem; overflow-x: auto; color: #0f172a;">
                            {!! $option !!}
                        </div>
                        <div style="text-align: center;">
                            <span style="display: inline-block; width: 20px; height: 20px; border-radius: 50%; border: 2px solid #cbd5e1;"></span>
                        </div>
                        <div style="text-align: center;">
                            <span style="display: inline-block; width: 20px; height: 20px; border-radius: 50%; border: 2px solid #cbd5e1;"></span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="session-preview-option-grid">
                @foreach($options as $optIndex => $option)
                    <div style="padding: 12px 16px; border-radius: 10px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); display: flex; align-items: flex-start; gap: 12px;">
                        <div style="width: 24px; height: 24px; border-radius: 6px; background: rgba(255,255,255,0.1); color: #0f172a; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; flex-shrink: 0;">
                            @if($question->type === 'multiple_choice')
                                <i class="fas fa-square" style="font-size: 0.85rem;"></i>
                            @else
                                {{ chr(65 + $optIndex) }}
                            @endif
                        </div>
                        <div class="session-preview-option-html" style="font-size: 0.95rem; color: var(--text-secondary); overflow-x: auto;">
                            {!! $option !!}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
        
        <!-- Pembahasan dihapus pada view Preview Soal murni -->

    </div>
    @endforeach
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof renderMathInElement === 'function') {
            renderMathInElement(document.body, {
                delimiters: [
                    {left: '\\(', right: '\\)', display: false},
                    {left: '\\[', right: '\\]', display: true},
                    {left: '$$', right: '$$', display: true},
                    {left: '$', right: '$', display: false}
                ],
                throwOnError: false
            });
        }
    });
</script>
@endpush
