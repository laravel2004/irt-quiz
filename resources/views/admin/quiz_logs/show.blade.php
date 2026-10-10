@extends('layouts.admin')

@section('title', 'Detail Log Jawaban')
@section('header_title', 'Detail Log Jawaban')

@section('content')
<div class="glass animate-fade-in" style="padding: 32px; margin-bottom: 24px;">
    <div style="margin-bottom: 24px;">
        <a href="{{ route('admin.quiz-logs.index') }}" class="btn-primary" style="background:#64748b; text-decoration:none; display:inline-block; margin-bottom:16px;">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
        <h3 style="font-family: 'Outfit', sans-serif; margin-bottom: 4px;">Detail Jawaban</h3>
    </div>

    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:24px; background:#f8fafc; padding:20px; border-radius:16px; border:1px solid #e2e8f0;">
        <div>
            <div style="color:var(--text-secondary); font-size:0.85rem; margin-bottom:4px;">Peserta</div>
            <div style="font-weight:600;">{{ $attempt->user->name }}</div>
            <div style="font-size:0.9rem; color:var(--text-secondary);">{{ $attempt->user->email }}</div>
        </div>
        <div>
            <div style="color:var(--text-secondary); font-size:0.85rem; margin-bottom:4px;">Jenjang</div>
            <div><span class="badge" style="background:#e2e8f0; color:#475569;">{{ $attempt->user->jenjang ?? '-' }}</span></div>
        </div>
        <div>
            <div style="color:var(--text-secondary); font-size:0.85rem; margin-bottom:4px;">Periode</div>
            <div style="font-weight:600;">{{ \Carbon\Carbon::parse($attempt->period_key)->format('d M Y') }}</div>
        </div>
        <div>
            <div style="color:var(--text-secondary); font-size:0.85rem; margin-bottom:4px;">Waktu Menjawab</div>
            <div style="font-weight:600;">{{ $attempt->answered_at?->format('d M Y H:i:s') ?? '-' }}</div>
        </div>
        <div>
            <div style="color:var(--text-secondary); font-size:0.85rem; margin-bottom:4px;">Hasil & XP</div>
            <div>
                @if(is_null($attempt->answered_at))
                    <span class="badge" style="background:#e2e8f0; color:#475569;">Belum dijawab</span>
                @elseif($attempt->is_correct)
                    <span class="badge" style="background:#ecfdf5; color:#047857;">Benar</span>
                    <span style="font-weight:600; color:#7c3aed; margin-left:8px;">+{{ $attempt->xp_earned }} XP</span>
                @else
                    <span class="badge" style="background:#fef2f2; color:#b91c1c;">Salah</span>
                    <span style="font-weight:600; color:#7c3aed; margin-left:8px;">+0 XP</span>
                @endif
            </div>
        </div>
    </div>

    <div style="margin-bottom: 24px;">
        <h4 style="font-family:'Outfit', sans-serif; margin-bottom:12px;">Soal</h4>
        @if(!$attempt->quiz)
            <div style="background:#fffbeb; color:#b45309; padding:8px 12px; border-radius:8px; font-size:0.85rem; margin-bottom:12px;">
                <i class="fas fa-exclamation-triangle"></i> Soal asli sudah dihapus, menampilkan salinan
            </div>
        @endif
        <div style="background:#fff; padding:20px; border-radius:16px; border:1px solid #e2e8f0; font-size:1.05rem;">
            {!! $attempt->quiz?->question_text ?? $attempt->question_snapshot !!}
        </div>
    </div>

    @if($attempt->quiz)
        <div style="margin-bottom: 24px;">
            <h4 style="font-family:'Outfit', sans-serif; margin-bottom:12px;">Pilihan Jawaban</h4>
            <div style="display:flex; flex-direction:column; gap:12px;">
                @foreach((array)$attempt->quiz->options as $i => $opt)
                    @php
                        $isKey = in_array((string)$i, $correct, true);
                        $userChosen = false;
                        $mbsText = '';
                        if ($attempt->quiz->type === 'pilihan_ganda' || $attempt->quiz->type === 'benar_salah') {
                            $userChosen = (string)$attempt->answer === (string)$i;
                        } elseif ($attempt->quiz->type === 'multiple_choice') {
                            $userChosen = is_array($attempt->answer) && in_array((string)$i, array_map('strval', $attempt->answer), true);
                        } elseif ($attempt->quiz->type === 'multiple_benar_salah') {
                            $userChosen = false; // special rendering
                            $ansStr = $attempt->answer[(string)$i] ?? '-';
                            $keyStr = $isKey ? 'benar' : 'salah';
                            $mbsText = "Jawaban user: <span style='font-weight:600;'>$ansStr</span> | Kunci: <span style='font-weight:600;'>$keyStr</span>";
                        }
                    @endphp
                    <div style="display:flex; align-items:center; gap:16px; padding:16px; border-radius:12px; border:2px solid {{ $userChosen ? '#3b82f6' : '#e2e8f0' }}; background:{{ $isKey ? '#ecfdf5' : '#fff' }}; {{ $isKey ? 'border-color:#10b981;' : '' }}">
                        @if($attempt->quiz->type !== 'multiple_benar_salah')
                            <div style="width:36px; height:36px; border-radius:8px; background:{{ $isKey ? '#10b981' : ($userChosen ? '#3b82f6' : '#f1f5f9') }}; color:{{ $isKey || $userChosen ? '#fff' : '#475569' }}; display:flex; align-items:center; justify-content:center; font-weight:700;">
                                {{ chr(65 + $i) }}
                            </div>
                        @endif
                        <div style="flex:1;">
                            {!! $opt !!}
                            @if($mbsText)
                                <div style="margin-top:8px; font-size:0.9rem; color:#475569;">{!! $mbsText !!}</div>
                            @endif
                        </div>
                        @if($userChosen && $attempt->quiz->type !== 'multiple_benar_salah')
                            <div style="color:#3b82f6; font-size:0.85rem; font-weight:600;"><i class="fas fa-check"></i> Jawaban User</div>
                        @endif
                        @if($isKey && $attempt->quiz->type !== 'multiple_benar_salah')
                            <div style="color:#10b981; font-size:0.85rem; font-weight:600;"><i class="fas fa-key"></i> Kunci Benar</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        @if($attempt->quiz->explanation)
            <div>
                <h4 style="font-family:'Outfit', sans-serif; margin-bottom:12px;">Pembahasan</h4>
                <div style="background:#faf5ff; padding:20px; border-radius:16px; border:1px dashed #c4b5fd;">
                    {!! $attempt->quiz->explanation !!}
                </div>
            </div>
        @endif
    @endif
</div>
@endsection
