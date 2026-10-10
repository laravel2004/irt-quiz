@extends('layouts.admin')

@section('title', 'Quiz Setting')
@section('header_title', 'Quiz Setting')

@section('content')
<div class="glass animate-fade-in" style="padding: 32px; margin-bottom: 24px;">
    <div style="margin-bottom: 24px;">
        <h3 style="font-family: 'Outfit', sans-serif; margin-bottom: 4px;">Pengaturan Quiz Harian</h3>
        <p style="color: var(--text-secondary); font-size: 0.9rem;">Atur kapan quiz di-reset, berapa soal per hari, dan XP untuk jawaban benar.</p>
    </div>

    @include('admin.quiz_settings._tabs')

    @if(session('success'))
        <div style="margin-bottom: 24px; padding: 16px; border-radius: 12px; background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2); color: #059669;">
            <i class="fas fa-check-circle" style="margin-right: 8px;"></i> {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div style="margin-bottom: 24px; padding: 16px; border-radius: 12px; background: rgba(244,63,94,0.1); border: 1px solid rgba(244,63,94,0.2); color: #e11d48;">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div style="margin-bottom: 24px; padding: 16px; border-radius: 12px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1e3a8a; font-size: 0.9rem;">
        <i class="fas fa-info-circle"></i>
        Periode quiz saat ini dimulai <strong>{{ $periodStart->translatedFormat('d M Y H:i') }}</strong>
        dan di-reset berikutnya <strong>{{ $nextReset->translatedFormat('d M Y H:i') }}</strong>.
    </div>

    <form action="{{ route('admin.quiz-settings.update') }}" method="POST" style="max-width: 560px;">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label>Jam Reset Quiz</label>
            <input type="time" name="reset_time" class="form-input" value="{{ old('reset_time', $setting->reset_time) }}" required>
            <small style="color: var(--text-secondary);">Setelah jam ini, user bisa mengerjakan quiz baru lagi.</small>
        </div>

        <div class="form-group">
            <label>Jumlah Soal per Hari</label>
            <input type="number" name="questions_per_day" class="form-input" min="1" max="20" value="{{ old('questions_per_day', $setting->questions_per_day) }}" required>
        </div>

        <div class="form-group">
            <label>XP per Jawaban Benar</label>
            <input type="number" name="xp_per_correct" class="form-input" min="0" value="{{ old('xp_per_correct', $setting->xp_per_correct) }}" required>
        </div>

        <div style="margin: 0 0 20px; padding: 12px 16px; border-radius: 12px; background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.3); color: #92400e; font-size: 0.85rem;">
            <i class="fas fa-exclamation-triangle"></i>
            Ubah jam reset sebaiknya di luar jam ramai. Mengubahnya di tengah hari dapat menggeser periode quiz yang sedang berjalan.
        </div>

        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Simpan Pengaturan</button>
        </div>
    </form>
</div>
@endsection
