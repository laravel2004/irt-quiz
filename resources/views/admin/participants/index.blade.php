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
            <button class="btn-primary" onclick="openBulkReportModal()" style="flex-shrink: 0; background: #10b981;">
                <i class="fas fa-file-alt"></i> Bulk Raport
            </button>
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
                        <button class="btn-icon" onclick="openReportHistoryModal({{ $user->id }})" title="History Raport" style="color: #3b82f6;">
                            <i class="fas fa-history"></i>
                        </button>
                        <button class="btn-icon" onclick="openReportModal({{ $user->id }})" title="Cetak Raport" style="color: #10b981;">
                            <i class="fas fa-file-alt"></i>
                        </button>
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

<!-- Modal Cetak Raport -->
<div class="modal-overlay" id="reportModal">
    <div class="modal-content glass animate-fade-in" style="max-width: 600px;">
        <div class="modal-header">
            <h3 id="reportModalTitle">Cetak Raport</h3>
            <button class="close-modal" onclick="closeReportModal()">&times;</button>
        </div>

        <!-- State 1: Loading daftar sesi -->
        <div id="reportLoading" style="text-align: center; padding: 40px;">
            <div style="width: 40px; height: 40px; border: 3px solid rgba(59, 130, 246, 0.3); border-top-color: #3b82f6; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 16px;"></div>
            <p style="color: var(--text-secondary);">Memuat daftar sesi ujian...</p>
        </div>

        <!-- State 2: Daftar sesi untuk dipilih -->
        <div id="reportSessionList" style="display: none;">
            <p style="color: var(--text-secondary); margin-bottom: 16px; font-size: 0.9rem;">
                Pilih sesi ujian yang ingin dimasukkan ke dalam raport:
            </p>
            <div id="sessionCheckboxes" style="max-height: 300px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px;">
                <!-- Checkbox sesi akan di-generate oleh JavaScript -->
            </div>
            <div style="display: flex; gap: 12px; margin-top: 24px; justify-content: flex-end;">
                <button type="button" class="btn-primary" style="background: transparent; border: 1px solid var(--glass-border); color: var(--text-secondary);" onclick="closeReportModal()">Batal</button>
                <button type="button" class="btn-primary" onclick="submitGenerateReport()" id="generateReportBtn" style="background: #10b981;">
                    <i class="fas fa-file-alt"></i> Generate Raport
                </button>
            </div>
        </div>

        <!-- State 3: Proses generate -->
        <div id="reportProcessing" style="display: none; text-align: center; padding: 40px;">
            <div style="width: 40px; height: 40px; border: 3px solid rgba(16, 185, 129, 0.3); border-top-color: #10b981; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 16px;"></div>
            <p style="color: var(--text-secondary);">Raport sedang digenerate, mohon tunggu...</p>
        </div>

        <!-- State 4: Selesai -->
        <div id="reportDone" style="display: none; text-align: center; padding: 40px;">
            <div style="width: 60px; height: 60px; background: rgba(16, 185, 129, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                <i class="fas fa-check" style="font-size: 1.5rem; color: #10b981;"></i>
            </div>
            <h4 style="margin-bottom: 8px;">Raport Berhasil Digenerate!</h4>
            <a id="viewReportLink" href="#" class="btn-primary" style="display: inline-flex; margin-top: 16px; background: #10b981; text-decoration: none;">
                <i class="fas fa-eye"></i> Lihat Raport
            </a>
        </div>
    </div>
</div>

<!-- Modal History Raport -->
<div class="modal-overlay" id="reportHistoryModal">
    <div class="modal-content glass animate-fade-in" style="max-width: 800px;">
        <div class="modal-header">
            <h3 id="reportHistoryModalTitle">History Raport</h3>
            <button class="close-modal" onclick="closeReportHistoryModal()">&times;</button>
        </div>

        <div id="reportHistoryLoading" style="text-align: center; padding: 40px;">
            <div style="width: 40px; height: 40px; border: 3px solid rgba(59, 130, 246, 0.3); border-top-color: #3b82f6; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 16px;"></div>
            <p style="color: var(--text-secondary);">Memuat history raport...</p>
        </div>

        <div id="reportHistoryContent" style="display: none;">
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tanggal Generate</th>
                            <th>Sesi Ujian</th>
                            <th>Status</th>
                            <th>Admin</th>
                            <th style="width: 100px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="reportHistoryTableBody">
                        <!-- Disisipkan via JS -->
                    </tbody>
                </table>
            </div>
            <div style="display: flex; justify-content: flex-end; margin-top: 24px;">
                <button type="button" class="btn-primary" style="background: transparent; border: 1px solid var(--glass-border); color: var(--text-secondary);" onclick="closeReportHistoryModal()">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Bulk Raport -->
<div class="modal-overlay" id="bulkReportModal">
    <div class="modal-content glass animate-fade-in" style="max-width: 700px;">
        <div class="modal-header">
            <h3 id="bulkReportModalTitle">Bulk Generate Raport</h3>
            <button class="close-modal" onclick="closeBulkReportModal()">&times;</button>
        </div>

        <!-- STEP 1: Pilih Peserta -->
        <div id="bulkStep1">
            <p style="color: var(--text-secondary); margin-bottom: 12px; font-size: 0.9rem;">
                Centang peserta yang ingin dicetak raportnya:
            </p>
            
            <div style="position: relative; margin-bottom: 16px;">
                <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                <input type="text" id="bulkSearchParticipant" class="form-input" placeholder="Cari nama peserta..." style="padding-left: 36px; margin-bottom: 0;" oninput="filterBulkParticipants()">
            </div>
            
            <div style="margin-bottom: 12px;">
                <label style="cursor: pointer; font-size: 0.85rem; color: var(--text-secondary); display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" id="bulkSelectAll" onchange="toggleSelectAllParticipants()" style="width: 16px; height: 16px;">
                    Pilih Semua
                </label>
            </div>
            
            <!-- Loading saat fetch peserta -->
            <div id="bulkParticipantLoading" style="text-align: center; padding: 40px;">
                <div style="width: 40px; height: 40px; border: 3px solid rgba(59, 130, 246, 0.3); border-top-color: #3b82f6; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 16px;"></div>
                <p style="color: var(--text-secondary);">Memuat daftar peserta...</p>
            </div>
            
            <div id="bulkParticipantList" style="max-height: 300px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; display: none;">
            </div>
            
            <div style="display: flex; gap: 12px; margin-top: 24px; justify-content: space-between; align-items: center;">
                <span id="bulkSelectedCount" style="font-size: 0.85rem; color: var(--text-secondary);">0 peserta dipilih</span>
                <div style="display: flex; gap: 12px;">
                    <button type="button" class="btn-primary" style="background: transparent; border: 1px solid var(--glass-border); color: var(--text-secondary);" onclick="closeBulkReportModal()">Batal</button>
                    <button type="button" class="btn-primary" onclick="bulkGoToStep2()" style="background: #3b82f6;">
                        Lanjut <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- STEP 2: Pilih Sesi Ujian -->
        <div id="bulkStep2" style="display: none;">
            <p style="color: var(--text-secondary); margin-bottom: 16px; font-size: 0.9rem;">
                Pilih sesi ujian yang ingin dimasukkan ke raport:
            </p>
            
            <div id="bulkSessionLoading" style="text-align: center; padding: 40px;">
                <div style="width: 40px; height: 40px; border: 3px solid rgba(59, 130, 246, 0.3); border-top-color: #3b82f6; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 16px;"></div>
                <p style="color: var(--text-secondary);">Memuat daftar sesi ujian...</p>
            </div>
            
            <div id="bulkSessionCheckboxes" style="max-height: 300px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px;">
            </div>
            
            <div id="bulkStep2Actions" style="display: none;">
                <div style="display: flex; gap: 12px; margin-top: 24px; justify-content: space-between;">
                    <button type="button" class="btn-primary" style="background: transparent; border: 1px solid var(--glass-border); color: var(--text-secondary);" onclick="bulkBackToStep1()">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </button>
                    <button type="button" class="btn-primary" onclick="bulkSubmitGenerate()" style="background: #10b981;">
                        <i class="fas fa-file-alt"></i> Generate Raport
                    </button>
                </div>
            </div>
        </div>

        <!-- STEP 3: Processing -->
        <div id="bulkStep3" style="display: none;">
            <p style="color: var(--text-secondary); margin-bottom: 16px; font-size: 0.9rem;">
                Raport sedang digenerate, mohon tunggu...
            </p>
            <div id="bulkProgressList" style="display: flex; flex-direction: column; gap: 8px; max-height: 300px; overflow-y: auto;">
            </div>
        </div>

        <!-- STEP 4: Done -->
        <div id="bulkStep4" style="display: none;">
            <div style="text-align: center; margin-bottom: 24px;">
                <div style="width: 60px; height: 60px; background: rgba(16, 185, 129, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                    <i class="fas fa-check" style="font-size: 1.5rem; color: #10b981;"></i>
                </div>
                <h4>Bulk Raport Selesai!</h4>
            </div>
            <div id="bulkResultList" style="display: flex; flex-direction: column; gap: 8px; max-height: 300px; overflow-y: auto;">
            </div>
            <div style="display: flex; justify-content: flex-end; margin-top: 24px;">
                <button type="button" class="btn-primary" onclick="closeBulkReportModal()">Tutup</button>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes spin { to { transform: rotate(360deg); } }
</style>
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

    // ==================== CETAK RAPORT ====================
    let currentReportUserId = null;

    function openReportModal(userId) {
        currentReportUserId = userId;
        const modal = document.getElementById('reportModal');
        modal.classList.add('active');

        // Reset semua state
        document.getElementById('reportLoading').style.display = 'block';
        document.getElementById('reportSessionList').style.display = 'none';
        document.getElementById('reportProcessing').style.display = 'none';
        document.getElementById('reportDone').style.display = 'none';

        // Fetch daftar sesi ujian user ini
        fetch(`/admin/participants/${userId}/report-sessions`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            document.getElementById('reportLoading').style.display = 'none';

            if (data.data.sessions.length === 0) {
                showToast('Peserta ini belum pernah menyelesaikan ujian.', 'error');
                closeReportModal();
                return;
            }

            document.getElementById('reportModalTitle').innerText = 'Cetak Raport: ' + data.data.user_name;

            // Generate checkbox untuk setiap sesi
            const container = document.getElementById('sessionCheckboxes');
            container.innerHTML = '';

            data.data.sessions.forEach(session => {
                const div = document.createElement('div');
                div.style.cssText = 'padding: 12px 16px; border: 1px solid var(--glass-border); border-radius: 12px; display: flex; align-items: center; gap: 12px; cursor: pointer; transition: all 0.2s ease;';

                div.innerHTML = `
                    <input type="checkbox" value="${session.exam_session_id}" id="session_${session.exam_session_id}" style="width: 18px; height: 18px; cursor: pointer;">
                    <label for="session_${session.exam_session_id}" style="cursor: pointer; flex: 1;">
                        <div style="font-weight: 600; font-size: 0.95rem;">${session.session_name}</div>
                        <div style="font-size: 0.75rem; color: var(--text-secondary);">Percobaan: ${session.attempt_count}x</div>
                    </label>
                `;

                // Klik div = toggle checkbox
                div.addEventListener('click', function(e) {
                    if (e.target.tagName !== 'INPUT') {
                        const cb = div.querySelector('input[type="checkbox"]');
                        cb.checked = !cb.checked;
                    }
                });

                container.appendChild(div);
            });

            document.getElementById('reportSessionList').style.display = 'block';
        })
        .catch(err => {
            console.error(err);
            showToast('Gagal memuat data sesi ujian.', 'error');
            closeReportModal();
        });
    }

    function closeReportModal() {
        document.getElementById('reportModal').classList.remove('active');
    }

    function submitGenerateReport() {
        // Kumpulkan semua checkbox yang dicentang
        const checkboxes = document.querySelectorAll('#sessionCheckboxes input[type="checkbox"]:checked');
        const sessionIds = Array.from(checkboxes).map(cb => parseInt(cb.value));

        if (sessionIds.length === 0) {
            showToast('Pilih minimal 1 sesi ujian.', 'error');
            return;
        }

        // Tampilkan state processing
        document.getElementById('reportSessionList').style.display = 'none';
        document.getElementById('reportProcessing').style.display = 'block';

        // Kirim request ke backend
        fetch(`/admin/participants/${currentReportUserId}/generate-report`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ session_ids: sessionIds })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const reportCardId = data.data.report_card_id;
                // Mulai polling status
                pollReportStatus(reportCardId);
            } else {
                showToast(data.message || 'Gagal generate raport.', 'error');
                document.getElementById('reportProcessing').style.display = 'none';
                document.getElementById('reportSessionList').style.display = 'block';
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Terjadi kesalahan sistem.', 'error');
            document.getElementById('reportProcessing').style.display = 'none';
            document.getElementById('reportSessionList').style.display = 'block';
        });
    }

    function pollReportStatus(reportCardId) {
        const interval = setInterval(() => {
            fetch(`/admin/report-cards/${reportCardId}/status`, {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.data.status === 'completed') {
                    clearInterval(interval);
                    document.getElementById('reportProcessing').style.display = 'none';
                    document.getElementById('reportDone').style.display = 'block';
                    document.getElementById('viewReportLink').href = `/admin/report-cards/${reportCardId}/view`;
                } else if (data.data.status === 'failed') {
                    clearInterval(interval);
                    showToast('Gagal generate raport: ' + (data.data.error_message || 'Unknown error'), 'error');
                    closeReportModal();
                }
                // Jika masih 'processing', polling lanjut
            })
            .catch(() => {
                clearInterval(interval);
                showToast('Gagal mengecek status raport.', 'error');
                closeReportModal();
            });
        }, 2000); // Poll setiap 2 detik
    }

    // ==================== HISTORY RAPORT ====================
    function openReportHistoryModal(userId) {
        const modal = document.getElementById('reportHistoryModal');
        modal.classList.add('active');

        document.getElementById('reportHistoryLoading').style.display = 'block';
        document.getElementById('reportHistoryContent').style.display = 'none';

        fetch(`/admin/participants/${userId}/report-history`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            document.getElementById('reportHistoryLoading').style.display = 'none';
            document.getElementById('reportHistoryModalTitle').innerText = 'History Raport: ' + data.data.user_name;

            const tbody = document.getElementById('reportHistoryTableBody');
            tbody.innerHTML = '';

            if (data.data.reports.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: var(--text-secondary); padding: 20px;">Belum ada history raport.</td></tr>`;
            } else {
                data.data.reports.forEach(report => {
                    let statusBadge = '';
                    if (report.status === 'completed') {
                        statusBadge = '<span class="badge" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">Selesai</span>';
                    } else if (report.status === 'failed') {
                        statusBadge = '<span class="badge" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">Gagal</span>';
                    } else {
                        statusBadge = '<span class="badge" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">Diproses</span>';
                    }

                    let actionBtn = report.status === 'completed' 
                        ? `<a href="/admin/report-cards/${report.id}/view" target="_blank" class="btn-primary" style="background: #10b981; text-decoration: none; padding: 6px 12px; font-size: 0.8rem; display: inline-flex; border-radius: 6px;"><i class="fas fa-eye"></i></a>`
                        : '-';

                    tbody.innerHTML += `
                        <tr>
                            <td>${report.created_at}</td>
                            <td><div style="font-size: 0.85rem; max-width: 250px; white-space: normal; line-height: 1.4;">${report.sessions_text}</div></td>
                            <td>${statusBadge}</td>
                            <td>${report.generated_by_name}</td>
                            <td style="text-align: center;">${actionBtn}</td>
                        </tr>
                    `;
                });
            }

            document.getElementById('reportHistoryContent').style.display = 'block';
        })
        .catch(err => {
            console.error(err);
            showToast('Gagal memuat history raport.', 'error');
            closeReportHistoryModal();
        });
    }

    function closeReportHistoryModal() {
        document.getElementById('reportHistoryModal').classList.remove('active');
    }

    // Removed client-side search, handled via backend now

    // ==================== BULK RAPORT ====================
    let bulkAllParticipants = []; // Cache semua peserta
    let bulkSelectedUserIds = [];
    let bulkReportCards = []; // Array {report_card_id, user_id, user_name, status}
    let bulkPollInterval = null;

    function openBulkReportModal() {
        // Reset semua state
        document.getElementById('bulkStep1').style.display = 'block';
        document.getElementById('bulkStep2').style.display = 'none';
        document.getElementById('bulkStep3').style.display = 'none';
        document.getElementById('bulkStep4').style.display = 'none';
        document.getElementById('bulkParticipantLoading').style.display = 'block';
        document.getElementById('bulkParticipantList').style.display = 'none';
        document.getElementById('bulkSearchParticipant').value = '';
        document.getElementById('bulkSelectAll').checked = false;
        document.getElementById('bulkSelectedCount').innerText = '0 peserta dipilih';
        document.getElementById('bulkReportModalTitle').innerText = 'Bulk Generate Raport';
        bulkSelectedUserIds = [];
        bulkReportCards = [];
        if (bulkPollInterval) clearInterval(bulkPollInterval);

        document.getElementById('bulkReportModal').classList.add('active');

        // Fetch semua peserta
        fetch('{{ route("admin.participants.all-basic") }}', {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            bulkAllParticipants = data.data.participants;
            renderBulkParticipantList(bulkAllParticipants);
            document.getElementById('bulkParticipantLoading').style.display = 'none';
            document.getElementById('bulkParticipantList').style.display = 'flex';
        })
        .catch(err => {
            console.error(err);
            showToast('Gagal memuat daftar peserta.', 'error');
            closeBulkReportModal();
        });
    }

    function closeBulkReportModal() {
        document.getElementById('bulkReportModal').classList.remove('active');
        if (bulkPollInterval) clearInterval(bulkPollInterval);
    }

    function renderBulkParticipantList(participants) {
        const container = document.getElementById('bulkParticipantList');
        container.innerHTML = '';

        if (participants.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: var(--text-secondary); padding: 20px;">Tidak ada peserta ditemukan.</p>';
            return;
        }

        participants.forEach(p => {
            const div = document.createElement('div');
            div.className = 'bulk-participant-item';
            div.dataset.name = p.name.toLowerCase();
            div.style.cssText = 'padding: 12px 16px; border: 1px solid var(--glass-border); border-radius: 12px; display: flex; align-items: center; gap: 12px; cursor: pointer; transition: all 0.2s ease;';
            div.innerHTML = `
                <input type="checkbox" value="${p.id}" class="bulk-participant-cb" style="width: 18px; height: 18px; cursor: pointer;" onchange="updateBulkSelectedCount()">
                <div style="flex: 1;">
                    <div style="font-weight: 600; font-size: 0.95rem;">${p.name}</div>
                    <div style="font-size: 0.75rem; color: var(--text-secondary);">${p.email}</div>
                </div>
            `;
            div.addEventListener('click', function(e) {
                if (e.target.tagName !== 'INPUT') {
                    const cb = div.querySelector('input[type="checkbox"]');
                    cb.checked = !cb.checked;
                    updateBulkSelectedCount();
                }
            });
            container.appendChild(div);
        });
    }

    function filterBulkParticipants() {
        const query = document.getElementById('bulkSearchParticipant').value.toLowerCase();
        const items = document.querySelectorAll('.bulk-participant-item');
        items.forEach(item => {
            item.style.display = item.dataset.name.includes(query) ? 'flex' : 'none';
        });
    }

    function toggleSelectAllParticipants() {
        const isChecked = document.getElementById('bulkSelectAll').checked;
        const checkboxes = document.querySelectorAll('.bulk-participant-cb');
        checkboxes.forEach(cb => {
            // Hanya toggle yang visible (tidak ter-filter)
            if (cb.closest('.bulk-participant-item').style.display !== 'none') {
                cb.checked = isChecked;
            }
        });
        updateBulkSelectedCount();
    }

    function updateBulkSelectedCount() {
        const count = document.querySelectorAll('.bulk-participant-cb:checked').length;
        document.getElementById('bulkSelectedCount').innerText = count + ' peserta dipilih';
    }

    function bulkGoToStep2() {
        const checkboxes = document.querySelectorAll('.bulk-participant-cb:checked');
        bulkSelectedUserIds = Array.from(checkboxes).map(cb => parseInt(cb.value));

        if (bulkSelectedUserIds.length === 0) {
            showToast('Pilih minimal 1 peserta.', 'error');
            return;
        }

        // Pindah ke step 2
        document.getElementById('bulkStep1').style.display = 'none';
        document.getElementById('bulkStep2').style.display = 'block';
        document.getElementById('bulkSessionLoading').style.display = 'block';
        document.getElementById('bulkSessionCheckboxes').innerHTML = '';
        document.getElementById('bulkStep2Actions').style.display = 'none';
        document.getElementById('bulkReportModalTitle').innerText = `Bulk Raport (${bulkSelectedUserIds.length} peserta)`;

        // Build query string
        const params = bulkSelectedUserIds.map(id => `user_ids[]=${id}`).join('&');

        fetch(`{{ route('admin.participants.bulk-report-sessions') }}?${params}`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            document.getElementById('bulkSessionLoading').style.display = 'none';

            if (data.data.sessions.length === 0) {
                document.getElementById('bulkSessionCheckboxes').innerHTML = '<p style="text-align: center; color: var(--text-secondary); padding: 20px;">Tidak ada sesi ujian yang dikerjakan oleh semua peserta terpilih.</p>';
                document.getElementById('bulkStep2Actions').style.display = 'block';
                return;
            }

            const container = document.getElementById('bulkSessionCheckboxes');
            data.data.sessions.forEach(session => {
                const div = document.createElement('div');
                div.style.cssText = 'padding: 12px 16px; border: 1px solid var(--glass-border); border-radius: 12px; display: flex; align-items: center; gap: 12px; cursor: pointer; transition: all 0.2s ease;';
                div.innerHTML = `
                    <input type="checkbox" value="${session.id}" class="bulk-session-cb" style="width: 18px; height: 18px; cursor: pointer;">
                    <label style="cursor: pointer; flex: 1;">
                        <div style="font-weight: 600; font-size: 0.95rem;">${session.name}</div>
                    </label>
                `;
                div.addEventListener('click', function(e) {
                    if (e.target.tagName !== 'INPUT') {
                        const cb = div.querySelector('input[type="checkbox"]');
                        cb.checked = !cb.checked;
                    }
                });
                container.appendChild(div);
            });

            document.getElementById('bulkStep2Actions').style.display = 'block';
        })
        .catch(err => {
            console.error(err);
            showToast('Gagal memuat daftar sesi ujian.', 'error');
            bulkBackToStep1();
        });
    }

    function bulkBackToStep1() {
        document.getElementById('bulkStep2').style.display = 'none';
        document.getElementById('bulkStep1').style.display = 'block';
        document.getElementById('bulkReportModalTitle').innerText = 'Bulk Generate Raport';
    }

    function bulkSubmitGenerate() {
        const sessionCheckboxes = document.querySelectorAll('.bulk-session-cb:checked');
        const sessionIds = Array.from(sessionCheckboxes).map(cb => parseInt(cb.value));

        if (sessionIds.length === 0) {
            showToast('Pilih minimal 1 sesi ujian.', 'error');
            return;
        }

        // Pindah ke step 3 (processing)
        document.getElementById('bulkStep2').style.display = 'none';
        document.getElementById('bulkStep3').style.display = 'block';
        document.getElementById('bulkReportModalTitle').innerText = 'Memproses Raport...';

        // Buat progress list per peserta
        const progressList = document.getElementById('bulkProgressList');
        progressList.innerHTML = '';
        bulkSelectedUserIds.forEach(uid => {
            const participant = bulkAllParticipants.find(p => p.id === uid);
            const div = document.createElement('div');
            div.id = `bulk-progress-${uid}`;
            div.style.cssText = 'padding: 12px 16px; border: 1px solid var(--glass-border); border-radius: 12px; display: flex; align-items: center; gap: 12px;';
            div.innerHTML = `
                <div style="width: 24px; height: 24px; border: 3px solid rgba(59, 130, 246, 0.3); border-top-color: #3b82f6; border-radius: 50%; animation: spin 1s linear infinite; flex-shrink: 0;" class="bulk-spinner"></div>
                <div style="flex: 1;">
                    <div style="font-weight: 600; font-size: 0.95rem;">${participant ? participant.name : 'User #' + uid}</div>
                    <div style="font-size: 0.75rem; color: var(--text-secondary);" class="bulk-status-text">Memproses...</div>
                </div>
            `;
            progressList.appendChild(div);
        });

        // POST ke backend
        fetch('{{ route("admin.participants.bulk-generate-report") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ user_ids: bulkSelectedUserIds, session_ids: sessionIds })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                bulkReportCards = data.data.report_cards.map(rc => ({
                    ...rc,
                    status: 'processing'
                }));
                pollBulkReportStatus();
            } else {
                showToast(data.message || 'Gagal generate raport.', 'error');
                closeBulkReportModal();
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Terjadi kesalahan sistem.', 'error');
            closeBulkReportModal();
        });
    }

    function pollBulkReportStatus() {
        bulkPollInterval = setInterval(() => {
            const pending = bulkReportCards.filter(rc => rc.status === 'processing');

            if (pending.length === 0) {
                clearInterval(bulkPollInterval);
                showBulkResults();
                return;
            }

            pending.forEach(rc => {
                fetch(`/admin/report-cards/${rc.report_card_id}/status`, {
                    headers: { 'Accept': 'application/json' }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.data.status === 'completed' || data.data.status === 'failed') {
                        rc.status = data.data.status;
                        rc.error_message = data.data.error_message;
                        updateBulkProgressItem(rc);

                        // Cek apakah semua selesai
                        const stillPending = bulkReportCards.filter(r => r.status === 'processing');
                        if (stillPending.length === 0) {
                            clearInterval(bulkPollInterval);
                            setTimeout(() => showBulkResults(), 500);
                        }
                    }
                })
                .catch(() => {});
            });
        }, 2000);
    }

    function updateBulkProgressItem(rc) {
        const div = document.getElementById(`bulk-progress-${rc.user_id}`);
        if (!div) return;

        const spinner = div.querySelector('.bulk-spinner');
        const statusText = div.querySelector('.bulk-status-text');

        if (rc.status === 'completed') {
            spinner.style.animation = 'none';
            spinner.style.border = 'none';
            spinner.innerHTML = '<i class="fas fa-check-circle" style="color: #10b981; font-size: 1.2rem;"></i>';
            statusText.innerText = 'Selesai';
            statusText.style.color = '#10b981';
        } else if (rc.status === 'failed') {
            spinner.style.animation = 'none';
            spinner.style.border = 'none';
            spinner.innerHTML = '<i class="fas fa-times-circle" style="color: #ef4444; font-size: 1.2rem;"></i>';
            statusText.innerText = 'Gagal: ' + (rc.error_message || 'Unknown error');
            statusText.style.color = '#ef4444';
        }
    }

    function showBulkResults() {
        document.getElementById('bulkStep3').style.display = 'none';
        document.getElementById('bulkStep4').style.display = 'block';
        document.getElementById('bulkReportModalTitle').innerText = 'Bulk Raport Selesai';

        const resultList = document.getElementById('bulkResultList');
        resultList.innerHTML = '';

        const completed = bulkReportCards.filter(rc => rc.status === 'completed');
        const failed = bulkReportCards.filter(rc => rc.status === 'failed');

        completed.forEach(rc => {
            const div = document.createElement('div');
            div.style.cssText = 'padding: 12px 16px; border: 1px solid #10b981; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; background: rgba(16, 185, 129, 0.05);';
            div.innerHTML = `
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-check-circle" style="color: #10b981;"></i>
                    <span style="font-weight: 600;">${rc.user_name}</span>
                </div>
                <a href="/admin/report-cards/${rc.report_card_id}/view" target="_blank" class="btn-primary" style="background: #10b981; text-decoration: none; padding: 6px 16px; font-size: 0.8rem; border-radius: 8px;">
                    <i class="fas fa-eye"></i> Lihat
                </a>
            `;
            resultList.appendChild(div);
        });

        failed.forEach(rc => {
            const div = document.createElement('div');
            div.style.cssText = 'padding: 12px 16px; border: 1px solid #ef4444; border-radius: 12px; display: flex; align-items: center; gap: 12px; background: rgba(239, 68, 68, 0.05);';
            div.innerHTML = `
                <i class="fas fa-times-circle" style="color: #ef4444;"></i>
                <div>
                    <span style="font-weight: 600;">${rc.user_name}</span>
                    <div style="font-size: 0.75rem; color: #ef4444;">${rc.error_message || 'Gagal generate'}</div>
                </div>
            `;
            resultList.appendChild(div);
        });
    }
</script>
@endpush
