@extends('layouts.app')

@section('title', 'Pembahasan: ' . $registration->examSession->name)

@section('content')
<div class="container" style="padding: 40px 20px; max-width: 1000px; margin: 0 auto;">
    <div style="margin-bottom: 32px; display: flex; justify-content: space-between; align-items: center;">
        <a href="{{ route('participant.result', $registration->id) }}" style="color: var(--text-secondary); text-decoration: none; display: flex; align-items: center; gap: 8px; font-size: 0.9rem;">
            <i class="fas fa-arrow-left"></i> Kembali ke Hasil
        </a>
        <div style="text-align: right;">
            <h4 style="font-family: 'Outfit', sans-serif; margin-bottom: 4px;">{{ $registration->examSession->name }}</h4>
            <span class="badge" style="background: rgba(234, 179, 8, 0.1); color: #eab308;">Fitur Premium: Pembahasan</span>
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
        } else {
            $correctIndex = $correctIndices[0] ?? null;
            $correctValue = $options[$correctIndex] ?? null;
            $isCorrect = ($participantAnswer == $correctValue);
        }
    @endphp
    <div class="glass animate-fade-in" style="padding: 32px; margin-bottom: 24px; border-left: 6px solid {{ $participantAnswer === null ? '#94a3b8' : ($isCorrect ? '#10b981' : '#ef4444') }};">
        <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
            <span class="badge" style="background: rgba(255,255,255,0.05); color: var(--text-secondary);">SOAL NO {{ $index + 1 }}</span>
            <span class="badge" style="background: rgba(var(--accent-rgb), 0.1); color: var(--accent);">{{ $question->category->name }}</span>
        </div>

        <div style="font-size: 1.15rem; line-height: 1.6; margin-bottom: 24px; color: white;">
            {!! $question->question_text !!}
            @if($question->question_image)
            <div style="margin-top: 16px;">
                <img src="/storage/{{ $question->question_image }}" style="max-width: 100%; border-radius: 12px; border: 1px solid var(--glass-border);">
            </div>
            @endif
        </div>

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
                <div style="width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; background: {{ $isThisCorrect ? '#10b981' : ($isThisParticipantAnswer ? '#ef4444' : 'rgba(255,255,255,0.1)') }}; color: white;">
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

        @if($question->explanation)
        <div style="background: rgba(255, 255, 255, 0.03); border-radius: 12px; padding: 20px; border-top: 2px solid var(--accent);">
            <div style="font-size: 0.8rem; color: var(--accent); font-weight: 700; margin-bottom: 8px; text-transform: uppercase;">Pembahasan:</div>
            <div style="font-size: 0.95rem; color: var(--text-secondary); line-height: 1.5;">
                {!! $question->explanation !!}
            </div>
        </div>
        @endif
    </div>
    @endforeach
</div>
@endsection
