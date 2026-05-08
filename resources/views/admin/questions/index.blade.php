@extends('layouts.admin')

@section('title', 'Bank Soal')
@section('header_title', 'Manajemen Bank Soal')

@section('content')
<div class="glass animate-fade-in" style="padding: 32px; margin-bottom: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
        <div>
            <h3 style="font-family: 'Outfit', sans-serif; margin-bottom: 4px;">Bank Soal</h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Kelola pertanyaan ujian untuk berbagai kategori.</p>
        </div>
        <div style="display: flex; gap: 16px;">
            <div style="position: relative; width: 300px;">
                <i class="fas fa-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                <input type="text" id="searchInput" class="form-input" placeholder="Cari soal..." style="padding-left: 44px; margin-bottom: 0;">
            </div>
            <button class="btn-primary" onclick="openQuestionModal('create')">
                <i class="fas fa-plus"></i> Tambah Soal
            </button>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 60px;">ID</th>
                <th>KATEGORI</th>
                <th>TIPE</th>
                <th>SOAL</th>
                <th style="width: 150px; text-align: center;">AKSI</th>
            </tr>
        </thead>
        <tbody>
            @forelse($questions as $question)
            <tr>
                <td>#{{ $question->id }}</td>
                <td><span class="badge" style="background: rgba(59, 130, 246, 0.1); color: var(--accent);">{{ $question->category->name }}</span></td>
                <td>
                    @if($question->type === 'pilihan_ganda') Pilihan Ganda
                    @elseif($question->type === 'benar_salah') Benar / Salah
                    @else Multiple Choice @endif
                </td>
                <td style="max-width: 400px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        @if($question->question_image)
                            <img src="{{ asset('storage/' . $question->question_image) }}" style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover;">
                        @endif
                        <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ Str::limit($question->question_text, 80) }}</span>
                    </div>
                </td>
                <td style="text-align: center;">
                    <button class="btn-icon" onclick="editQuestion({{ $question->id }})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon delete" onclick="deleteQuestion({{ $question->id }})" title="Hapus">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-secondary);">Belum ada soal.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    
    <div style="margin-top: 24px; display: flex; justify-content: center;">
        {{ $questions->links() }}
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
        background: rgba(255, 255, 255, 0.05) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
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
        background: rgba(255, 255, 255, 0.1) !important;
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

<!-- Question Modal -->
<div class="modal-overlay" id="questionModal">
    <div class="modal-content glass animate-fade-in" style="max-width: 800px;">
        <div class="modal-header">
            <h3 id="modalTitle">Tambah Soal Baru</h3>
            <button class="close-modal" onclick="closeQuestionModal()">&times;</button>
        </div>
        <form id="questionForm" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="questionId">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                <!-- Left Column -->
                <div>
                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="category_id" id="catSelect" class="form-input" required>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Tipe Soal</label>
                        <select name="type" id="typeSelect" class="form-input" onchange="handleTypeChange()" required>
                            <option value="pilihan_ganda">Pilihan Ganda (Single)</option>
                            <option value="benar_salah">Benar / Salah</option>
                            <option value="multiple_choice">Multiple Choice (Pilih Banyak)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Pertanyaan</label>
                        <textarea name="question_text" id="qText" class="form-input" style="height: 120px;" placeholder="Tuliskan soal di sini..." required></textarea>
                    </div>
                </div>

                <!-- Right Column -->
                <div>
                    <div class="form-group">
                        <label>Gambar Soal (Opsional)</label>
                        <div class="image-preview-container" onclick="document.getElementById('imageInput').click()">
                            <img id="imagePreview" src="" style="display: none;">
                            <div class="placeholder" id="imagePlaceholder">
                                <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; margin-bottom: 8px;"></i>
                                <p>Klik untuk upload gambar</p>
                            </div>
                        </div>
                        <input type="file" name="question_image" id="imageInput" style="display: none;" onchange="previewImage(this)">
                    </div>

                    <div id="optionsContainer">
                        <label style="display: block; margin-bottom: 12px; color: var(--text-secondary); font-size: 0.9rem;">Opsi Jawaban</label>
                        <div id="optionsList">
                            <!-- Dynamic Options Based on Type -->
                        </div>
                        <button type="button" id="addOptionBtn" class="btn-primary" style="background: transparent; border: 1px dashed var(--glass-border); width: 100%; margin-top: 12px;" onclick="addOption()">
                            <i class="fas fa-plus"></i> Tambah Opsi
                        </button>
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 32px; justify-content: flex-end;">
                <button type="button" class="btn-primary" style="background: transparent; border: 1px solid var(--glass-border); color: var(--text-secondary);" onclick="closeQuestionModal()">Batal</button>
                <button type="submit" class="btn-primary">Simpan Soal</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const questionModal = document.getElementById('questionModal');
    const questionForm = document.getElementById('questionForm');
    const optionsList = document.getElementById('optionsList');
    const typeSelect = document.getElementById('typeSelect');
    let mode = 'create';

    function openQuestionModal(m) {
        mode = m;
        document.getElementById('modalTitle').innerText = m === 'create' ? 'Tambah Soal Baru' : 'Edit Soal';
        document.getElementById('questionId').value = '';
        questionForm.reset();
        document.getElementById('imagePreview').style.display = 'none';
        document.getElementById('imagePlaceholder').style.display = 'block';
        handleTypeChange();
        questionModal.classList.add('active');
    }

    function closeQuestionModal() {
        questionModal.classList.remove('active');
    }

    function handleTypeChange() {
        const type = typeSelect.value;
        optionsList.innerHTML = '';
        document.getElementById('addOptionBtn').style.display = type === 'benar_salah' ? 'none' : 'block';

        if (type === 'benar_salah') {
            addOptionItem('Benar', false, type);
            addOptionItem('Salah', false, type);
        } else {
            addOptionItem('', false, type);
            addOptionItem('', false, type);
        }
    }

    function addOption() {
        addOptionItem('', false, typeSelect.value);
    }

    function addOptionItem(text = '', isCorrect = false, type = 'pilihan_ganda') {
        const index = optionsList.children.length;
        const div = document.createElement('div');
        div.className = `option-item ${isCorrect ? 'correct' : ''}`;
        
        const inputType = type === 'multiple_choice' ? 'checkbox' : 'radio';
        const name = 'correct_answer[]';

        div.innerHTML = `
            <input type="${inputType}" name="${name}" value="${index}" ${isCorrect ? 'checked' : ''} onchange="handleCorrectChange(this)">
            <input type="text" name="options[]" value="${text}" placeholder="Pilihan ${String.fromCharCode(65 + index)}" ${type === 'benar_salah' ? 'readonly' : ''} required>
            ${type !== 'benar_salah' ? '<button type="button" class="btn-icon delete" onclick="this.parentElement.remove()" style="border:none; background:none;"><i class="fas fa-times"></i></button>' : ''}
        `;
        optionsList.appendChild(div);
    }

    function handleCorrectChange(el) {
        if (typeSelect.value !== 'multiple_choice') {
            document.querySelectorAll('.option-item').forEach(item => item.classList.remove('correct'));
        }
        if (el.checked) {
            el.parentElement.classList.add('correct');
        } else {
            el.parentElement.classList.remove('correct');
        }
    }

    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('imagePreview').src = e.target.result;
                document.getElementById('imagePreview').style.display = 'block';
                document.getElementById('imagePlaceholder').style.display = 'none';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    questionForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const id = document.getElementById('questionId').value;
        const url = mode === 'create' ? "{{ route('admin.questions.store') }}" : `/admin/questions/${id}`;
        
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
                showToast(data.message || 'Validation failed', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Gagal memproses permintaan', 'error');
        });
    });

    function deleteQuestion(id) {
        customConfirm('Hapus soal ini dari bank soal?', function() {
            fetch(`/admin/questions/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast('Soal berhasil dihapus');
                    setTimeout(() => location.reload(), 500);
                }
            });
        });
    }

    // Edit Logic (Simplified)
    function editQuestion(id) {
        fetch(`/admin/questions/${id}`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            const q = data.data;
            openQuestionModal('edit');
            document.getElementById('questionId').value = q.id;
            document.getElementById('catSelect').value = q.category_id;
            document.getElementById('typeSelect').value = q.type;
            document.getElementById('qText').value = q.question_text;
            
            if (q.question_image) {
                document.getElementById('imagePreview').src = `/storage/${q.question_image}`;
                document.getElementById('imagePreview').style.display = 'block';
                document.getElementById('imagePlaceholder').style.display = 'none';
            }

            optionsList.innerHTML = '';
            q.options.forEach((opt, idx) => {
                const isCorrect = q.correct_answer.includes(idx.toString());
                addOptionItem(opt, isCorrect, q.type);
            });
            
            document.getElementById('addOptionBtn').style.display = q.type === 'benar_salah' ? 'none' : 'block';
        });
    }

    // Client-side Search Filter
    document.getElementById('searchInput').addEventListener('input', function(e) {
        const term = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('.data-table tbody tr');

        rows.forEach(row => {
            if (row.cells.length < 5) return; // Skip empty row
            const category = row.cells[1].textContent.toLowerCase();
            const type = row.cells[2].textContent.toLowerCase();
            const text = row.cells[3].textContent.toLowerCase();
            
            if (category.includes(term) || type.includes(term) || text.includes(term)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
</script>
@endpush
