@extends('layouts.admin')

@section('title', 'Sesi Ujian')
@section('header_title', 'Manajemen Sesi Ujian')

@section('content')

<style>
    #sessionModal .form-input,
    #sessionModal select.form-input,
    #sessionModal input[type="date"],
    #sessionModal input[type="time"],
    #sessionModal input[type="number"],
    #sessionModal input[type="text"] {
        background: #ffffff !important;
        color: #0f172a !important;
        border: 1px solid var(--glass-border) !important;
    }
    #sessionModal .form-input::placeholder {
        color: #94a3b8 !important;
    }
    #sessionModal .category-row {
        background: #ffffff !important;
        border: 1px solid var(--glass-border) !important;
        border-radius: 8px;
    }
    #sessionModal [id^="subCategoryContainer_"] {
        background: #f8fafc !important;
        border: 1px solid var(--glass-border);
    }
</style>

<div class="glass animate-fade-in" style="padding: 32px; margin-bottom: 24px;">
    <div class="flex-stack-mobile" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; gap: 20px;">
        <div>
            <h3 style="font-family: 'Outfit', sans-serif; margin-bottom: 4px;">Sesi Ujian</h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Kelola jadwal dan pembagian soal untuk ujian.</p>
        </div>
        <div class="flex-stack-mobile search-container" style="display: flex; gap: 16px; width: 100%; max-width: 300px;">
            <div style="position: relative; width: 100%;">
                <i class="fas fa-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                <input type="text" id="searchInput" class="form-input" placeholder="Cari sesi..." style="padding-left: 44px; margin-bottom: 0; width: 100%;">
            </div>
            @if(auth()->user()->role === 'superadmin')
            <button class="btn-primary" onclick="openSessionModal('create')">
                <i class="fas fa-plus"></i> Tambah Sesi
            </button>
            @endif
        </div>
    </div>

    @if(auth()->user()->role === 'superadmin')
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
                    <td>{{ $session->sessionCategories->sum('duration') }} mnt</td>
                    <td>{{ $session->sessionCategories->sum('total_questions') }} btr</td>
                    <td>
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            @foreach($session->sessionCategories as $sc)
                                <span class="badge" style="font-size: 0.7rem; background: rgba(59, 130, 246, 0.1); color: var(--accent); align-self: flex-start;">
                                    {{ data_get($sc->category, 'name') }} ({{ $sc->duration }}m, {{ $sc->total_questions }}q)
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
                    <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-secondary);">Belum ada sesi ujian.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @else
    <div class="responsive-grid" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px;">
        @forelse($sessions as $session)
        <div class="glass card-hover" style="padding: 24px; border-radius: 16px; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                    <span class="badge {{ $session->is_active ? 'active' : '' }}" style="font-size: 0.7rem;">
                        {{ $session->is_active ? 'Sesi Terbuka' : 'Sesi Tertutup' }}
                    </span>
                    <code style="color: var(--accent); font-size: 0.8rem; font-weight: 600;">{{ $session->code }}</code>
                </div>
                <h4 style="font-family: 'Outfit', sans-serif; font-size: 1.2rem; margin-bottom: 12px; color: #0f172a;">{{ $session->name }}</h4>
                
                <div class="flex-stack-mobile" style="display: flex; gap: 12px; margin-bottom: 20px; justify-content: space-between;">
                    <div style="font-size: 0.8rem; color: var(--text-secondary); flex: 1;">
                        <i class="fas fa-calendar-alt" style="width: 16px;"></i> {{ $session->start_date }}
                    </div>
                    <div style="font-size: 0.8rem; color: var(--text-secondary); flex: 1;">
                        <i class="fas fa-clock" style="width: 16px;"></i> {{ $session->sessionCategories->sum('duration') }} Menit
                    </div>
                    <div style="font-size: 0.8rem; color: var(--text-secondary); flex: 1;">
                        <i class="fas fa-list" style="width: 16px;"></i> {{ $session->sessionCategories->sum('total_questions') }} Soal
                    </div>
                </div>
            </div>

            <a href="{{ route('admin.sessions.show', $session->id) }}" class="btn-primary" style="width: 100%; height: 44px; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center;">
                Kelola Sesi <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
            </a>
        </div>
        @empty
        <div style="grid-column: 1 / -1; text-align: center; padding: 60px; color: var(--text-secondary);">
            <i class="fas fa-book-open" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.2;"></i>
            <p>Belum ada sesi ujian yang ditugaskan kepada Anda.</p>
        </div>
        @endforelse
    </div>
    @endif
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
            
            <div style="display: flex; flex-direction: column; gap: 24px;">
                <!-- Basic Info -->
                <div style="background: #ffffff; padding: 20px; border-radius: 12px; border: 1px solid var(--glass-border);">
                    <h4 style="margin-bottom: 16px; font-family: 'Outfit', sans-serif; color: var(--accent);"><i class="fas fa-info-circle"></i> Informasi Dasar Sesi</h4>
                    
                    <div class="form-group">
                        <label>Nama Sesi</label>
                        <input type="text" name="name" id="sName" class="form-input" placeholder="Contoh: Try Out Akbar Nasional" required>
                    </div>

                    <div class="flex-stack-mobile" style="display: flex; gap: 12px; width: 100%;">
                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <label>Tanggal Mulai</label>
                            <input type="date" name="start_date" id="sStartDate" class="form-input" required>
                        </div>
                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <label>Tanggal Selesai</label>
                            <input type="date" name="end_date" id="sEndDate" class="form-input" required>
                        </div>
                    </div>

                    <div class="flex-stack-mobile" style="display: flex; gap: 12px; width: 100%; margin-top: 12px;">
                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <label>Waktu Mulai</label>
                            <input type="time" name="start_time" id="sStartTime" class="form-input" required>
                        </div>
                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <label>Waktu Selesai</label>
                            <input type="time" name="end_time" id="sEndTime" class="form-input" required>
                        </div>
                    </div>
                </div>

                <!-- Subject Configurations -->
                <div style="background: #ffffff; padding: 20px; border-radius: 12px; border: 1px solid var(--glass-border);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <h4 style="margin-bottom: 0; font-family: 'Outfit', sans-serif; color: #10b981;"><i class="fas fa-layer-group"></i> Konfigurasi Mata Pelajaran</h4>
                        <button type="button" class="btn-primary" style="padding: 6px 14px; font-size: 0.85rem;" onclick="addCategoryRow()">
                            <i class="fas fa-plus"></i> Tambah Pelajaran
                        </button>
                    </div>
                    
                    <div id="sessionCategoriesList" style="display: flex; flex-direction: column; gap: 20px;">
                        <!-- Dynamic Category Rows -->
                    </div>
                </div>
            </div>

            <div class="flex-stack-mobile" style="display: flex; gap: 12px; margin-top: 32px; justify-content: flex-end;">
                <button type="button" class="btn-primary" style="background: #ffffff; border: 1px solid var(--glass-border); color: var(--text-primary);" onclick="closeSessionModal()">Batal</button>
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
    let mode = 'create';
    let categoryIndexCounter = 0;

    const allCategories = @json($categories);

    function openSessionModal(m) {
        mode = m;
        document.getElementById('modalTitle').innerText = m === 'create' ? 'Tambah Sesi Baru' : 'Edit Sesi';
        document.getElementById('sessionId').value = '';
        sessionForm.reset();
        categoriesList.innerHTML = '';
        categoryIndexCounter = 0;
        
        if (m === 'create') {
            addCategoryRow();
        }
        sessionModal.classList.add('active');
    }

    function closeSessionModal() {
        sessionModal.classList.remove('active');
    }

    function getSubCategoriesOptions(categoryId, selectedSubId = '') {
        const cat = allCategories.find(c => c.id == categoryId);
        if (!cat || !cat.sub_categories) return '';
        
        return cat.sub_categories.map(s => 
            `<option value="${s.id}" ${s.id == selectedSubId ? 'selected' : ''}>${s.name}</option>`
        ).join('');
    }

    function updateSubCategoryDropdowns(selectElement, catIndex) {
        const categoryId = selectElement.value;
        const container = document.getElementById(`subCategoryContainer_${catIndex}`);
        const list = document.getElementById(`subCategoryList_${catIndex}`);
        
        if (!categoryId) {
            container.style.display = 'none';
            list.innerHTML = '';
            return;
        }

        const cat = allCategories.find(c => c.id == categoryId);
        if (cat && cat.sub_categories && cat.sub_categories.length > 0) {
            container.style.display = 'block';
            if (list.children.length === 0) {
                addSubCategoryRow(catIndex, categoryId);
            } else {
                // Update existing dropdowns
                list.querySelectorAll('.subcat-select').forEach(sel => {
                    const currentVal = sel.value;
                    sel.innerHTML = '<option value="">Pilih Sub Pelajaran</option>' + getSubCategoriesOptions(categoryId, currentVal);
                });
            }
        } else {
            container.style.display = 'none';
            list.innerHTML = '';
        }
    }

    function addCategoryRow(data = null) {
        const idx = categoryIndexCounter++;
        const div = document.createElement('div');
        div.className = 'category-row';
        div.dataset.index = idx;
        div.style.background = '#ffffff';
        div.style.padding = '16px';
        div.style.borderRadius = '8px';
        div.style.border = '1px solid var(--glass-border)';
        div.style.position = 'relative';

        const catId = data ? data.category_id : '';
        const dur = data ? data.duration : '';
        const tq = data ? data.total_questions : '';
        const msr = data ? data.max_score_raw : 100;
        const msi = data ? data.max_score_irt : 1000;

        let options = allCategories.map(c => `<option value="${c.id}" ${c.id == catId ? 'selected' : ''}>${c.name}</option>`).join('');

        div.innerHTML = `
            <button type="button" class="btn-icon delete" onclick="this.parentElement.remove();" style="position: absolute; right: 10px; top: 10px; border:none; background:none;">
                <i class="fas fa-times"></i>
            </button>
            <div class="form-group" style="margin-bottom: 12px; margin-right: 30px;">
                <label>Mata Pelajaran</label>
                <select class="form-input cat-select" onchange="updateSubCategoryDropdowns(this, ${idx})" required>
                    <option value="">Pilih Mata Pelajaran</option>
                    ${options}
                </select>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 12px; margin-bottom: 12px;">
                <div>
                    <label style="font-size: 0.8rem;">Durasi (Mnt)</label>
                    <input type="number" class="form-input cat-duration" value="${dur}" required min="1">
                </div>
                <div>
                    <label style="font-size: 0.8rem;">Jml Soal</label>
                    <input type="number" class="form-input cat-questions" value="${tq}" required min="1">
                </div>
                <div>
                    <label style="font-size: 0.8rem;">Skor Raw</label>
                    <input type="number" class="form-input cat-raw" value="${msr}" required min="1">
                </div>
                <div>
                    <label style="font-size: 0.8rem;">Skor IRT</label>
                    <input type="number" class="form-input cat-irt" value="${msi}" required min="1">
                </div>
            </div>

            <!-- Sub Categories Container -->
            <div id="subCategoryContainer_${idx}" style="background: #f8fafc; padding: 12px; border-radius: 6px; display: ${catId ? 'block' : 'none'};">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <label style="margin-bottom: 0; font-size: 0.85rem; color: var(--text-secondary);">Persentase Sub Mata Pelajaran</label>
                    <button type="button" class="btn-primary" style="padding: 2px 8px; font-size: 0.75rem; background: var(--accent);" onclick="addSubCategoryRow(${idx})">
                        <i class="fas fa-plus"></i> Sub Pelajaran
                    </button>
                </div>
                <div id="subCategoryList_${idx}" style="display: flex; flex-direction: column; gap: 8px;"></div>
                <div style="display: flex; justify-content: flex-end; margin-top: 8px; font-size: 0.8rem;">
                    <span style="color: var(--text-secondary);">Total: </span>
                    <span id="subTotal_${idx}" style="margin-left: 4px; font-weight: 600; color: #ef4444;">0%</span>
                </div>
            </div>
        `;
        categoriesList.appendChild(div);

        // Load existing sub categories if editing
        if (data && data.sub_categories && data.sub_categories.length > 0) {
            const list = document.getElementById(`subCategoryList_${idx}`);
            data.sub_categories.forEach(sub => {
                addSubCategoryRow(idx, catId, sub.sub_category_id, sub.percentage);
            });
            updateSubTotal(idx);
        } else if (catId) {
            updateSubCategoryDropdowns(div.querySelector('.cat-select'), idx);
        }
    }

    function addSubCategoryRow(catIndex, forcedCatId = null, subCatId = '', percentage = '') {
        const list = document.getElementById(`subCategoryList_${catIndex}`);
        const categoryId = forcedCatId || document.querySelector(`.category-row[data-index="${catIndex}"] .cat-select`).value;
        
        if (!categoryId) return;

        const div = document.createElement('div');
        div.style.display = 'flex';
        div.style.gap = '8px';
        div.style.alignItems = 'center';

        const options = getSubCategoriesOptions(categoryId, subCatId);

        div.innerHTML = `
            <select class="form-input subcat-select" style="margin-bottom: 0; flex: 2; padding: 6px; font-size: 0.85rem;" required>
                <option value="">Pilih Sub Pelajaran</option>
                ${options}
            </select>
            <div style="display: flex; align-items: center; gap: 4px; flex: 1;">
                <input type="number" class="form-input subcat-percentage" value="${percentage}" placeholder="%" style="margin-bottom: 0; padding: 6px; font-size: 0.85rem; text-align: center;" min="1" max="100" oninput="updateSubTotal(${catIndex})" required>
                <span style="color: var(--text-secondary); font-size: 0.85rem;">%</span>
            </div>
            <button type="button" class="btn-icon delete" onclick="this.parentElement.remove(); updateSubTotal(${catIndex});" style="border:none; background:none; padding: 4px;">
                <i class="fas fa-times" style="font-size: 0.85rem;"></i>
            </button>
        `;
        list.appendChild(div);
        updateSubTotal(catIndex);
    }

    function updateSubTotal(catIndex) {
        const list = document.getElementById(`subCategoryList_${catIndex}`);
        if (!list) return 0;
        
        const inputs = list.querySelectorAll('.subcat-percentage');
        let total = 0;
        inputs.forEach(input => {
            total += parseInt(input.value) || 0;
        });
        
        const display = document.getElementById(`subTotal_${catIndex}`);
        if (display) {
            display.innerText = total + '%';
            display.style.color = total === 100 ? '#10b981' : '#ef4444';
        }
        return total;
    }

    function validateAllTotals() {
        let isValid = true;
        document.querySelectorAll('.category-row').forEach(row => {
            const idx = row.dataset.index;
            const container = document.getElementById(`subCategoryContainer_${idx}`);
            if (container.style.display !== 'none') {
                const total = updateSubTotal(idx);
                if (total !== 100) {
                    isValid = false;
                }
            }
        });
        return isValid;
    }

    sessionForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!validateAllTotals()) {
            showToast('Setiap Mata Pelajaran harus memiliki total persentase Sub Pelajaran 100%!', 'error');
            return;
        }

        const id = document.getElementById('sessionId').value;
        const url = mode === 'create' ? "{{ route('admin.sessions.store') }}" : `/admin/sessions/${id}`;
        
        // Build the categories array for submission
        const categories = [];
        document.querySelectorAll('.category-row').forEach(row => {
            const idx = row.dataset.index;
            const cat = {
                id: row.querySelector('.cat-select').value,
                duration: row.querySelector('.cat-duration').value,
                total_questions: row.querySelector('.cat-questions').value,
                max_score_raw: row.querySelector('.cat-raw').value,
                max_score_irt: row.querySelector('.cat-irt').value,
                sub_categories: []
            };

            const subContainer = document.getElementById(`subCategoryContainer_${idx}`);
            if (subContainer.style.display !== 'none') {
                document.getElementById(`subCategoryList_${idx}`).querySelectorAll('div[style*="display: flex"]').forEach(subRow => {
                    const subSelect = subRow.querySelector('.subcat-select');
                    const subPercent = subRow.querySelector('.subcat-percentage');
                    if (subSelect && subPercent) {
                        cat.sub_categories.push({
                            id: subSelect.value,
                            percentage: subPercent.value
                        });
                    }
                });
            }

            categories.push(cat);
        });

        if (categories.length === 0) {
            showToast('Harap tambahkan minimal 1 mata pelajaran!', 'error');
            return;
        }

        const data = {
            name: document.getElementById('sName').value,
            start_date: document.getElementById('sStartDate').value,
            end_date: document.getElementById('sEndDate').value,
            start_time: document.getElementById('sStartTime').value,
            end_time: document.getElementById('sEndTime').value,
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
            const s = data.data.session;
            openSessionModal('edit');
            document.getElementById('sessionId').value = s.id;
            document.getElementById('sName').value = s.name;
            document.getElementById('sStartDate').value = s.start_date;
            document.getElementById('sEndDate').value = s.end_date;
            
            // Format time to HH:mm for time input
            document.getElementById('sStartTime').value = s.start_time ? s.start_time.substring(0, 5) : '';
            document.getElementById('sEndTime').value = s.end_time ? s.end_time.substring(0, 5) : '';
            
            categoriesList.innerHTML = '';
            // Handle both camelCase and snake_case relationship names
            const cats = s.session_categories || s.sessionCategories || [];
            if (cats.length > 0) {
                cats.forEach(sc => {
                    const mappedData = {
                        category_id: sc.category_id,
                        duration: sc.duration,
                        total_questions: sc.total_questions,
                        max_score_raw: sc.max_score_raw,
                        max_score_irt: sc.max_score_irt,
                        sub_categories: sc.sub_categories || sc.subCategories || []
                    };
                    addCategoryRow(mappedData);
                });
            } else {
                addCategoryRow();
            }
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
