@extends('layouts.app')

@section('title', 'Dashboard Peserta')

@section('content')
<div class="container" style="padding: 40px 20px;">
    <div class="flex-stack-mobile" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; gap: 20px;">
        <div>
            <h1 style="font-family: 'Outfit', sans-serif; font-size: 2.2rem; margin-bottom: 8px;">Selamat Datang, {{ auth()->user()->name }}</h1>
            <div style="display: flex; gap: 12px; align-items: center;">
                <span class="badge" style="background: {{ auth()->user()->role === 'premium' ? 'rgba(234, 179, 8, 0.1)' : 'rgba(59, 130, 246, 0.1)' }}; color: {{ auth()->user()->role === 'premium' ? '#eab308' : '#3b82f6' }}; padding: 4px 12px; border-radius: 20px; font-weight: 600; font-size: 0.8rem;">
                    <i class="fas {{ auth()->user()->role === 'premium' ? 'fa-crown' : 'fa-user' }}" style="margin-right: 4px;"></i>
                    Peserta {{ ucfirst(auth()->user()->role) }}
                </span>
                <span style="color: var(--text-secondary); font-size: 0.9rem;">{{ auth()->user()->email }}</span>
            </div>
        </div>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn-primary" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid #ef4444;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </form>
    </div>

    @if(session('error'))
        <div class="glass animate-fade-in" style="margin-bottom: 24px; padding: 16px; background: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444;">
            <p style="color: #ef4444; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
            </p>
        </div>
    @endif

    <div class="glass animate-fade-in" style="padding: 32px;">
        <h3 style="font-family: 'Outfit', sans-serif; margin-bottom: 24px;">Ujian Saya</h3>
        
        <div class="responsive-grid" style="grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 24px;">
            @forelse($registrations as $reg)
            @php $session = $reg->examSession; @endphp
            <div class="glass card-hover" style="padding: 24px; border-radius: 16px; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                        <span class="badge {{ $session->is_active ? 'active' : '' }}" style="font-size: 0.7rem;">
                            {{ $session->is_active ? 'Sesi Terbuka' : 'Sesi Tertutup' }}
                        </span>
                        <code style="color: var(--accent); font-size: 0.8rem; font-weight: 600;">{{ $session->code }}</code>
                    </div>
                    <h4 style="font-family: 'Outfit', sans-serif; font-size: 1.2rem; margin-bottom: 12px; color: white;">{{ $session->name }}</h4>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px;">
                        <div style="font-size: 0.8rem; color: var(--text-secondary);">
                            <i class="fas fa-calendar-alt" style="width: 16px;"></i> {{ $session->start_date }}
                        </div>
                        <div style="font-size: 0.8rem; color: var(--text-secondary);">
                            <i class="fas fa-clock" style="width: 16px;"></i> {{ $session->duration }} Menit
                        </div>
                    </div>
                </div>

                @if($reg->finished_at)
                    @if(!$session->is_active && $reg->result)
                        <a href="{{ route('participant.result', $reg->id) }}" class="btn-primary" style="width: 100%; height: 44px; text-decoration: none; background: var(--accent);">
                            Lihat Hasil <i class="fas fa-chart-bar" style="margin-left: 8px;"></i>
                        </a>
                    @else
                        <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 12px; border-radius: 8px; text-align: center; font-weight: 600; font-size: 0.9rem;">
                            <i class="fas fa-check-circle"></i> Selesai Dikerjakan
                        </div>
                    @endif
                @elseif(!$session->is_active)
                    <div style="background: rgba(255,255,255,0.05); color: var(--text-secondary); padding: 12px; border-radius: 8px; text-align: center; font-size: 0.9rem;">
                        <i class="fas fa-lock"></i> Menunggu Sesi Dibuka
                    </div>
                @else
                    <button class="btn-primary" style="width: 100%; height: 44px;" onclick="openJoinModal('{{ $session->name }}', '{{ $session->code }}')">
                        Mulai Ujian <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
                    </button>
                @endif
            </div>
            @empty
            <div style="grid-column: 1 / -1; text-align: center; padding: 60px; color: var(--text-secondary);">
                <i class="fas fa-book-open" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.2;"></i>
                <p>Anda belum terdaftar di sesi ujian manapun.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Access Code Modal -->
<div class="modal-overlay" id="joinModal">
    <div class="modal-content glass animate-fade-in" style="max-width: 400px;">
        <div class="modal-header">
            <h3 id="modalSessionName">Mulai Ujian</h3>
            <button class="close-modal" onclick="closeJoinModal()">&times;</button>
        </div>
        <form action="{{ route('participant.join-session') }}" method="POST">
            @csrf
            <input type="hidden" name="session_code" id="modalSessionCode">
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 20px;">Masukkan kode akses yang diberikan oleh pengawas untuk memulai.</p>
            
            <div class="form-group">
                <label>Kode Akses (6 Digit)</label>
                <input type="text" name="access_code" class="form-input" style="text-align: center; font-size: 1.5rem; letter-spacing: 4px; font-weight: 800; text-transform: uppercase;" maxlength="6" placeholder="000000" required>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 32px;">
                <button type="button" class="btn-primary" style="flex: 1; background: transparent; border: 1px solid var(--glass-border); color: var(--text-secondary);" onclick="closeJoinModal()">Batal</button>
                <button type="submit" class="btn-primary" style="flex: 2;">Konfirmasi & Mulai</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openJoinModal(name, code) {
        document.getElementById('modalSessionName').innerText = name;
        document.getElementById('modalSessionCode').value = code;
        document.getElementById('joinModal').classList.add('active');
    }

    function closeJoinModal() {
        document.getElementById('joinModal').classList.remove('active');
    }
</script>
@endsection
