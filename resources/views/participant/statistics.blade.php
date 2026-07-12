@extends('layouts.app')

@section('title', $isClosed ? 'Statistik Final' : 'Statistik Sementara')

@section('content')
<div class="container" style="padding: 40px 20px;">
    <div style="margin-bottom: 30px;">
        <a href="{{ route('participant.dashboard') }}" style="color: #cbd5e1; text-decoration: none; font-size: 0.9rem; transition: color 0.2s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='#cbd5e1'">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>

    <div class="glass animate-fade-in" style="padding: 32px; border-radius: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
            <div>
                <h1 style="font-family: 'Outfit', sans-serif; font-size: 2rem; margin-bottom: 8px;">
                    <i class="{{ $isClosed ? 'fas fa-trophy' : 'fas fa-list-ol' }}" style="color: {{ $isClosed ? '#eab308' : '#3b82f6' }}; margin-right: 12px;"></i>
                    {{ $isClosed ? 'Statistik Final' : 'Statistik Sementara' }}
                </h1>
                <p style="color: #cbd5e1; font-size: 1rem; margin: 0;">Sesi: <span style="color: white; font-weight: 600;">{{ $session->name }}</span></p>
            </div>
            <div>
                <span class="badge" style="background: rgba(59, 130, 246, 0.1); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.2); padding: 8px 16px; font-size: 0.9rem;">
                    Total Peserta Selesai: <strong style="color: white;">{{ $rankings->count() }}</strong>
                </span>
            </div>
        </div>

        @if(!$isClosed)
            <div style="background: #dbeafe; border-left: 4px solid #3b82f6; padding: 16px; border-radius: 8px; margin-bottom: 24px; color: #bfdbfe; font-size: 0.9rem;">
                <i class="fas fa-info-circle" style="margin-right: 8px;"></i> Ini adalah <strong>Statistik Sementara</strong> yang diurutkan berdasarkan <strong>Skor Raw</strong>. Sesi ini belum ditutup. Peringkat masih bisa berubah.
            </div>
        @else
            <div style="background: rgba(234, 179, 8, 0.1); border-left: 4px solid #eab308; padding: 16px; border-radius: 8px; margin-bottom: 24px; color: #fef08a; font-size: 0.9rem;">
                <i class="fas fa-check-circle" style="margin-right: 8px;"></i> Ini adalah <strong>Statistik Final</strong>. Peringkat diurutkan berdasarkan <strong>Skor IRT</strong> (jika sudah digenerate) dan <strong>Skor Raw</strong>.
            </div>
        @endif

        <div style="overflow-x: auto;">
            <div class="table-responsive">
            <table style="width: 100%; border-collapse: separate; border-spacing: 0; min-width: 600px;">
                <thead>
                    <tr>
                        <th style="text-align: center; padding: 16px; background: rgba(0,0,0,0.3); color: #cbd5e1; border-top-left-radius: 12px; border-bottom-left-radius: 12px;">Peringkat</th>
                        <th style="text-align: left; padding: 16px; background: rgba(0,0,0,0.3); color: #cbd5e1;">Nama Peserta</th>
                        <th style="text-align: center; padding: 16px; background: rgba(0,0,0,0.3); color: #cbd5e1;">Skor Raw</th>
                        @if($isClosed)
                            <th style="text-align: center; padding: 16px; background: rgba(0,0,0,0.3); color: #cbd5e1; border-top-right-radius: 12px; border-bottom-right-radius: 12px;">Skor IRT</th>
                        @else
                            <th style="background: rgba(0,0,0,0.3); border-top-right-radius: 12px; border-bottom-right-radius: 12px;"></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($rankings as $index => $res)
                        @php
                            $isCurrentUser = $res->participant->user_id === $user->id;
                            // Add a little highlight color for the current user
                            $rowBg = $isCurrentUser ? 'rgba(59, 130, 246, 0.15)' : 'rgba(255, 255, 255, 0.02)';
                            $borderColor = $isCurrentUser ? '#3b82f6' : 'rgba(255,255,255,0.05)';
                        @endphp
                        <tr style="background: {{ $rowBg }}; transition: all 0.2s;">
                            <td style="padding: 16px; text-align: center; font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 1.1rem; color: {{ $index < 3 ? '#eab308' : '#0f172a' }}; border-bottom: 1px solid {{ $borderColor }};">
                                @if($index == 0)
                                    <i class="fas fa-medal" style="color: #fbbf24; margin-right: 4px;"></i> 1
                                @elseif($index == 1)
                                    <i class="fas fa-medal" style="color: #9ca3af; margin-right: 4px;"></i> 2
                                @elseif($index == 2)
                                    <i class="fas fa-medal" style="color: #b45309; margin-right: 4px;"></i> 3
                                @else
                                    {{ $index + 1 }}
                                @endif
                            </td>
                            <td style="padding: 16px; border-bottom: 1px solid {{ $borderColor }};">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 36px; height: 36px; border-radius: 50%; background: rgba(59, 130, 246, 0.2); border: 1px solid rgba(59, 130, 246, 0.3); display: flex; align-items: center; justify-content: center; font-weight: bold; color: #60a5fa;">
                                        {{ strtoupper(substr($res->participant->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: {{ $isCurrentUser ? '#2563eb' : '#0f172a' }};">
                                            {{ $res->participant->name }}
                                            @if($isCurrentUser)
                                                <span class="badge" style="background: #3b82f6; color: #0f172a; padding: 2px 6px; font-size: 0.7rem; margin-left: 8px;">Anda</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 16px; text-align: center; font-family: monospace; font-size: 1.1rem; color: #0f172a; border-bottom: 1px solid {{ $borderColor }};">
                                {{ number_format($res->score, 2) }}
                            </td>
                            @if($isClosed)
                                <td style="padding: 16px; text-align: center; font-family: monospace; font-size: 1.1rem; color: #10b981; font-weight: bold; border-bottom: 1px solid {{ $borderColor }};">
                                    {{ $res->irt_score > 0 ? number_format($res->irt_score, 2) : '-' }}
                                </td>
                            @else
                                <td style="border-bottom: 1px solid {{ $borderColor }};"></td>
                            @endif
                        </tr>
                    @endforeach
                    @if($rankings->isEmpty())
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 40px; color: #475569;">
                                Belum ada peserta yang menyelesaikan sesi ini.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>
@endsection
