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

<div class="glass animate-fade-in" style="padding: 32px; margin-bottom: 24px;">
    <div class="flex-stack-mobile" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; gap: 20px;">
        <div>
            <h3 style="font-family: 'Outfit', sans-serif; margin-bottom: 4px;">Sesi Ujian</h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Kelola jadwal dan pembagian soal untuk ujian.</p>
        </div>
        <div class="flex-stack-mobile search-container" style="display: flex; gap: 16px; width: 100%; max-width: 400px; align-items: center;">
            <form method="GET" action="{{ route('admin.sessions.index') }}" style="position: relative; width: 100%; margin: 0; display: flex; gap: 8px;">
                <div style="position: relative; width: 100%;">
                    <i class="fas fa-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                    <input type="text" name="search" id="searchInput" class="form-input" placeholder="Cari sesi (tekan enter)..." value="{{ request('search') }}" style="padding-left: 44px; margin-bottom: 0; width: 100%;">
                </div>
                @if(request('search'))
                    <a href="{{ route('admin.sessions.index') }}" class="btn-primary" style="background: #f1f5f9; color: var(--text-primary); border: 1px solid var(--glass-border); padding: 0 16px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; border-radius: 8px;">
                        Reset
                    </a>
                @endif
            </form>
            @if(auth()->user()->role === 'superadmin')
            <button class="btn-primary" onclick="openSessionModal('create')" style="flex-shrink: 0;">
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
    <div style="margin-top: 24px; display: flex; justify-content: center;">
        {{ $sessions->links() }}
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
    <div style="margin-top: 24px; display: flex; justify-content: center;">
        {{ $sessions->links() }}
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

                    <div class="form-group" style="margin: 16px 0 0;">
                        <label for="sIsLockQuiz" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" id="sIsLockQuiz" style="width: 16px; height: 16px;">
                            <span>Kunci soal untuk sesi ini</span>
                        </label>
                        <small style="display: block; margin-top: 4px; color: var(--text-secondary);">Soal terkunci tidak dapat digunakan oleh sesi ujian berikutnya sampai dibuka kembali.</small>
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

                <div style="background: #ffffff; padding: 20px; border-radius: 12px; border: 1px solid var(--glass-border);">
                    <h4 style="margin-bottom: 8px; font-family: 'Outfit', sans-serif; color: var(--accent);"><i class="fas fa-award"></i> Ambang Predikat IRT</h4>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 16px;">Masukkan nilai minimum setiap predikat berdasarkan total skor IRT sesi.</p>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="predicateKurangMin">Minimum Kurang</label>
                            <input type="number" id="predicateKurangMin" class="form-input" required min="0" step="0.01" oninput="predicateThresholdsTouched = true">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="predicateMemadaiMin">Minimum Memadai</label>
                            <input type="number" id="predicateMemadaiMin" class="form-input" required min="0" step="0.01" oninput="predicateThresholdsTouched = true">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="predicateBaikMin">Minimum Baik</label>
                            <input type="number" id="predicateBaikMin" class="form-input" required min="0" step="0.01" oninput="predicateThresholdsTouched = true">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="predicateIstimewaMin">Minimum Istimewa</label>
                            <input type="number" id="predicateIstimewaMin" class="form-input" required min="0" step="0.01" oninput="predicateThresholdsTouched = true">
                        </div>
                    </div>
                    <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 14px; color: var(--text-secondary); font-size: 0.85rem;">
                        <span>Total batas bawah: <strong id="totalMinIrt" style="color: #0f172a;">0.00</strong></span>
                        <span>Total batas atas: <strong id="totalMaxIrt" style="color: #0f172a;">0.00</strong></span>
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
    let predicateThresholdsTouched = false;

    const allCategories = @json($categories);
    let availabilityCounts = initialAvailabilityCounts();

    function initialAvailabilityCounts() {
        return Object.fromEntries(allCategories.flatMap(category =>
            (category.sub_categories || []).map(subCategory => [
                subCategory.id,
                Number(subCategory.available_questions_count || 0)
            ])
        ));
    }

    function openSessionModal(m) {
        mode = m;
        document.getElementById('modalTitle').innerText = m === 'create' ? 'Tambah Sesi Baru' : 'Edit Sesi';
        document.getElementById('sessionId').value = '';
        sessionForm.reset();
        document.getElementById('sIsLockQuiz').checked = false;
        categoriesList.innerHTML = '';
        categoryIndexCounter = 0;
        predicateThresholdsTouched = false;
        availabilityCounts = initialAvailabilityCounts();
        
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
            `<option value="${s.id}" ${s.id == selectedSubId ? 'selected' : ''}>${s.name} (${availableQuestionCount(s.id)} soal tersedia)</option>`
        ).join('');
    }

    function availableQuestionCount(subCategoryId) {
        return Number(availabilityCounts[subCategoryId] || 0);
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

        updateSubEstimates(catIndex);
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
        const minIrt = data ? data.min_score_irt : 0;
        const msi = data ? data.max_score_irt : 1000;

        let options = allCategories.map(c => `<option value="${c.id}" ${c.id == catId ? 'selected' : ''}>${c.name}</option>`).join('');

        div.innerHTML = `
            <button type="button" class="btn-icon delete" aria-label="Hapus mata pelajaran" onclick="this.parentElement.remove(); updateIrtTotals();" style="position: absolute; right: 10px; top: 10px; border:none; background:none;">
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
                    <input type="number" class="form-input cat-questions" value="${tq}" required min="1" oninput="updateSubEstimates(${idx})">
                </div>
                <div>
                    <label style="font-size: 0.8rem;">Skor Raw</label>
                    <input type="number" class="form-input cat-raw" aria-label="Skor raw maksimum" value="${msr}" required min="1">
                </div>
                <div>
                    <label style="font-size: 0.8rem;">Batas Bawah IRT</label>
                    <input type="number" class="form-input cat-irt-min" aria-label="Batas bawah IRT" value="${minIrt}" required min="0" step="0.01" oninput="updateIrtTotals()">
                </div>
                <div>
                    <label style="font-size: 0.8rem;">Batas Atas IRT</label>
                    <input type="number" class="form-input cat-irt" aria-label="Batas atas IRT" value="${msi}" required min="1" step="1" oninput="updateIrtTotals()">
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
        updateIrtTotals();

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
        div.className = 'subcat-row';
        div.style.display = 'grid';
        div.style.gridTemplateColumns = 'minmax(0, 2fr) minmax(150px, 1fr) auto';
        div.style.gap = '8px';
        div.style.alignItems = 'center';

        const options = getSubCategoriesOptions(categoryId, subCatId);

        div.innerHTML = `
            <select class="form-input subcat-select" style="margin-bottom: 0; padding: 6px; font-size: 0.85rem;" onchange="updateSubEstimates(${catIndex})" required>
                <option value="">Pilih Sub Pelajaran</option>
                ${options}
            </select>
            <div style="display: flex; align-items: center; gap: 4px;">
                <input type="number" class="form-input subcat-percentage" value="${percentage}" placeholder="%" style="margin-bottom: 0; padding: 6px; font-size: 0.85rem; text-align: center;" min="1" max="100" oninput="updateSubTotal(${catIndex})" required>
                <span style="color: var(--text-secondary); font-size: 0.85rem;">%</span>
            </div>
            <button type="button" class="btn-icon delete" onclick="this.parentElement.remove(); updateSubTotal(${catIndex});" style="border:none; background:none; padding: 4px;">
                <i class="fas fa-times" style="font-size: 0.85rem;"></i>
            </button>
            <small class="subcat-estimate" style="grid-column: 1 / -1; color: var(--text-secondary);"></small>
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
        updateSubEstimates(catIndex);
        return total;
    }

    function updateSubEstimates(catIndex) {
        const row = document.querySelector(`.category-row[data-index="${catIndex}"]`);
        if (!row) return;

        const totalQuestions = Number(row.querySelector('.cat-questions').value || 0);
        const subRows = [...document.querySelectorAll(`#subCategoryList_${catIndex} .subcat-row`)];
        const percentages = subRows.map((subRow, index) => ({
            index,
            percentage: Number(subRow.querySelector('.subcat-percentage').value || 0),
        }));
        const totalPercentage = percentages.reduce((total, item) => total + item.percentage, 0);

        if (totalQuestions < 1 || totalPercentage !== 100) {
            subRows.forEach(subRow => {
                subRow.querySelector('.subcat-estimate').innerText = 'Masukkan total persentase 100% untuk menghitung jumlah soal.';
            });
            return;
        }

        const allocations = percentages.map(item => {
            const raw = (item.percentage / 100) * totalQuestions;
            return { ...item, count: Math.floor(raw), remainder: raw - Math.floor(raw) };
        });
        let remaining = totalQuestions - allocations.reduce((total, item) => total + item.count, 0);
        [...allocations]
            .sort((left, right) => right.remainder - left.remainder || left.index - right.index)
            .slice(0, remaining)
            .forEach(item => allocations[item.index].count++);

        subRows.forEach((subRow, index) => {
            const subCategoryId = subRow.querySelector('.subcat-select').value;
            const available = availableQuestionCount(subCategoryId);
            const required = allocations[index].count;
            const estimate = subRow.querySelector('.subcat-estimate');
            estimate.innerText = `Total soal yang akan dipakai: ${required} dari ${available} soal tersedia.`;
            estimate.style.color = subCategoryId && required > available ? '#ef4444' : 'var(--text-secondary)';
            estimate.dataset.required = required;
            estimate.dataset.available = available;
        });
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

    function updateIrtTotals() {
        const rows = [...document.querySelectorAll('.category-row')];
        const totalMin = rows.reduce((total, row) => total + Number(row.querySelector('.cat-irt-min')?.value || 0), 0);
        const totalMax = rows.reduce((total, row) => total + Number(row.querySelector('.cat-irt')?.value || 0), 0);
        const range = totalMax - totalMin;

        document.getElementById('totalMinIrt').innerText = totalMin.toFixed(2);
        document.getElementById('totalMaxIrt').innerText = totalMax.toFixed(2);

        if (!predicateThresholdsTouched && range > 0) {
            document.getElementById('predicateKurangMin').value = totalMin.toFixed(2);
            document.getElementById('predicateMemadaiMin').value = (totalMin + (range * 0.50)).toFixed(2);
            document.getElementById('predicateBaikMin').value = (totalMin + (range * 0.70)).toFixed(2);
            document.getElementById('predicateIstimewaMin').value = (totalMin + (range * 0.85)).toFixed(2);
        }

        return { totalMin, totalMax };
    }

    function validateIrtConfiguration() {
        const invalidBounds = [...document.querySelectorAll('.category-row')].some(row =>
            Number(row.querySelector('.cat-irt').value) <= Number(row.querySelector('.cat-irt-min').value)
        );
        if (invalidBounds) {
            showToast('Batas atas IRT harus lebih besar dari batas bawah IRT.', 'error');
            return false;
        }

        const { totalMin, totalMax } = updateIrtTotals();
        const thresholds = [
            Number(document.getElementById('predicateKurangMin').value),
            Number(document.getElementById('predicateMemadaiMin').value),
            Number(document.getElementById('predicateBaikMin').value),
            Number(document.getElementById('predicateIstimewaMin').value),
        ];

        if (Math.abs(thresholds[0] - totalMin) > 0.001) {
            showToast(`Minimum Kurang harus sama dengan total batas bawah IRT (${totalMin.toFixed(2)}).`, 'error');
            return false;
        }
        if (!(thresholds[0] < thresholds[1] && thresholds[1] < thresholds[2] && thresholds[2] < thresholds[3])) {
            showToast('Urutan ambang harus Kurang < Memadai < Baik < Istimewa.', 'error');
            return false;
        }
        if (thresholds[3] > totalMax) {
            showToast(`Minimum Istimewa tidak boleh melebihi total batas atas IRT (${totalMax.toFixed(2)}).`, 'error');
            return false;
        }

        return true;
    }

    sessionForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!validateAllTotals()) {
            showToast('Setiap Mata Pelajaran harus memiliki total persentase Sub Pelajaran 100%!', 'error');
            return;
        }

        if (!validateIrtConfiguration()) {
            return;
        }

        const shortage = [...document.querySelectorAll('.subcat-estimate')].find(estimate =>
            Number(estimate.dataset.required) > Number(estimate.dataset.available)
        );
        if (shortage) {
            showToast(shortage.innerText, 'error');
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
                min_score_irt: row.querySelector('.cat-irt-min').value,
                max_score_irt: row.querySelector('.cat-irt').value,
                sub_categories: []
            };

            const subContainer = document.getElementById(`subCategoryContainer_${idx}`);
            if (subContainer.style.display !== 'none') {
                document.getElementById(`subCategoryList_${idx}`).querySelectorAll('.subcat-row').forEach(subRow => {
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
            is_lock_quiz: document.getElementById('sIsLockQuiz').checked,
            predicate_kurang_min: document.getElementById('predicateKurangMin').value,
            predicate_memadai_min: document.getElementById('predicateMemadaiMin').value,
            predicate_baik_min: document.getElementById('predicateBaikMin').value,
            predicate_istimewa_min: document.getElementById('predicateIstimewaMin').value,
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
        .then(async response => {
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Gagal menyimpan sesi');
            return result;
        })
        .then(data => {
            if (data.status === 'success') {
                showToast(data.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message || 'Gagal menyimpan sesi', 'error');
            }
        })
        .catch(error => showToast(error.message || 'Gagal menyimpan sesi', 'error'));
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
            document.getElementById('sIsLockQuiz').checked = Boolean(s.is_lock_quiz);
            document.getElementById('predicateKurangMin').value = s.predicate_kurang_min;
            document.getElementById('predicateMemadaiMin').value = s.predicate_memadai_min;
            document.getElementById('predicateBaikMin').value = s.predicate_baik_min;
            document.getElementById('predicateIstimewaMin').value = s.predicate_istimewa_min;
            predicateThresholdsTouched = true;
            availabilityCounts = data.data.availabilityCounts || initialAvailabilityCounts();
            
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
                        min_score_irt: sc.min_score_irt,
                        max_score_irt: sc.max_score_irt,
                        sub_categories: sc.sub_categories || sc.subCategories || []
                    };
                    addCategoryRow(mappedData);
                });
            } else {
                addCategoryRow();
            }
            updateIrtTotals();
        })
        .catch(err => {
            console.error(err);
            showToast('Gagal mengambil data sesi', 'error');
        });
    }

    // Removed client-side search, handled via backend now
</script>
@endpush
