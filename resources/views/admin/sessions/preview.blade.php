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
                @php $displayDifficulty = $question->pivot->difficulty ?? $question->difficulty; @endphp
                <div>Kesulitan: <span style="color: var(--accent); font-weight: 600;">{{ number_format((float)$displayDifficulty, 2) }}</span></div>
                <div style="width: 1px; height: 12px; background: rgba(255,255,255,0.1);"></div>
                <div>Skor: <span style="color: #10b981; font-weight: 600;">+{{ $question->score_correct }}</span> / <span style="color: #ef4444; font-weight: 600;">{{ $question->score_incorrect }}</span></div>
            </div>
        </div>

        <div style="font-size: 1.1rem; line-height: 1.6; margin-bottom: 24px; color: white; overflow-x: auto;">
            {!! $question->question_text !!}
        </div>

        @if($question->question_image)
        <div style="margin-bottom: 24px;">
            <img src="{{ asset('storage/' . $question->question_image) }}" alt="Soal Image" style="max-width: 100%; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1);">
        </div>
        @endif

        @php
            $options = (array) $question->options;
            $correct = (array) $question->correct_answer;
        @endphp

        @if($question->type === 'multiple_benar_salah')
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <div style="display: grid; grid-template-columns: 1fr 80px 80px; gap: 12px; padding: 0 16px; font-weight: 600; color: var(--text-secondary); font-size: 0.85rem;">
                    <div>PERNYATAAN</div>
                    <div style="text-align: center;">BENAR</div>
                    <div style="text-align: center;">SALAH</div>
                </div>
                @foreach($options as $optIndex => $option)
                    @php
                        $isBenar = in_array((string)$optIndex, $correct) || in_array($optIndex, $correct);
                    @endphp
                    <div style="display: grid; grid-template-columns: 1fr 80px 80px; gap: 12px; padding: 16px; border-radius: 10px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); align-items: center;">
                        <div style="font-size: 0.95rem; overflow-x: auto; color: white;">
                            {!! $option !!}
                        </div>
                        <div style="text-align: center;">
                            @if($isBenar)
                                <span style="background: rgba(16, 185, 129, 0.2); color: #10b981; padding: 6px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: 700; display: inline-block;"><i class="fas fa-check"></i></span>
                            @else
                                <span style="color: rgba(255,255,255,0.1); display: inline-block;"><i class="fas fa-minus"></i></span>
                            @endif
                        </div>
                        <div style="text-align: center;">
                            @if(!$isBenar)
                                <span style="background: rgba(16, 185, 129, 0.2); color: #10b981; padding: 6px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: 700; display: inline-block;"><i class="fas fa-check"></i></span>
                            @else
                                <span style="color: rgba(255,255,255,0.1); display: inline-block;"><i class="fas fa-minus"></i></span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
                @foreach($options as $optIndex => $option)
                    @php
                        $isCorrect = in_array((string)$optIndex, $correct) || in_array($optIndex, $correct);
                    @endphp
                    <div style="padding: 12px 16px; border-radius: 10px; background: {{ $isCorrect ? 'rgba(16, 185, 129, 0.1)' : 'rgba(255,255,255,0.03)' }}; border: 1px solid {{ $isCorrect ? '#10b981' : 'rgba(255,255,255,0.05)' }}; display: flex; align-items: flex-start; gap: 12px;">
                        <div style="width: 24px; height: 24px; border-radius: 6px; background: {{ $isCorrect ? '#10b981' : 'rgba(255,255,255,0.1)' }}; color: white; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; flex-shrink: 0;">
                            @if($question->type === 'multiple_choice')
                                <i class="fas {{ $isCorrect ? 'fa-check-square' : 'fa-square' }}" style="font-size: 0.85rem;"></i>
                            @else
                                {{ chr(65 + $optIndex) }}
                            @endif
                        </div>
                        <div style="font-size: 0.95rem; color: {{ $isCorrect ? '#10b981' : 'var(--text-secondary)' }}; overflow-x: auto;">
                            {!! $option !!}
                            @if($isCorrect && $question->type !== 'multiple_choice')
                            <i class="fas fa-check-circle" style="margin-left: 8px;"></i>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    @endforeach
</div>
@endsection

@push('scripts')
<script>
    window.MathJax = {
        tex: {
            inlineMath: [['\\(', '\\)']],
            displayMath: [['\\[', '\\]']],
            packages: {'[+]': ['mhchem']}
        },
        loader: {load: ['[tex]/mhchem']},
        startup: {
            pageReady: () => {
                return MathJax.startup.defaultPageReady();
            }
        }
    };
</script>
<script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
@endpush
