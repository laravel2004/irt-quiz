@extends('layouts.admin')

@section('title', 'Hotspot Dashboard')
@section('header_title', 'Hotspot - Ringkasan Sistem')

@section('content')
<div class="stat-grid">
    <div class="stat-card glass animate-fade-in" style="animation-delay: 0.1s;">
        <div class="icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="value">{{ number_format($totalParticipants) }}</div>
        <div class="label">Total Peserta</div>
    </div>
    <div class="stat-card glass animate-fade-in" style="animation-delay: 0.2s;">
        <div class="icon" style="color: #10b981; background: rgba(16, 185, 129, 0.1);">
            <i class="fas fa-question-circle"></i>
        </div>
        <div class="value">{{ number_format($totalQuestions) }}</div>
        <div class="label">Total Bank Soal</div>
    </div>
    <div class="stat-card glass animate-fade-in" style="animation-delay: 0.3s;">
        <div class="icon" style="color: #f59e0b; background: rgba(245, 158, 11, 0.1);">
            <i class="fas fa-clock"></i>
        </div>
        <div class="value">{{ number_format($activeSessionsCount) }}</div>
        <div class="label">Sesi Aktif</div>
    </div>
    <div class="stat-card glass animate-fade-in" style="animation-delay: 0.4s;">
        <div class="icon" style="color: #8b5cf6; background: rgba(139, 92, 246, 0.1);">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="value">{{ number_format($avgScore, 0) }}</div>
        <div class="label">Avg. IRT Score</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
    <!-- Recent Sessions -->
    <div class="glass" style="padding: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 style="font-family: 'Outfit', sans-serif;">Sesi Ujian Terbaru</h3>
            <a href="{{ route('admin.sessions.index') }}" style="color: var(--accent); text-decoration: none; font-size: 0.85rem;">Lihat Semua</a>
        </div>
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="color: var(--text-secondary); font-size: 0.85rem; border-bottom: 1px solid var(--glass-border);">
                    <th style="padding: 12px 0;">NAMA SESI</th>
                    <th style="padding: 12px 0; text-align: center;">SOAL</th>
                    <th style="padding: 12px 0; text-align: center;">PESERTA</th>
                    <th style="padding: 12px 0; text-align: right;">STATUS</th>
                </tr>
            </thead>
            <tbody style="font-size: 0.9rem;">
                @foreach($recentSessions as $s)
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <td style="padding: 16px 0;">
                        <div style="font-weight: 600;">{{ $s->name }}</div>
                        <code style="font-size: 0.75rem; color: var(--accent);">{{ $s->code }}</code>
                    </td>
                    <td style="text-align: center;">{{ $s->questions_count }}</td>
                    <td style="text-align: center;">{{ $s->participants_count }}</td>
                    <td style="text-align: right;">
                        <span class="badge {{ $s->is_active ? 'active' : '' }}" style="font-size: 0.75rem; padding: 4px 12px;">
                            {{ $s->is_active ? 'Aktif' : 'Selesai' }}
                        </span>
                    </td>
                </tr>
                @endforeach
                @if($recentSessions->isEmpty())
                <tr>
                    <td colspan="4" style="text-align: center; padding: 40px; color: var(--text-secondary);">Belum ada sesi ujian.</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>

    <!-- Quick Actions -->
    <div class="glass" style="padding: 24px;">
        <h3 style="font-family: 'Outfit', sans-serif; margin-bottom: 24px;">Quick Actions</h3>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <a href="{{ route('admin.sessions.index') }}" class="btn-primary" style="justify-content: flex-start; background: rgba(59, 130, 246, 0.1); color: var(--accent); text-decoration: none; width: auto; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-plus-circle"></i> Kelola Sesi
            </a>
            <a href="{{ route('admin.questions.index') }}" class="btn-primary" style="justify-content: flex-start; background: rgba(139, 92, 246, 0.1); color: #8b5cf6; text-decoration: none; width: auto; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-question-circle"></i> Kelola Soal
            </a>
            <a href="{{ route('admin.categories.index') }}" class="btn-primary" style="justify-content: flex-start; background: rgba(245, 158, 11, 0.1); color: #f59e0b; text-decoration: none; width: auto; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-tags"></i> Kelola Kategori
            </a>
        </div>

        <div style="margin-top: 32px;">
            <h4 style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 16px;">Sistem Status</h4>
            <div style="display: flex; align-items: center; gap: 12px; font-size: 0.85rem;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #10b981;"></div>
                <span>IRT Assessment Engine Online</span>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; font-size: 0.85rem; margin-top: 12px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #10b981;"></div>
                <span>Database Sync Success</span>
            </div>
        </div>
    </div>
</div>
@endsection
