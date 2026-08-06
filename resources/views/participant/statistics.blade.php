@extends('layouts.app')

@section('title', $isClosed ? 'Statistik Final' : 'Statistik Sementara')

@section('content')
<style>
    /* Styling khusus Leaderboard */
    .leaderboard-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 40px 20px;
    }
    
    .lb-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .lb-header h1 {
        font-family: 'Outfit', sans-serif;
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 12px;
        background: linear-gradient(135deg, #60a5fa, #a855f7);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .lb-subtitle {
        color: #94a3b8;
        font-size: 1.1rem;
    }

    .lb-subtitle span {
        color: #f8fafc;
        font-weight: 600;
    }

    .lb-info-box {
        background: rgba(15, 23, 42, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        padding: 16px 24px;
        border-radius: 12px;
        margin-bottom: 32px;
        display: flex;
        align-items: center;
        gap: 16px;
        color: #cbd5e1;
        font-size: 0.95rem;
        line-height: 1.5;
    }

    .lb-info-box.temporary {
        border-left: 4px solid #3b82f6;
    }

    .lb-info-box.final {
        border-left: 4px solid #eab308;
    }

    .lb-info-icon {
        font-size: 1.8rem;
        flex-shrink: 0;
    }

    .temporary .lb-info-icon { color: #60a5fa; }
    .final .lb-info-icon { color: #facc15; }

    /* Leaderboard Rows */
    .lb-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .lb-row {
        background: rgba(30, 41, 59, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 16px;
        padding: 16px 24px;
        display: flex;
        align-items: center;
        gap: 20px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        animation: slideUpFade 0.5s ease backwards;
    }

    .lb-row:hover {
        transform: translateY(-2px) scale(1.01);
        background: rgba(30, 41, 59, 0.8);
        border-color: rgba(255, 255, 255, 0.15);
        box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
    }

    @keyframes slideUpFade {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Podium Styles */
    .lb-row.rank-1 {
        background: linear-gradient(90deg, rgba(234, 179, 8, 0.12), rgba(30, 41, 59, 0.8));
        border-color: rgba(234, 179, 8, 0.3);
        box-shadow: 0 0 20px rgba(234, 179, 8, 0.1);
    }
    .lb-row.rank-2 {
        background: linear-gradient(90deg, rgba(148, 163, 184, 0.12), rgba(30, 41, 59, 0.8));
        border-color: rgba(148, 163, 184, 0.3);
    }
    .lb-row.rank-3 {
        background: linear-gradient(90deg, rgba(180, 83, 9, 0.12), rgba(30, 41, 59, 0.8));
        border-color: rgba(180, 83, 9, 0.3);
    }

    /* Current User Highlight */
    .lb-row.is-me {
        border-color: rgba(59, 130, 246, 0.6);
        box-shadow: 0 0 15px rgba(59, 130, 246, 0.15);
    }
    .lb-row.is-me::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: #3b82f6;
        box-shadow: 0 0 10px #3b82f6;
    }

    /* Column Layouts */
    .lb-rank {
        font-family: 'Outfit', sans-serif;
        font-size: 1.5rem;
        font-weight: 800;
        color: #64748b;
        width: 40px;
        text-align: center;
        flex-shrink: 0;
    }
    .rank-1 .lb-rank { color: #facc15; text-shadow: 0 0 15px rgba(250, 204, 21, 0.4); font-size: 1.8rem; }
    .rank-2 .lb-rank { color: #cbd5e1; }
    .rank-3 .lb-rank { color: #d97706; }

    .lb-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.05);
        border: 2px solid rgba(255, 255, 255, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.2rem;
        color: #f8fafc;
        flex-shrink: 0;
    }
    .rank-1 .lb-avatar { border-color: #facc15; color: #facc15; background: rgba(234, 179, 8, 0.1); }
    .rank-2 .lb-avatar { border-color: #cbd5e1; color: #cbd5e1; background: rgba(148, 163, 184, 0.1); }
    .rank-3 .lb-avatar { border-color: #d97706; color: #d97706; background: rgba(180, 83, 9, 0.1); }
    .is-me .lb-avatar { background: rgba(59, 130, 246, 0.2) !important; border-color: #60a5fa !important; color: #60a5fa !important; }

    .lb-details {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .lb-name {
        font-size: 1.1rem;
        font-weight: 600;
        color: #f8fafc;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .lb-badge-me {
        background: #3b82f6;
        color: white;
        font-size: 0.7rem;
        padding: 2px 8px;
        border-radius: 12px;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        box-shadow: 0 0 10px rgba(59, 130, 246, 0.5);
    }

    .lb-scores {
        display: flex;
        gap: 32px;
        align-items: center;
    }

    .lb-score-block {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }
    .lb-score-label {
        font-size: 0.75rem;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 600;
        margin-bottom: 4px;
    }
    .lb-score-value {
        font-family: 'Outfit', sans-serif;
        font-size: 1.4rem;
        font-weight: 700;
        color: #f8fafc;
    }
    .lb-score-block.primary .lb-score-value {
        color: #34d399; /* Green for primary score (IRT) */
    }
    .rank-1 .lb-score-block.primary .lb-score-value {
        color: #facc15; /* Gold for rank 1 */
    }
    
    /* Responsive */
    @media (max-width: 640px) {
        .lb-row {
            flex-wrap: wrap;
            padding: 16px;
        }
        .lb-scores {
            width: 100%;
            justify-content: space-between;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }
        .lb-score-block {
            align-items: flex-start;
        }
    }
</style>

<div class="leaderboard-container">
    <div style="margin-bottom: 24px;">
        <a href="{{ route('participant.dashboard') }}" style="color: #94a3b8; text-decoration: none; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 8px; transition: color 0.2s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='#94a3b8'">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>

    <div class="lb-header animate-fade-in">
        <h1>
            <i class="{{ $isClosed ? 'fas fa-trophy' : 'fas fa-chart-line' }}" style="font-size: 2rem; margin-right: 8px; vertical-align: middle;"></i>
            {{ $isClosed ? 'Statistik Final' : 'Statistik Sementara' }}
        </h1>
        <div class="lb-subtitle">Sesi Ujian: <span>{{ $session->name }}</span> &nbsp;&bull;&nbsp; Total Peserta: <span>{{ $rankings->count() }}</span></div>
    </div>

    @if(!$isClosed)
        <div class="lb-info-box temporary animate-fade-in">
            <i class="fas fa-info-circle lb-info-icon"></i>
            <div>Ini adalah <strong>Statistik Sementara</strong> yang diurutkan berdasarkan <strong>Skor Mentah (Raw Score)</strong>. Sesi ujian masih berlangsung sehingga peringkat masih bisa berubah sewaktu-waktu.</div>
        </div>
    @else
        <div class="lb-info-box final animate-fade-in">
            <i class="fas fa-check-circle lb-info-icon"></i>
            <div>Ini adalah <strong>Statistik Final</strong>. Peringkat telah dikalibrasi dan diurutkan berdasarkan <strong>Skor IRT</strong> (Item Response Theory) untuk memberikan hasil yang paling adil dan akurat.</div>
        </div>
    @endif

    <div class="lb-list">
        @forelse($rankings as $index => $res)
            @php
                $isCurrentUser = $res->participant->user_id === $user->id;
                $rankClass = '';
                if ($index === 0) $rankClass = 'rank-1';
                elseif ($index === 1) $rankClass = 'rank-2';
                elseif ($index === 2) $rankClass = 'rank-3';
                
                $meClass = $isCurrentUser ? 'is-me' : '';
                $delay = min($index * 0.05, 1.5); // 50ms stagger, max 1.5s delay
            @endphp
            
            <div class="lb-row {{ $rankClass }} {{ $meClass }}" style="animation-delay: {{ $delay }}s;">
                <div class="lb-rank">
                    @if($index === 0)
                        <i class="fas fa-crown"></i>
                    @else
                        {{ $index + 1 }}
                    @endif
                </div>
                
                <div class="lb-avatar">
                    {{ strtoupper(substr($res->participant->name, 0, 1)) }}
                </div>
                
                <div class="lb-details">
                    <div class="lb-name">
                        {{ $res->participant->name }}
                        @if($isCurrentUser)
                            <span class="lb-badge-me">Anda</span>
                        @endif
                    </div>
                </div>

                <div class="lb-scores">
                    <div class="lb-score-block">
                        <div class="lb-score-label">Skor Mentah</div>
                        <div class="lb-score-value">{{ number_format($res->score, 2) }}</div>
                    </div>
                    
                    @if($isClosed)
                    <div class="lb-score-block primary">
                        <div class="lb-score-label" style="color: inherit;">Skor IRT</div>
                        <div class="lb-score-value">{{ $res->irt_score > 0 ? number_format($res->irt_score, 2) : '-' }}</div>
                    </div>
                    @endif
                </div>
            </div>
        @empty
            <div style="text-align: center; padding: 60px 20px; background: rgba(30, 41, 59, 0.3); border-radius: 16px; border: 1px dashed rgba(255,255,255,0.1); color: #94a3b8; animation: slideUpFade 0.5s ease backwards;">
                <i class="fas fa-ghost" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.5;"></i>
                <p>Belum ada peserta yang menyelesaikan sesi ujian ini.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
