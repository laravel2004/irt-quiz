@extends('layouts.admin')

@section('title', 'Sub Mata Pelajaran')
@section('header_title', 'Manajemen Sub Mata Pelajaran')

@section('content')
<div class="glass animate-fade-in" style="padding: 32px; margin-bottom: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
        <div>
            <h3 style="font-family: 'Outfit', sans-serif; margin-bottom: 4px;">Daftar Sub Pelajaran</h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Kelola sub-materi untuk {{ $category ? 'Mata Pelajaran: ' . $category->name : 'Semua Mata Pelajaran' }}.</p>
        </div>
        <button class="btn-primary" onclick="openModal('create')">
            <i class="fas fa-plus"></i> Tambah Sub Pelajaran
        </button>
    </div>

    @if($category)
        <div style="margin-bottom: 20px;">
            <a href="{{ route('admin.categories.index') }}" class="btn-icon" style="text-decoration: none; display: inline-flex; width: auto; padding: 8px 16px; border-radius: 8px;">
                <i class="fas fa-arrow-left"></i> Kembali ke Daftar Mata Pelajaran
            </a>
        </div>
    @endif

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 80px;">ID</th>
                    <th>MATA PELAJARAN INDUK</th>
                    <th>NAMA SUB PELAJARAN</th>
                    <th style="width: 150px; text-align: center;">AKSI</th>
                </tr>
            </thead>
            <tbody id="subCategoryTableBody">
                @forelse($subCategories as $subCategory)
                <tr id="row-{{ $subCategory->id }}">
                    <td>#{{ $subCategory->id }}</td>
                    <td><span class="badge" style="background: rgba(59, 130, 246, 0.1); color: var(--accent);">{{ $subCategory->category->name }}</span></td>
                    <td class="cat-name" id="sub-name-{{ $subCategory->id }}">{{ $subCategory->name }}</td>
                    <td style="text-align: center;">
                        <a href="{{ route('admin.sub-categories.show', $subCategory->id) }}" class="btn-icon" title="Detail & Bank Soal">
                            <i class="fas fa-eye"></i>
                        </a>
                        <button class="btn-icon" onclick="openEditModal({{ $subCategory->id }}, '{{ addslashes($subCategory->name) }}', {{ $subCategory->category_id }})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon delete" onclick="deleteSubCategory({{ $subCategory->id }})" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                        Belum ada sub pelajaran yang ditambahkan.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Create/Edit Modal -->
<div class="modal-overlay" id="subCategoryModal">
    <div class="modal-content glass animate-fade-in" style="max-width: 600px;">
        <div class="modal-header">
            <h3 id="modalTitle">Tambah Sub Pelajaran</h3>
            <button class="close-modal" onclick="closeModal()">&times;</button>
        </div>
        <form id="subCategoryForm">
            @csrf
            <input type="hidden" id="subCategoryId">
            <div class="form-group">
                <label for="catSelect">Mata Pelajaran Induk</label>
                <select id="catSelect" class="form-input" required>
                    <option value="">-- Pilih Mata Pelajaran --</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ ($category && $category->id == $cat->id) ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="subCatName">Nama Sub Pelajaran</label>
                <input type="text" id="subCatName" name="name" class="form-input" placeholder="Contoh: Aljabar" required>
            </div>
            <div style="display: flex; gap: 12px; margin-top: 32px;">
                <button type="button" class="btn-primary" style="background: transparent; border: 1px solid var(--glass-border); color: var(--text-secondary);" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-primary" style="flex: 1;">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const modal = document.getElementById('subCategoryModal');
    const form = document.getElementById('subCategoryForm');
    let currentMode = 'create';

    function openModal(mode) {
        currentMode = mode;
        document.getElementById('modalTitle').innerText = mode === 'create' ? 'Tambah Sub Pelajaran' : 'Edit Sub Pelajaran';
        if(mode === 'create') {
            document.getElementById('subCategoryId').value = '';
            document.getElementById('subCatName').value = '';
            @if(!$category)
                document.getElementById('catSelect').value = '';
            @endif
        }
        modal.classList.add('active');
    }

    function openEditModal(id, name, catId) {
        openModal('edit');
        document.getElementById('subCategoryId').value = id;
        document.getElementById('subCatName').value = name;
        document.getElementById('catSelect').value = catId;
    }

    function closeModal() {
        modal.classList.remove('active');
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const id = document.getElementById('subCategoryId').value;
        const name = document.getElementById('subCatName').value;
        const categoryId = document.getElementById('catSelect').value;
        
        const url = currentMode === 'create' ? "{{ route('admin.sub-categories.store') }}" : `/admin/sub-categories/${id}`;
        const method = currentMode === 'create' ? 'POST' : 'PUT';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ category_id: categoryId, name: name })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showToast(currentMode === 'create' ? 'Sub pelajaran berhasil ditambahkan!' : 'Sub pelajaran berhasil diperbarui!');
                setTimeout(() => location.reload(), 500);
            } else {
                showToast(data.message || 'Terjadi kesalahan', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Gagal memproses permintaan', 'error');
        });
    });

    function deleteSubCategory(id) {
        customConfirm('Apakah Anda yakin ingin menghapus sub pelajaran ini?', function() {
            fetch(`/admin/sub-categories/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast('Sub pelajaran berhasil dihapus!');
                    document.getElementById(`row-${id}`).remove();
                } else {
                    showToast(data.message || 'Gagal menghapus', 'error');
                }
            });
        });
    }
</script>
@endpush
