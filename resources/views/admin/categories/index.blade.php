@extends('layouts.admin')

@section('title', 'Kategori Soal')
@section('header_title', 'Manajemen Kategori Soal')

@section('content')
<div class="glass animate-fade-in" style="padding: 32px; margin-bottom: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
        <div>
            <h3 style="font-family: 'Outfit', sans-serif; margin-bottom: 4px;">Daftar Kategori</h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Kelola kategori mata pelajaran untuk bank soal.</p>
        </div>
        <button class="btn-primary" onclick="openModal('create')">
            <i class="fas fa-plus"></i> Tambah Kategori
        </button>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 80px;">ID</th>
                <th>NAMA KATEGORI</th>
                <th>SLUG</th>
                <th style="width: 150px; text-align: center;">AKSI</th>
            </tr>
        </thead>
        <tbody id="categoryTableBody">
            @forelse($categories as $category)
            <tr id="row-{{ $category->id }}">
                <td>#{{ $category->id }}</td>
                <td class="cat-name">{{ $category->name }}</td>
                <td><code style="color: var(--accent);">{{ $category->slug }}</code></td>
                <td style="text-align: center;">
                    <a href="{{ route('admin.categories.show', $category->id) }}" class="btn-icon" title="Lihat Detail">
                        <i class="fas fa-eye"></i>
                    </a>
                    <button class="btn-icon" onclick="openEditModal({{ $category->id }}, '{{ $category->name }}')" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon delete" onclick="deleteCategory({{ $category->id }})" title="Hapus">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                    Belum ada kategori yang ditambahkan.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Create/Edit Modal -->
<div class="modal-overlay" id="categoryModal">
    <div class="modal-content glass animate-fade-in">
        <div class="modal-header">
            <h3 id="modalTitle">Tambah Kategori</h3>
            <button class="close-modal" onclick="closeModal()">&times;</button>
        </div>
        <form id="categoryForm">
            @csrf
            <input type="hidden" id="categoryId">
            <div class="form-group">
                <label for="catName">Nama Kategori</label>
                <input type="text" id="catName" name="name" class="form-input" placeholder="Contoh: Matematika IPA" required>
                <span class="error-msg" id="nameError" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px; display: none;"></span>
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
    const modal = document.getElementById('categoryModal');
    const form = document.getElementById('categoryForm');
    let currentMode = 'create';

    function openModal(mode) {
        currentMode = mode;
        document.getElementById('modalTitle').innerText = mode === 'create' ? 'Tambah Kategori' : 'Edit Kategori';
        document.getElementById('categoryId').value = '';
        form.reset();
        hideErrors();
        modal.classList.add('active');
    }

    function openEditModal(id, name) {
        openModal('edit');
        document.getElementById('categoryId').value = id;
        document.getElementById('catName').value = name;
    }

    function closeModal() {
        modal.classList.remove('active');
    }

    function hideErrors() {
        document.querySelectorAll('.error-msg').forEach(el => el.style.display = 'none');
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        hideErrors();
        
        const id = document.getElementById('categoryId').value;
        const name = document.getElementById('catName').value;
        const url = currentMode === 'create' ? "{{ route('admin.categories.store') }}" : `/admin/categories/${id}`;
        const method = currentMode === 'create' ? 'POST' : 'PUT';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ name: name })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showToast(currentMode === 'create' ? 'Kategori berhasil ditambahkan!' : 'Kategori berhasil diperbarui!');
                setTimeout(() => location.reload(), 500);
            } else {
                if (data.data && data.data.name) {
                    const errorEl = document.getElementById('nameError');
                    errorEl.innerText = data.data.name[0];
                    errorEl.style.display = 'block';
                } else {
                    showToast(data.message || 'Terjadi kesalahan', 'error');
                }
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Gagal memproses permintaan', 'error');
        });
    });

    function deleteCategory(id) {
        customConfirm('Apakah Anda yakin ingin menghapus kategori ini?', function() {
            fetch(`/admin/categories/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast('Kategori berhasil dihapus!');
                    document.getElementById(`row-${id}`).remove();
                } else {
                    showToast(data.message || 'Gagal menghapus', 'error');
                }
            });
        });
    }
</script>
@endpush
