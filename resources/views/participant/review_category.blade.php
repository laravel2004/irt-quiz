@extends('layouts.app')

@section('title', 'Pembahasan: ' . $category->name)

@section('content')
<div class="container" style="padding: 40px 20px; max-width: 1000px; margin: 0 auto;">
    <div style="margin-bottom: 32px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <a href="{{ route('participant.review', $registration->id) }}" class="btn-primary" style="background: #dbeafe; color: #0f172a; border: none; padding: 10px 20px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar Laporan
        </a>
        <div style="text-align: right;">
            <h4 style="font-family: 'Outfit', sans-serif; margin-bottom: 4px; color: #0f172a;">{{ $registration->examSession->name }}</h4>
            <span class="badge" style="background: rgba(var(--accent-rgb), 0.1); color: var(--accent);">Pembahasan: {{ $category->name }}</span>
        </div>
    </div>

    @foreach($registration->questions as $index => $question)
    @php 
        $participantAnswer = $answers[$question->id] ?? null;
        $isCorrect = false;
        
        $options = is_string($question->options) ? json_decode($question->options, true) : $question->options;
        $correctIndices = is_string($question->correct_answer) ? json_decode($question->correct_answer, true) : $question->correct_answer;
        
        // Handle correction check based on type
        if ($question->type === 'multiple_choice') {
            $correctValues = array_map(fn($idx) => $options[$idx] ?? null, $correctIndices);
            sort($correctValues);
            if (is_array($participantAnswer)) {
                sort($participantAnswer);
                $isCorrect = ($participantAnswer === $correctValues);
            }
        } elseif ($question->type === 'multiple_benar_salah') {
            $totalStatements = count($options);
            $correctCount = 0;
            if (is_array($participantAnswer)) {
                foreach ($options as $idx => $optText) {
                    $userAns = $participantAnswer[strval($idx)] ?? null;
                    $shouldBeBenar = in_array(strval($idx), array_map('strval', $correctIndices));
                    if (($shouldBeBenar && $userAns === 'benar') || (!$shouldBeBenar && $userAns === 'salah')) {
                        $correctCount++;
                    }
                }
            }
            $isCorrect = ($correctCount === $totalStatements);
        } else {
            $correctIndex = $correctIndices[0] ?? null;
            $correctValue = $options[$correctIndex] ?? null;
            $isCorrect = ($participantAnswer == $correctValue);
        }
    @endphp
    <div class="glass animate-fade-in" style="padding: 32px; margin-bottom: 24px; border-left: 6px solid {{ $participantAnswer === null ? '#94a3b8' : ($isCorrect ? '#10b981' : '#ef4444') }};">
        <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
            <span class="badge" style="background: #eff6ff; color: #475569;">SOAL NO {{ $index + 1 }}</span>
            <span class="badge" style="background: rgba(var(--accent-rgb), 0.1); color: var(--accent);">{{ $question->category->name }}</span>
        </div>

        <div style="font-size: 1.15rem; line-height: 1.6; margin-bottom: 24px; color: #0f172a;">
            {!! $question->question_text !!}
            @if($question->question_image)
            <div style="margin-top: 16px;">
                <img src="/storage/{{ $question->question_image }}" style="max-width: 100%; border-radius: 12px; border: 1px solid var(--glass-border);">
            </div>
            @endif
        </div>

        @if($question->type === 'multiple_benar_salah')
        {{-- Multiple Benar/Salah review --}}
        <div style="display: grid; gap: 12px; margin-bottom: 24px;">
            @foreach($options as $optIndex => $option)
            @php
                $shouldBeBenar = in_array(strval($optIndex), array_map('strval', $correctIndices));
                $userAns = is_array($participantAnswer) ? ($participantAnswer[strval($optIndex)] ?? null) : null;
                $statementCorrect = ($shouldBeBenar && $userAns === 'benar') || (!$shouldBeBenar && $userAns === 'salah');
                
                $bgColor = $statementCorrect ? 'rgba(16, 185, 129, 0.1)' : ($userAns !== null ? 'rgba(239, 68, 68, 0.1)' : 'rgba(255, 255, 255, 0.03)');
                $borderColor = $statementCorrect ? '#10b981' : ($userAns !== null ? '#ef4444' : 'var(--glass-border)');
            @endphp
            <div style="padding: 16px; border-radius: 12px; border: 1px solid {{ $borderColor }}; background: {{ $bgColor }};">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="color: #0f172a; font-weight: 500;">{{ $optIndex + 1 }}. {{ $option }}</span>
                </div>
                <div style="display: flex; gap: 12px; align-items: center; font-size: 0.85rem;">
                    <span style="color: #475569;">Jawaban Anda: <strong style="color: {{ $userAns ? ($statementCorrect ? '#10b981' : '#ef4444') : '#94a3b8' }};">{{ $userAns ? ucfirst($userAns) : 'Tidak dijawab' }}</strong></span>
                    <span style="color: #475569;">|</span>
                    <span style="color: #10b981; font-weight: 600;">Jawaban Benar: {{ $shouldBeBenar ? 'Benar' : 'Salah' }}</span>
                </div>
            </div>
            @endforeach
        </div>
        @if(isset($correctCount) && isset($totalStatements))
        <div style="background: #dbeafe; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem; color: var(--accent);">
            <i class="fas fa-chart-pie" style="margin-right: 8px;"></i> Skor: {{ $correctCount }}/{{ $totalStatements }} pernyataan benar ({{ round(($correctCount / max($totalStatements, 1)) * 100) }}%)
        </div>
        @endif
        @else
        {{-- Standard options review (pilihan_ganda, benar_salah, multiple_choice) --}}
        <div style="display: grid; gap: 12px; margin-bottom: 24px;">
            @foreach($options as $optIndex => $option)
            @php 
                $isThisCorrect = in_array($optIndex, $correctIndices);
                $isThisParticipantAnswer = false;
                if ($question->type === 'multiple_choice') {
                    $isThisParticipantAnswer = is_array($participantAnswer) && in_array($option, $participantAnswer);
                } else {
                    $isThisParticipantAnswer = ($participantAnswer == $option);
                }
                
                $bgColor = 'rgba(255, 255, 255, 0.03)';
                $borderColor = 'var(--glass-border)';
                
                if ($isThisCorrect) {
                    $bgColor = 'rgba(16, 185, 129, 0.1)';
                    $borderColor = '#10b981';
                }
                if ($isThisParticipantAnswer && !$isThisCorrect) {
                    $bgColor = 'rgba(239, 68, 68, 0.1)';
                    $borderColor = '#ef4444';
                }
            @endphp
            <div style="padding: 16px; border-radius: 12px; border: 1px solid {{ $borderColor }}; background: {{ $bgColor }}; display: flex; align-items: center; gap: 16px;">
                <div style="width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; background: {{ $isThisCorrect ? '#10b981' : ($isThisParticipantAnswer ? '#ef4444' : 'rgba(255,255,255,0.1)') }}; color: #0f172a;">
                    @if($isThisCorrect) <i class="fas fa-check"></i> @elseif($isThisParticipantAnswer) <i class="fas fa-times"></i> @else {{ chr(65 + $optIndex) }} @endif
                </div>
                <div style="color: {{ $isThisCorrect ? 'white' : ($isThisParticipantAnswer ? '#ef4444' : 'var(--text-secondary)') }}; font-weight: {{ ($isThisCorrect || $isThisParticipantAnswer) ? '600' : '400' }};">
                    {{ $option }}
                </div>
                @if($isThisCorrect)
                <span style="margin-left: auto; font-size: 0.7rem; color: #10b981; font-weight: 700; text-transform: uppercase;">Jawaban Benar</span>
                @elseif($isThisParticipantAnswer)
                <span style="margin-left: auto; font-size: 0.7rem; color: #ef4444; font-weight: 700; text-transform: uppercase;">Jawaban Anda</span>
                @endif
            </div>
            @endforeach
        </div>
        @endif

        @if($question->explanation)
        <div style="background: #f8fafc; border-radius: 12px; padding: 20px; border-top: 2px solid var(--accent);">
            <div style="font-size: 0.8rem; color: var(--accent); font-weight: 700; margin-bottom: 8px; text-transform: uppercase;">Pembahasan:</div>
            <div style="font-size: 0.95rem; color: #475569; line-height: 1.5;">
                {!! $question->explanation !!}
            </div>
        </div>
        @endif
    </div>
    @endforeach
</div>
@endsection
