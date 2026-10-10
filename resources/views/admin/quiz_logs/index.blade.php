@extends('layouts.admin')

@section('title', 'Log Jawaban Quiz')
@section('header_title', 'Log Jawaban Quiz')

@section('content')
<div class="glass animate-fade-in" style="padding: 32px; margin-bottom: 24px;">
    <div style="margin-bottom: 24px;">
        <h3 style="font-family: 'Outfit', sans-serif; margin-bottom: 4px;">Log Jawaban Harian</h3>
        <p style="color: var(--text-secondary); font-size: 0.9rem;">Riwayat jawaban quiz harian peserta.</p>
    </div>

    @include('admin.quiz_settings._tabs')

    <form method="GET" action="{{ route('admin.quiz-logs.index') }}" style="display:flex; gap:12px; margin-bottom:24px; flex-wrap:wrap; width:100%;">
        <div style="display:flex; gap:12px; flex:1; min-width:300px; flex-wrap:wrap;">
            <input type="text" name="search" placeholder="Cari nama/email..." value="{{ $search }}" class="form-input" style="flex:1; min-width:150px; margin-bottom:0;">
            <select name="jenjang" class="form-input" style="flex:1; min-width:120px; margin-bottom:0;">
                <option value="">Semua Jenjang</option>
                <option value="SD" {{ $jenjang === 'SD' ? 'selected' : '' }}>SD</option>
                <option value="SMP" {{ $jenjang === 'SMP' ? 'selected' : '' }}>SMP</option>
                <option value="SMA" {{ $jenjang === 'SMA' ? 'selected' : '' }}>SMA</option>
            </select>
            <select name="status" class="form-input" style="flex:1; min-width:120px; margin-bottom:0;" onchange="this.form.submit()">
                <option value="">Semua Hasil</option>
                <option value="benar" {{ $status === 'benar' ? 'selected' : '' }}>Benar</option>
                <option value="salah" {{ $status === 'salah' ? 'selected' : '' }}>Salah</option>
                <option value="belum" {{ $status === 'belum' ? 'selected' : '' }}>Belum dijawab</option>
            </select>
            <input type="date" name="date" class="form-input" value="{{ $date }}" onchange="this.form.submit()" style="flex:1; min-width:150px; margin-bottom:0;">
        </div>
        @if($search || $status || $date || $jenjang)
            <a href="{{ route('admin.quiz-logs.index') }}" class="btn-primary" style="background:#ef4444; white-space:nowrap; flex-shrink:0; text-decoration:none;">
                <i class="fas fa-times"></i> Reset
            </a>
        @endif
    </form>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>WAKTU</th>
                    <th>USER</th>
                    <th>JENJANG</th>
                    <th>PERIODE</th>
                    <th>SOAL</th>
                    <th>HASIL</th>
                    <th>XP</th>
                    <th>AKSI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td>{{ $log->answered_at?->format('d M Y H:i') ?? '-' }}</td>
                    <td>
                        <div style="font-weight: 600;">{{ $log->user->name }}</div>
                        <div style="font-size: 0.85rem; color: var(--text-secondary);">{{ $log->user->email }}</div>
                    </td>
                    <td><span class="badge" style="background:#f1f5f9; color:#475569;">{{ $log->user->jenjang ?? '-' }}</span></td>
                    <td>{{ \Carbon\Carbon::parse($log->period_key)->format('d M Y') }}</td>
                    <td style="max-width:200px;">{{ \Illuminate\Support\Str::limit(strip_tags($log->question_snapshot), 80) }}</td>
                    <td>
                        @if(is_null($log->answered_at))
                            <span class="badge" style="background:#f1f5f9; color:#475569;">Belum dijawab</span>
                        @elseif($log->is_correct)
                            <span class="badge" style="background:#ecfdf5; color:#047857;">Benar</span>
                        @else
                            <span class="badge" style="background:#fef2f2; color:#b91c1c;">Salah</span>
                        @endif
                    </td>
                    <td><span style="font-weight:600; color:#7c3aed;">+{{ $log->xp_earned }}</span></td>
                    <td>
                        <a href="{{ route('admin.quiz-logs.show', $log->id) }}" class="btn-icon" title="Detail"><i class="fas fa-eye"></i></a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center;">Belum ada log jawaban.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 24px; display: flex; justify-content: center;">
        {{ $logs->links() }}
    </div>
</div>

<style>
    /* Premium Pagination Styling */
    .pagination {
        display: flex !important;
        gap: 8px !important;
        list-style: none !important;
        padding: 0 !important;
        margin: 0 !important;
        align-items: center !important;
        justify-content: center !important;
    }
    .page-item {
        margin: 0 !important;
    }
    .page-item .page-link {
        width: 40px !important;
        height: 40px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        background: #f8fafc !important;
        border: 1px solid var(--glass-border) !important;
        border-radius: 12px !important;
        color: var(--text-secondary) !important;
        text-decoration: none !important;
        font-weight: 600 !important;
        transition: all 0.3s ease !important;
        padding: 0 !important;
        font-size: 0.9rem !important;
    }
    .page-item.active .page-link {
        background: var(--accent) !important;
        border-color: var(--accent) !important;
        color: white !important;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3) !important;
    }
    .page-item.disabled .page-link {
        opacity: 0.3 !important;
        cursor: not-allowed !important;
        background: transparent !important;
    }
    .page-item .page-link:hover:not(.disabled):not(.active) {
        background: #eff6ff !important;
        transform: translateY(-2px) !important;
        color: white !important;
    }
    nav div:first-child {
        display: none !important;
    }
    nav div:last-child {
        display: flex !important;
        justify-content: center !important;
    }
</style>
@endsection
