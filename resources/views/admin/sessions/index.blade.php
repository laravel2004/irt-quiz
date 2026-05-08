@extends('layouts.admin')

@section('title', 'Sesi Ujian')
@section('header_title', 'Manajemen Sesi Ujian')

@section('content')
<div class="glass animate-fade-in" style="padding: 32px; margin-bottom: 24px;">
    <div class="flex-stack-mobile" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; gap: 20px;">
        <div>
            <h3 style="font-family: 'Outfit', sans-serif; margin-bottom: 4px;">Sesi Ujian</h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Kelola jadwal dan pembagian soal untuk ujian.</p>
        </div>
        <div class="flex-stack-mobile" style="display: flex; gap: 16px;">
            <div style="position: relative; width: 300px;">
                <i class="fas fa-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                <input type="text" id="searchInput" class="form-input" placeholder="Cari sesi..." style="padding-left: 44px; margin-bottom: 0;">
            </div>
            <button class="btn-primary" onclick="openSessionModal('create')">
                <i class="fas fa-plus"></i> Tambah Sesi
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>NAMA SESI</th>
                    <th>TANGGAL</th>
                    <th>WAKTU</th>
                    <th>DURASI</th>
                    <th>SOAL</th>
                    <th>KATEGORI</th>
                    <th>STATUS</th>
                    <th style="width: 150px; text-align: center;">AKSI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sessions as $session)
                <tr>
                    <td>
                        <div style="font-weight: 600;">{{ $session->name }}</div>
                        <code style="font-size: 0.75rem; color: var(--accent);">{{ $session->code }}</code>
                    </td>
                    <td>
                        <div style="font-size: 0.85rem;">{{ $session->start_date }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-secondary);">s/d {{ $session->end_date }}</div>
                    </td>
                    <td>{{ substr($session->start_time, 0, 5) }} - {{ substr($session->end_time, 0, 5) }}</td>
                    <td>{{ $session->duration }} mnt</td>
                    <td>{{ $session->total_questions }} btr</td>
                    <td>
                        <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                            @foreach($session->sessionCategories as $sc)
                                <span class="badge" style="font-size: 0.7rem; background: rgba(255,255,255,0.05);">
                                    {{ data_get($sc->category, 'name') }} ({{ $sc->percentage }}%)
                                </span>
                            @endforeach
                        </div>
                    </td>
                    <td>
                        <span class="badge {{ $session->is_active ? 'active' : '' }}">
                            {{ $session->is_active ? 'Terbuka' : 'Tertutup' }}
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <a href="{{ route('admin.sessions.show', $session->id) }}" class="btn-icon" title="Lihat Detail">
                            <i class="fas fa-eye"></i>
                        </a>
                        <button class="btn-icon {{ $session->is_active ? 'delete' : '' }}" onclick="toggleStatus({{ $session->id }})" title="{{ $session->is_active ? 'Tutup Sesi' : 'Buka Sesi' }}" style="color: {{ $session->is_active ? '#ef4444' : '#10b981' }};">
                            <i class="fas {{ $session->is_active ? 'fa-lock' : 'fa-lock-open' }}"></i>
                        </button>
                        <button class="btn-icon" onclick="editSession({{ $session->id }})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon delete" onclick="deleteSession({{ $session->id }})" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-secondary);">Belum ada sesi ujian.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Session Modal -->
<div class="modal-overlay" id="sessionModal">
    <div class="modal-content glass animate-fade-in" style="max-width: 900px;">
        <div class="modal-header">
            <h3 id="modalTitle">Tambah Sesi Baru</h3>
            <button class="close-modal" onclick="closeSessionModal()">&times;</button>
        </div>
        <form id="sessionForm">
            @csrf
            <input type="hidden" id="sessionId">
            
            <div class="responsive-grid">
                <!-- Left Column -->
                <div>
                    <div class="form-group">
                        <label>Nama Sesi</label>
                        <input type="text" name="name" id="sName" class="form-input" placeholder="Contoh: Try Out Akbar Nasional" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label>Tanggal Mulai</label>
                            <input type="date" name="start_date" id="sStartDate" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label>Tanggal Selesai</label>
                            <input type="date" name="end_date" id="sEndDate" class="form-input" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label>Waktu Mulai</label>
                            <input type="time" name="start_time" id="sStartTime" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label>Waktu Selesai</label>
                            <input type="time" name="end_time" id="sEndTime" class="form-input" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label>Waktu Pengerjaan (Menit)</label>
                            <input type="number" name="duration" id="sDuration" class="form-input" placeholder="90" required>
                        </div>
                        <div class="form-group">
                            <label>Jumlah Soal</label>
                            <input type="number" name="total_questions" id="sTotalQuestions" class="form-input" placeholder="50" required>
                        </div>
                    </div>
                </div>

                <!-- Right Column (Categories & Percentage) -->
                <div>
                    <div class="form-group">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <label style="margin-bottom: 0;">Pembagian Kategori Soal</label>
                            <button type="button" class="btn-primary" style="padding: 4px 12px; font-size: 0.8rem; background: var(--accent);" onclick="addCategoryRow()">
                                <i class="fas fa-plus"></i> Kategori
                            </button>
                        </div>
                        <div id="sessionCategoriesList" style="display: flex; flex-direction: column; gap: 12px;">
                            <!-- Dynamic Category Rows -->
                        </div>
                        
                        <div style="margin-top: 20px; padding: 12px; border-top: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.9rem; color: var(--text-secondary);">Total Persentase:</span>
                            <span id="totalPercentageDisplay" style="font-weight: 600; color: #ef4444;">0%</span>
                        </div>
                        <p id="percentageWarning" style="color: #ef4444; font-size: 0.75rem; display: none;">Total persentase harus 100%!</p>
                    </div>
                </div>
            </div>

            <div class="flex-stack-mobile" style="display: flex; gap: 12px; margin-top: 32px; justify-content: flex-end;">
                <button type="button" class="btn-primary" style="background: transparent; border: 1px solid var(--glass-border); color: var(--text-secondary);" onclick="closeSessionModal()">Batal</button>
                <button type="submit" class="btn-primary">Simpan Sesi</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const sessionModal = document.getElementById('sessionModal');
    const sessionForm = document.getElementById('sessionForm');
    const categoriesList = document.getElementById('sessionCategoriesList');
    const totalDisplay = document.getElementById('totalPercentageDisplay');
    const percentageWarning = document.getElementById('percentageWarning');
    let mode = 'create';

    const allCategories = @json($categories);

    function openSessionModal(m) {
        mode = m;
        document.getElementById('modalTitle').innerText = m === 'create' ? 'Tambah Sesi Baru' : 'Edit Sesi';
        document.getElementById('sessionId').value = '';
        sessionForm.reset();
        categoriesList.innerHTML = '';
        if (m === 'create') addCategoryRow();
        updateTotalPercentage();
        sessionModal.classList.add('active');
    }

    function closeSessionModal() {
        sessionModal.classList.remove('active');
    }

    function addCategoryRow(categoryId = '', percentage = '') {
        const div = document.createElement('div');
        div.className = 'glass';
        div.style.padding = '12px';
        div.style.display = 'flex';
        div.style.alignItems = 'center';
        div.style.gap = '12px';
        div.style.borderRadius = '8px';

        let options = allCategories.map(c => `<option value="${c.id}" ${c.id == categoryId ? 'selected' : ''}>${c.name}</option>`).join('');

        div.innerHTML = `
            <select class="form-input cat-select" style="margin-bottom: 0; flex: 2;" required>
                <option value="">Pilih Kategori</option>
                ${options}
            </select>
            <div style="display: flex; align-items: center; gap: 8px; flex: 1;">
                <input type="number" class="form-input percentage-input" value="${percentage}" placeholder="%" style="margin-bottom: 0; text-align: center;" min="1" max="100" oninput="updateTotalPercentage()" required>
                <span style="color: var(--text-secondary);">%</span>
            </div>
            <button type="button" class="btn-icon delete" onclick="this.parentElement.remove(); updateTotalPercentage();" style="border:none; background:none;">
                <i class="fas fa-times"></i>
            </button>
        `;
        categoriesList.appendChild(div);
        updateTotalPercentage();
    }

    function updateTotalPercentage() {
        const inputs = document.querySelectorAll('.percentage-input');
        let total = 0;
        inputs.forEach(input => {
            total += parseInt(input.value) || 0;
        });
        
        totalDisplay.innerText = total + '%';
        if (total === 100) {
            totalDisplay.style.color = '#10b981';
            percentageWarning.style.display = 'none';
        } else {
            totalDisplay.style.color = '#ef4444';
            percentageWarning.style.display = 'block';
        }
        return total;
    }

    sessionForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (updateTotalPercentage() !== 100) {
            showToast('Total persentase kategori harus 100%!', 'error');
            return;
        }

        const id = document.getElementById('sessionId').value;
        const url = mode === 'create' ? "{{ route('admin.sessions.store') }}" : `/admin/sessions/${id}`;
        
        // Build the categories array for submission
        const categories = [];
        document.querySelectorAll('#sessionCategoriesList > div').forEach(row => {
            categories.push({
                id: row.querySelector('.cat-select').value,
                percentage: row.querySelector('.percentage-input').value
            });
        });

        const data = {
            name: document.getElementById('sName').value,
            start_date: document.getElementById('sStartDate').value,
            end_date: document.getElementById('sEndDate').value,
            start_time: document.getElementById('sStartTime').value,
            end_time: document.getElementById('sEndTime').value,
            duration: document.getElementById('sDuration').value,
            total_questions: document.getElementById('sTotalQuestions').value,
            categories: categories
        };

        if (mode === 'edit') data['_method'] = 'PUT';

        fetch(url, {
            method: 'POST',
            body: JSON.stringify(data),
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showToast(data.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message || 'Gagal menyimpan sesi', 'error');
            }
        });
    });

    function deleteSession(id) {
        customConfirm('Hapus sesi ujian ini? Data hasil ujian juga mungkin terpengaruh.', function() {
            fetch(`/admin/sessions/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast('Sesi berhasil dihapus');
                    setTimeout(() => location.reload(), 500);
                }
            });
        });
    }

    function toggleStatus(id) {
        fetch(`/admin/sessions/${id}/toggle-status`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showToast(data.message);
                setTimeout(() => location.reload(), 500);
            }
        });
    }

    function editSession(id) {
        fetch(`/admin/sessions/${id}`, {
            headers: { 
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => {
            if (!res.ok) {
                return res.text().then(text => { throw new Error(text) });
            }
            return res.json();
        })
        .then(data => {
            const s = data.data;
            openSessionModal('edit');
            document.getElementById('sessionId').value = s.id;
            document.getElementById('sName').value = s.name;
            document.getElementById('sStartDate').value = s.start_date;
            document.getElementById('sEndDate').value = s.end_date;
            
            // Format time to HH:mm for time input
            document.getElementById('sStartTime').value = s.start_time ? s.start_time.substring(0, 5) : '';
            document.getElementById('sEndTime').value = s.end_time ? s.end_time.substring(0, 5) : '';
            
            document.getElementById('sDuration').value = s.duration;
            document.getElementById('sTotalQuestions').value = s.total_questions;
            
            categoriesList.innerHTML = '';
            // Handle both camelCase and snake_case relationship names
            const cats = s.session_categories || s.sessionCategories || [];
            if (cats.length > 0) {
                cats.forEach(sc => {
                    addCategoryRow(sc.category_id, sc.percentage);
                });
            } else {
                addCategoryRow();
            }
            updateTotalPercentage();
        })
        .catch(err => {
            console.error(err);
            showToast('Gagal mengambil data sesi', 'error');
        });
    }

    // Client-side Search Filter
    document.getElementById('searchInput').addEventListener('input', function(e) {
        const term = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('.data-table tbody tr');

        rows.forEach(row => {
            if (row.cells.length < 7) return; // Skip empty row
            const name = row.cells[0].textContent.toLowerCase();
            
            if (name.includes(term)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
</script>
@endpush
