@extends('layouts.admin')

@section('title', 'Manajemen Peserta')
@section('header_title', 'Manajemen Peserta')

@section('content')
<div class="glass animate-fade-in" style="padding: 32px; margin-bottom: 24px;">
    <div class="flex-stack-mobile" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; gap: 20px;">
        <div>
            <h3 style="font-family: 'Outfit', sans-serif; margin-bottom: 4px;">Daftar Peserta</h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Kelola data peserta ujian (Basic & Premium).</p>
        </div>
        <div class="flex-stack-mobile" style="display: flex; gap: 16px; align-items: center;">
            <form method="GET" action="{{ route('admin.participants.index') }}" style="position: relative; width: 300px; margin: 0; display: flex; gap: 8px;">
                <div style="position: relative; width: 100%;">
                    <i class="fas fa-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                    <input type="text" name="search" id="searchInput" class="form-input" placeholder="Cari peserta (tekan enter)..." value="{{ request('search') }}" style="padding-left: 44px; margin-bottom: 0; width: 100%;">
                </div>
                @if(request('search'))
                    <a href="{{ route('admin.participants.index') }}" class="btn-primary" style="background: #f1f5f9; color: var(--text-primary); border: 1px solid var(--glass-border); padding: 0 16px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; border-radius: 8px;">
                        Reset
                    </a>
                @endif
            </form>
            <button class="btn-primary" onclick="openParticipantModal('create')" style="flex-shrink: 0;">
                <i class="fas fa-plus"></i> Tambah Peserta
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>NAMA</th>
                    <th>EMAIL</th>
                    <th>WHATSAPP</th>
                    <th>ROLE</th>
                    <th>SEKOLAH</th>
                    <th style="width: 150px; text-align: center;">AKSI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($participants as $user)
                <tr>
                    <td><div style="font-weight: 600;">{{ $user->name }}</div></td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->phone ?? '-' }}</td>
                    <td>
                        @php
                            $bgRole = 'rgba(59, 130, 246, 0.1)';
                            $colorRole = '#3b82f6';
                            if($user->role === 'superadmin') { $bgRole = 'rgba(234, 179, 8, 0.1)'; $colorRole = '#eab308'; }
                            elseif($user->role === 'admin_sesi') { $bgRole = 'rgba(139, 92, 246, 0.1)'; $colorRole = '#8b5cf6'; }
                        @endphp
                        <span class="badge" style="background: {{ $bgRole }}; color: {{ $colorRole }};">
                            {{ ucfirst(str_replace('_', ' ', $user->role)) }}
                        </span>
                    </td>
                    <td style="font-size: 0.85rem; color: var(--text-secondary);">{{ Str::limit($user->address, 50) ?? '-' }}</td>
                    <td style="text-align: center;">
                        <button class="btn-icon" onclick="editParticipant({{ $user->id }})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon delete" onclick="deleteParticipant({{ $user->id }})" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-secondary);">Belum ada peserta.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div style="margin-top: 24px; display: flex; justify-content: center;">
        {{ $participants->links() }}
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
    /* Hide the 'Showing X to Y' part if it's messy */
    nav div:first-child {
        display: none !important;
    }
    nav div:last-child {
        display: flex !important;
        justify-content: center !important;
        width: 100% !important;
    }
</style>

<!-- Participant Modal -->
<div class="modal-overlay" id="participantModal">
    <div class="modal-content glass animate-fade-in" style="max-width: 600px;">
        <div class="modal-header">
            <h3 id="modalTitle">Tambah Peserta Baru</h3>
            <button class="close-modal" onclick="closeParticipantModal()">&times;</button>
        </div>
        <form id="participantForm">
            @csrf
            <input type="hidden" id="participantId">
            
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="name" id="pName" class="form-input" placeholder="Masukkan nama lengkap" required>
            </div>

            <div class="flex-stack-mobile" style="display: flex; gap: 16px; width: 100%;">
                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                    <label>Email</label>
                    <input type="email" name="email" id="pEmail" class="form-input" placeholder="email@contoh.com" required>
                </div>
                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                    <label>No. WhatsApp</label>
                    <input type="text" name="phone" id="pPhone" class="form-input" placeholder="08123456789">
                </div>
            </div>

            <div class="flex-stack-mobile" style="display: flex; gap: 16px; width: 100%; margin-top: 16px;">
                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                    <label>Role</label>
                    <select name="role" id="pRole" class="form-input" required>
                        <option value="basic">Basic (Peserta Ujian)</option>
                        <option value="admin_sesi">Admin Sesi</option>
                        <option value="superadmin">Superadmin</option>
                    </select>
                </div>
                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                    <label>Password</label>
                    <input type="password" name="password" id="pPassword" class="form-input" placeholder="Min. 6 karakter">
                    <small id="passwordNote" style="color: var(--text-secondary); font-size: 0.7rem; display: none;">Kosongkan jika tidak ingin mengubah password.</small>
                </div>
            </div>

            <div class="form-group" style="margin-top: 16px;">
                <label>Sekolah</label>
                <textarea name="address" id="pAddress" class="form-input" style="height: 80px;" placeholder="Masukkan asal sekolah (opsional)"></textarea>
            </div>

            <div class="flex-stack-mobile" style="display: flex; gap: 12px; margin-top: 32px; justify-content: flex-end;">
                <button type="button" class="btn-primary" style="background: transparent; border: 1px solid var(--glass-border); color: var(--text-secondary);" onclick="closeParticipantModal()">Batal</button>
                <button type="submit" class="btn-primary">Simpan Data</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const pModal = document.getElementById('participantModal');
    const pForm = document.getElementById('participantForm');
    let mode = 'create';

    function openParticipantModal(m) {
        mode = m;
        document.getElementById('modalTitle').innerText = m === 'create' ? 'Tambah Peserta Baru' : 'Edit Peserta';
        document.getElementById('participantId').value = '';
        pForm.reset();
        document.getElementById('pPassword').required = m === 'create';
        document.getElementById('passwordNote').style.display = m === 'edit' ? 'block' : 'none';
        pModal.classList.add('active');
    }

    function closeParticipantModal() {
        pModal.classList.remove('active');
    }

    pForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const id = document.getElementById('participantId').value;
        const url = mode === 'create' ? "{{ route('admin.participants.store') }}" : `/admin/participants/${id}`;
        
        if (mode === 'edit') formData.append('_method', 'PUT');

        fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showToast(data.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message || 'Gagal menyimpan data', 'error');
            }
        });
    });

    function editParticipant(id) {
        fetch(`/admin/participants/${id}`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            const p = data.data;
            openParticipantModal('edit');
            document.getElementById('participantId').value = p.id;
            document.getElementById('pName').value = p.name;
            document.getElementById('pEmail').value = p.email;
            document.getElementById('pPhone').value = p.phone || '';
            document.getElementById('pRole').value = p.role;
            document.getElementById('pAddress').value = p.address || '';
        });
    }

    function deleteParticipant(id) {
        customConfirm('Hapus peserta ini? Data login peserta akan dihapus secara permanen.', function() {
            fetch(`/admin/participants/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast('Peserta berhasil dihapus');
                    setTimeout(() => location.reload(), 500);
                }
            });
        });
    }

    // Removed client-side search, handled via backend now
</script>
@endpush
