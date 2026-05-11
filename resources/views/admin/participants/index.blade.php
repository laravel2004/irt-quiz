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
        <div class="flex-stack-mobile" style="display: flex; gap: 16px;">
            <div style="position: relative; width: 300px;">
                <i class="fas fa-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                <input type="text" id="searchInput" class="form-input" placeholder="Cari peserta..." style="padding-left: 44px; margin-bottom: 0;">
            </div>
            <button class="btn-primary" onclick="openParticipantModal('create')">
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
                    <th>ALAMAT</th>
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
                        <span class="badge {{ $user->role === 'premium' ? 'active' : '' }}" style="background: {{ $user->role === 'premium' ? 'rgba(234, 179, 8, 0.1)' : 'rgba(59, 130, 246, 0.1)' }}; color: {{ $user->role === 'premium' ? '#eab308' : '#3b82f6' }};">
                            {{ ucfirst($user->role) }}
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
</div>

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

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="pEmail" class="form-input" placeholder="email@contoh.com" required>
                </div>
                <div class="form-group">
                    <label>No. WhatsApp</label>
                    <input type="text" name="phone" id="pPhone" class="form-input" placeholder="08123456789">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="pRole" class="form-input" required>
                        <option value="basic">Basic</option>
                        <option value="premium">Premium</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" id="pPassword" class="form-input" placeholder="Min. 6 karakter">
                    <small id="passwordNote" style="color: var(--text-secondary); font-size: 0.7rem; display: none;">Kosongkan jika tidak ingin mengubah password.</small>
                </div>
            </div>

            <div class="form-group">
                <label>Alamat</label>
                <textarea name="address" id="pAddress" class="form-input" style="height: 80px;" placeholder="Masukkan alamat lengkap (opsional)"></textarea>
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

    // Search Filter
    document.getElementById('searchInput').addEventListener('input', function(e) {
        const term = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('.data-table tbody tr');

        rows.forEach(row => {
            if (row.cells.length < 5) return;
            const name = row.cells[0].textContent.toLowerCase();
            const email = row.cells[1].textContent.toLowerCase();
            
            if (name.includes(term) || email.includes(term)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
</script>
@endpush
