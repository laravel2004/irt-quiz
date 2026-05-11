@extends('layouts.admin')

@section('title', 'Preview Soal: ' . $session->name)
@section('header_title', 'Preview Soal Sesi')

@section('content')
<div style="margin-bottom: 24px;">
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
        <div style="text-align: right;">
            <div style="background: rgba(var(--accent-rgb), 0.1); color: var(--accent); padding: 8px 16px; border-radius: 12px; font-weight: 600; font-size: 0.9rem;">
                <i class="fas fa-check-circle"></i> Soal Terkunci
            </div>
        </div>
    </div>
</div>

<div style="display: flex; flex-direction: column; gap: 24px;">
    @foreach($session->questions as $index => $question)
    <div class="glass animate-fade-in" style="padding: 24px; border-left: 4px solid var(--accent);">
        <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
            <div style="display: flex; gap: 12px; align-items: center;">
                <span style="width: 32px; height: 32px; background: var(--accent); color: white; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-family: 'Outfit', sans-serif;">
                    {{ $index + 1 }}
                </span>
                <span class="badge" style="background: rgba(255,255,255,0.05); color: var(--text-secondary);">
                    {{ data_get($question->category, 'name', 'Tanpa Kategori') }}
                </span>
                <span class="badge" style="background: rgba(255,255,255,0.05); color: var(--text-secondary); text-transform: capitalize;">
                    {{ str_replace('_', ' ', $question->type) }}
                </span>
            </div>
            <div style="display: flex; gap: 16px; align-items: center; color: var(--text-secondary); font-size: 0.8rem;">
                <div>Kesulitan: <span style="color: var(--accent); font-weight: 600;">{{ number_format($question->difficulty, 2) }}</span></div>
                <div style="width: 1px; height: 12px; background: rgba(255,255,255,0.1);"></div>
                <div>Skor: <span style="color: #10b981; font-weight: 600;">+{{ $question->score_correct }}</span> / <span style="color: #ef4444; font-weight: 600;">{{ $question->score_incorrect }}</span></div>
            </div>
        </div>

        <div style="font-size: 1.1rem; line-height: 1.6; margin-bottom: 24px; color: white;">
            {!! nl2br(e($question->question_text)) !!}
        </div>

        @if($question->question_image)
        <div style="margin-bottom: 24px;">
            <img src="{{ asset('storage/' . $question->question_image) }}" alt="Soal Image" style="max-width: 100%; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1);">
        </div>
        @endif

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
            @php
                $options = (array) $question->options;
                $correct = (array) $question->correct_answer;
            @endphp

            @foreach($options as $optIndex => $option)
                @php
                    $isCorrect = in_array($optIndex, $correct);
                @endphp
                <div style="padding: 12px 16px; border-radius: 10px; background: {{ $isCorrect ? 'rgba(16, 185, 129, 0.1)' : 'rgba(255,255,255,0.03)' }}; border: 1px solid {{ $isCorrect ? '#10b981' : 'rgba(255,255,255,0.05)' }}; display: flex; align-items: flex-start; gap: 12px;">
                    <div style="width: 24px; height: 24px; border-radius: 6px; background: {{ $isCorrect ? '#10b981' : 'rgba(255,255,255,0.1)' }}; color: white; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; flex-shrink: 0;">
                        {{ chr(65 + $optIndex) }}
                    </div>
                    <div style="font-size: 0.95rem; color: {{ $isCorrect ? '#10b981' : 'var(--text-secondary)' }};">
                        {{ $option }}
                        @if($isCorrect)
                        <i class="fas fa-check-circle" style="margin-left: 8px;"></i>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endforeach
</div>
@endsection
