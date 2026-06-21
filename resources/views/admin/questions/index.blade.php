@extends('layouts.admin')

@section('title', 'Bank Soal')
@section('header_title', 'Manajemen Bank Soal')

@section('content')
<div class="glass animate-fade-in" style="padding: 32px; margin-bottom: 24px;">
    <div class="flex-stack-mobile" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; gap: 20px;">
        <div>
            <h3 style="font-family: 'Outfit', sans-serif; margin-bottom: 4px;">Bank Soal</h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Kelola pertanyaan ujian untuk berbagai kategori.</p>
        </div>
        <div class="flex-stack-mobile" style="display: flex; gap: 16px;">
            <div style="position: relative; width: 300px;">
                <i class="fas fa-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                <input type="text" id="searchInput" class="form-input" placeholder="Cari soal..." style="padding-left: 44px; margin-bottom: 0;">
            </div>
            <button class="btn-primary" onclick="openQuestionModal('create')">
                <i class="fas fa-plus"></i> Tambah Soal
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>MATA PELAJARAN</th>
                    <th>TIPE</th>
                    <th>KODE SOAL</th>
                    <th>SOAL</th>
                    <th style="width: 150px; text-align: center;">AKSI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($questions as $question)
                <tr>
                    <td>#{{ $question->id }}</td>
                    <td>
                        <span class="badge" style="background: rgba(59, 130, 246, 0.1); color: var(--accent);">{{ $question->category->name }}</span>
                        @if($question->subCategory)
                            <span class="badge" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6; margin-top: 4px;">{{ $question->subCategory->name }}</span>
                        @endif
                    </td>
                    <td>
                        @if($question->type === 'pilihan_ganda') Pilihan Ganda
                        @elseif($question->type === 'benar_salah') Benar / Salah
                        @elseif($question->type === 'multiple_benar_salah') Multiple B/S
                        @else Multiple Choice @endif
                    </td>
                    <td>
                        <span class="badge" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">{{ $question->kode_soal ?? '-' }}</span>
                    </td>
                    <td style="max-width: 400px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            @if($question->question_image)
                                <img src="{{ asset('storage/' . $question->question_image) }}" style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover; flex-shrink: 0;">
                            @endif
                            <div class="math-render-cell" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%;">{{ str_replace('&nbsp;', ' ', strip_tags($question->question_text)) }}</div>
                        </div>
                    </td>
                    <td style="text-align: center;">
                        <button class="btn-icon" onclick="previewQuestion({{ $question->id }})" title="Preview">
                            <i class="fas fa-eye"></i>
                        </button>
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
                    <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-secondary);">Belum ada soal.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
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

    /* Multiple Benar/Salah styling */
    .mbs-statement-item {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
        background: rgba(255, 255, 255, 0.03);
        padding: 12px;
        border-radius: 8px;
        border: 1px solid var(--glass-border);
    }
    .mbs-statement-item input[type="text"] {
        flex: 1;
        background: transparent;
        border: none;
        color: white;
        outline: none;
    }
    .mbs-toggle-group {
        display: flex;
        gap: 4px;
        flex-shrink: 0;
    }
    .mbs-toggle-btn {
        padding: 6px 14px;
        border-radius: 6px;
        border: 1px solid var(--glass-border);
        background: transparent;
        color: var(--text-secondary);
        cursor: pointer;
        font-size: 0.8rem;
        font-weight: 600;
        transition: all 0.2s ease;
    }
    .mbs-toggle-btn.active-benar {
        background: rgba(16, 185, 129, 0.2);
        border-color: #10b981;
        color: #10b981;
    }
    .mbs-toggle-btn.active-salah {
        background: rgba(239, 68, 68, 0.2);
        border-color: #ef4444;
        color: #ef4444;
    }

    /* TinyMCE dark theme overrides */
    .tox-tinymce {
        border: 1px solid var(--glass-border) !important;
        border-radius: 8px !important;
    }
    .tox .tox-toolbar__primary {
        background: rgba(255, 255, 255, 0.05) !important;
    }
    /* Fix TinyMCE dropdowns/menus appearing behind the question modal */
    .tox-tinymce-aux {
        z-index: 4000 !important;
    }

    /* ===== Math Editor Modal ===== */
    .math-modal-overlay {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0, 0, 0, 0.85);
        backdrop-filter: blur(8px);
        z-index: 5000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .math-modal-overlay.active {
        display: flex;
    }
    .math-modal {
        background: #1e293b;
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 20px;
        width: 100%;
        max-width: 720px;
        padding: 32px;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
        animation: fadeIn 0.3s ease;
    }
    .math-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }
    .math-modal-header h3 {
        font-family: 'Outfit', sans-serif;
        font-size: 1.2rem;
        color: white;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .math-modal-header h3 i {
        color: var(--accent);
    }
    .math-close-btn {
        background: none;
        border: none;
        color: var(--text-secondary);
        font-size: 1.5rem;
        cursor: pointer;
        transition: color 0.2s;
    }
    .math-close-btn:hover { color: white; }

    /* MathLive field styling */
    math-field {
        display: block;
        width: 100%;
        min-height: 80px;
        font-size: 1.4rem;
        background: rgba(255, 255, 255, 0.05);
        border: 2px solid rgba(255, 255, 255, 0.15);
        border-radius: 12px;
        padding: 16px;
        color: white;
        --caret-color: #3b82f6;
        --selection-background-color: rgba(59, 130, 246, 0.3);
        --contains-highlight-background-color: transparent;
        --primary-color: #3b82f6;
        --text-font-family: 'Inter', sans-serif;
        margin-bottom: 16px;
        transition: border-color 0.3s;
    }
    math-field:focus-within {
        border-color: var(--accent);
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
    }

    /* Quick formulas palette */
    .math-quick-formulas {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 20px;
    }
    .math-quick-btn {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        padding: 8px 14px;
        color: var(--text-secondary);
        cursor: pointer;
        font-size: 0.85rem;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 44px;
    }
    .math-quick-btn:hover {
        background: rgba(59, 130, 246, 0.15);
        border-color: var(--accent);
        color: white;
        transform: translateY(-1px);
    }

    /* Preview box */
    .math-preview-box {
        background: rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 12px;
        padding: 20px;
        min-height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
    }
    .math-preview-box .katex {
        font-size: 1.5rem;
    }
    .math-preview-label {
        font-size: 0.8rem;
        color: var(--text-secondary);
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }
    .math-latex-raw {
        background: rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 8px;
        padding: 10px 14px;
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
        color: #94a3b8;
        word-break: break-all;
        margin-bottom: 20px;
    }
    .math-insert-btn {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
        border: none;
        padding: 14px 32px;
        border-radius: 12px;
        font-weight: 700;
        font-family: 'Outfit', sans-serif;
        font-size: 1rem;
        cursor: pointer;
        width: 100%;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .math-insert-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
    }
    .math-mode-tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 16px;
    }
    .math-mode-tab {
        flex: 1;
        padding: 10px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        background: transparent;
        color: var(--text-secondary);
        cursor: pointer;
        font-weight: 600;
        font-size: 0.85rem;
        transition: all 0.2s;
        text-align: center;
    }
    .math-mode-tab.active {
        background: rgba(59, 130, 246, 0.15);
        border-color: var(--accent);
        color: var(--accent);
    }
    
    /* Preview Modal Custom Styling */
    .preview-option-item {
        padding: 12px 16px;
        background: rgba(255,255,255,0.03);
        border-radius: 8px;
        border: 1px solid var(--glass-border);
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .preview-option-item.correct {
        background: rgba(16, 185, 129, 0.1);
        border-color: rgba(16, 185, 129, 0.3);
    }
</style>

<!-- Question Modal -->
<div class="modal-overlay" id="questionModal">
    <div class="modal-content glass animate-fade-in" style="max-width: 900px;">
        <div class="modal-header">
            <h3 id="modalTitle">Tambah Soal Baru</h3>
            <button class="close-modal" onclick="closeQuestionModal()">&times;</button>
        </div>
        <form id="questionForm" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="questionId">
            
            <div style="display: flex; flex-direction: column; gap: 24px;">
                <!-- Informasi Dasar Section -->
                <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--glass-border); border-radius: 16px; padding: 24px;">
                    <h4 style="margin-bottom: 20px; color: var(--text-primary); font-family: 'Outfit', sans-serif; display: flex; align-items: center; gap: 8px; font-size: 1.1rem;">
                        <div style="background: rgba(59, 130, 246, 0.2); color: var(--accent); width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        Informasi Dasar
                    </h4>
                    
                    <div class="responsive-grid" style="gap: 20px; align-items: end;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label><i class="fas fa-folder-open" style="color: var(--text-secondary); margin-right: 6px;"></i> Mata Pelajaran</label>
                            <select name="category_id" id="catSelect" class="form-input" onchange="loadSubCategoriesForForm(this.value)" required>
                                <option value="">-- Pilih Mata Pelajaran --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label><i class="fas fa-layer-group" style="color: var(--text-secondary); margin-right: 6px;"></i> Sub Pelajaran</label>
                            <select name="sub_category_id" id="subCatSelect" class="form-input" onchange="loadKodeSoalOptions(this.value)" required>
                                <option value="">-- Pilih Sub Pelajaran --</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label><i class="fas fa-tags" style="color: var(--text-secondary); margin-right: 6px;"></i> Kode Soal</label>
                            <input type="text" name="kode_soal" id="kodeSoal" class="form-input" list="kodeSoalOptions" placeholder="Pilih atau ketik kode soal baru" maxlength="100" required>
                            <datalist id="kodeSoalOptions"></datalist>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label><i class="fas fa-sliders-h" style="color: var(--text-secondary); margin-right: 6px;"></i> Tipe Soal</label>
                            <select name="type" id="typeSelect" class="form-input" onchange="handleTypeChange()" required>
                                <option value="pilihan_ganda">Pilihan Ganda (Single)</option>
                                <option value="benar_salah">Benar / Salah</option>
                                <option value="multiple_choice">Multiple Choice (Pilih Banyak)</option>
                                <option value="multiple_benar_salah">Multiple Benar / Salah</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label><i class="fas fa-check" style="color: #10b981; margin-right: 6px;"></i> Skor Benar</label>
                            <input type="number" name="score_correct" id="scoreCorrect" class="form-input" value="1" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label><i class="fas fa-times" style="color: #ef4444; margin-right: 6px;"></i> Skor Salah</label>
                            <input type="number" name="score_incorrect" id="scoreIncorrect" class="form-input" value="0" required>
                        </div>
                    </div>
                </div>

                <div class="responsive-grid" style="gap: 24px;">
                    <!-- Gambar Soal Section -->
                    <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--glass-border); border-radius: 16px; padding: 24px; display: flex; flex-direction: column;">
                        <h4 style="margin-bottom: 20px; color: var(--text-primary); font-family: 'Outfit', sans-serif; display: flex; align-items: center; gap: 8px; font-size: 1.1rem;">
                            <div style="background: rgba(245, 158, 11, 0.2); color: #f59e0b; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-image"></i>
                            </div>
                            Gambar Soal (Opsional)
                        </h4>
                        
                        <div class="form-group" style="flex: 1; margin-bottom: 0; display: flex; flex-direction: column;">
                            <div class="image-preview-container" onclick="document.getElementById('imageInput').click()" style="flex: 1; min-height: 250px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                <img id="imagePreview" src="" style="display: none; max-height: 100%; object-fit: contain;">
                                <div class="placeholder" id="imagePlaceholder">
                                    <i class="fas fa-cloud-upload-alt" style="font-size: 2.5rem; margin-bottom: 12px; color: var(--accent);"></i>
                                    <p>Klik untuk upload gambar pendukung</p>
                                </div>
                            </div>
                            <input type="file" name="question_image" id="imageInput" style="display: none;" onchange="previewImage(this)">
                        </div>
                    </div>

                    <!-- Opsi Jawaban Section -->
                    <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--glass-border); border-radius: 16px; padding: 24px; display: flex; flex-direction: column;">
                        <h4 style="margin-bottom: 20px; color: var(--text-primary); font-family: 'Outfit', sans-serif; display: flex; align-items: center; gap: 8px; font-size: 1.1rem;">
                            <div style="background: rgba(16, 185, 129, 0.2); color: #10b981; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-list-ul"></i>
                            </div>
                            <span id="optionsLabelText">Opsi Jawaban</span>
                        </h4>
                        
                        <div id="optionsContainer" style="flex: 1; display: flex; flex-direction: column;">
                            <div id="optionsList" style="flex: 1; max-height: 350px; overflow-y: auto; padding-right: 8px; margin-bottom: 12px;">
                                <!-- Dynamic Options Based on Type -->
                            </div>
                            <button type="button" id="addOptionBtn" class="btn-primary" style="background: rgba(59, 130, 246, 0.1); border: 1px dashed var(--accent); color: var(--accent); width: 100%; font-weight: 600;" onclick="addOption()">
                                <i class="fas fa-plus"></i> Tambah Opsi
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Konten Soal (Teks) Section -->
                <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--glass-border); border-radius: 16px; padding: 24px;">
                    <h4 style="margin-bottom: 20px; color: var(--text-primary); font-family: 'Outfit', sans-serif; display: flex; align-items: center; gap: 8px; font-size: 1.1rem;">
                        <div style="background: rgba(59, 130, 246, 0.2); color: #3b82f6; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-align-left"></i>
                        </div>
                        Konten Soal (Teks Pertanyaan)
                    </h4>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <textarea name="question_text" id="qText" class="form-input" style="height: 250px;"></textarea>
                    </div>
                </div>

                <!-- Pembahasan Section -->
                <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--glass-border); border-radius: 16px; padding: 24px;">
                    <h4 style="margin-bottom: 20px; color: var(--text-primary); font-family: 'Outfit', sans-serif; display: flex; align-items: center; gap: 8px; font-size: 1.1rem;">
                        <div style="background: rgba(139, 92, 246, 0.2); color: #8b5cf6; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        Pembahasan (Opsional)
                    </h4>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <textarea name="explanation" id="explanationText" class="form-input" style="height: 180px;"></textarea>
                    </div>
                </div>
            </div>

            <div class="flex-stack-mobile" style="display: flex; gap: 16px; margin-top: 32px; justify-content: flex-end; padding-top: 24px; border-top: 1px solid var(--glass-border);">
                <button type="button" class="btn-primary" style="background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); color: var(--text-primary); min-width: 120px;" onclick="closeQuestionModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn-primary" style="min-width: 180px; background: linear-gradient(135deg, #3b82f6, #2563eb); box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);">
                    <i class="fas fa-save"></i> Simpan Soal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal-overlay" id="previewModal">
    <div class="modal-content glass animate-fade-in" style="max-width: 800px;">
        <div class="modal-header">
            <h3><i class="fas fa-eye" style="margin-right:8px; color:var(--accent);"></i> Preview Soal</h3>
            <button class="close-modal" onclick="closePreviewModal()">&times;</button>
        </div>
        <div style="padding: 24px; background: rgba(255,255,255,0.02); border-radius: 12px; border: 1px solid var(--glass-border); margin-bottom: 24px;">
            <div style="display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap;">
                <span id="previewCategory" class="badge" style="background: rgba(59, 130, 246, 0.1); color: var(--accent);"></span>
                <span id="previewSubCategory" class="badge" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6; display: none;"></span>
                <span id="previewType" class="badge" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"></span>
                <span class="badge" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;"><i class="fas fa-check"></i> <span id="previewScoreCorrect"></span> | <i class="fas fa-times"></i> <span id="previewScoreIncorrect"></span></span>
            </div>
            
            <div id="previewImageContainer" style="margin-bottom: 20px; text-align: center; display: none;">
                <img id="previewImage" src="" style="max-height: 250px; border-radius: 8px; border: 1px solid var(--glass-border);">
            </div>
            
            <div id="previewText" style="font-size: 1.1rem; line-height: 1.6; margin-bottom: 24px; font-family: 'Inter', sans-serif;"></div>
            
            <h5 style="color: var(--text-secondary); margin-bottom: 12px; font-weight: 600;">Opsi Jawaban / Pernyataan:</h5>
            <div id="previewOptions" style="display: flex; flex-direction: column; gap: 12px;"></div>
        </div>
        
        <div id="previewExplanationContainer" style="display: none; padding: 24px; background: rgba(139, 92, 246, 0.05); border-radius: 12px; border: 1px dashed rgba(139, 92, 246, 0.3);">
            <h5 style="color: #8b5cf6; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;"><i class="fas fa-lightbulb"></i> Pembahasan</h5>
            <div id="previewExplanation" style="line-height: 1.6;"></div>
        </div>
        
        <div style="display: flex; justify-content: flex-end; margin-top: 24px;">
            <button class="btn-primary" onclick="closePreviewModal()" style="background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); color: white;">Tutup Preview</button>
        </div>
    </div>
</div>

<!-- Math Editor Modal -->
<div class="math-modal-overlay" id="mathEditorModal">
    <div class="math-modal">
        <div class="math-modal-header">
            <h3><i class="fas fa-square-root-variable"></i> Editor Rumus Matematika</h3>
            <button class="math-close-btn" onclick="closeMathEditor()">&times;</button>
        </div>

        <!-- Mode tabs -->
        <div class="math-mode-tabs">
            <button type="button" class="math-mode-tab active" onclick="switchMathMode('visual', this)">
                <i class="fas fa-pen-fancy" style="margin-right: 6px;"></i> Visual Editor
            </button>
            <button type="button" class="math-mode-tab" onclick="switchMathMode('latex', this)">
                <i class="fas fa-code" style="margin-right: 6px;"></i> LaTeX Manual
            </button>
        </div>

        <!-- Visual Editor (MathLive) -->
        <div id="mathVisualMode">
            <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 12px;">
                Ketik rumus langsung atau gunakan tombol cepat di bawah. Gunakan keyboard: <code style="background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px;">^</code> untuk pangkat, <code style="background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px;">/</code> untuk pecahan.
            </p>
            <math-field id="mathLiveField" virtual-keyboard-mode="manual"></math-field>

            <!-- Quick formula buttons -->
            <div style="margin-bottom: 8px;">
                <span class="math-preview-label">Rumus Cepat</span>
            </div>
            <div class="math-quick-formulas">
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\frac{a}{b}')" title="Pecahan">⅟</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('x^{2}')" title="Pangkat">x²</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('x_{n}')" title="Subskrip">xₙ</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\sqrt{x}')" title="Akar">√x</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\sqrt[n]{x}')" title="Akar-n">ⁿ√x</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\sum_{i=1}^{n}')" title="Sigma">∑</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\int_{a}^{b}')" title="Integral">∫</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\lim_{x \\\\to \\\\infty}')" title="Limit">lim</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\pi')" title="Pi">π</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\alpha')" title="Alpha">α</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\beta')" title="Beta">β</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\theta')" title="Theta">θ</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\Delta')" title="Delta">Δ</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\pm')" title="Plus Minus">±</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\times')" title="Kali">×</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\div')" title="Bagi">÷</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\neq')" title="Tidak sama dengan">≠</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\leq')" title="Kurang sama dengan">≤</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\geq')" title="Lebih sama dengan">≥</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\infty')" title="Infinity">∞</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\log_{a}')" title="Logaritma">log</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\sin')" title="Sin">sin</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\cos')" title="Cos">cos</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\tan')" title="Tan">tan</button>
            </div>
        </div>

        <!-- LaTeX Manual Mode -->
        <div id="mathLatexMode" style="display: none;">
            <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 12px;">
                Tulis kode LaTeX secara manual. Preview akan diperbarui otomatis.
            </p>
            <textarea id="mathLatexInput" class="form-input" style="height: 100px; font-family: 'Courier New', monospace; font-size: 0.95rem;" placeholder="Contoh: \frac{-b \pm \sqrt{b^2 - 4ac}}{2a}" oninput="updateMathPreviewFromLatex()"></textarea>
        </div>

        <!-- Preview -->
        <div style="margin-top: 16px;">
            <span class="math-preview-label">Preview</span>
        </div>
        <div class="math-preview-box" id="mathPreviewBox">
            <span style="color: var(--text-secondary); font-style: italic;">Ketik rumus untuk melihat preview...</span>
        </div>

        <!-- LaTeX output -->
        <div>
            <span class="math-preview-label">Kode LaTeX</span>
        </div>
        <div class="math-latex-raw" id="mathLatexDisplay">-</div>

        <button type="button" class="math-insert-btn" onclick="insertMathFormula()">
            <i class="fas fa-check-circle"></i> Sisipkan ke Editor
        </button>
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
    let questionEditor = null;
    let explanationEditor = null;
    let activeMathTarget = null; // which editor to insert math into

    // Initialize TinyMCE
    function initTinyMCE() {
        // Destroy existing editors first
        if (questionEditor) {
            questionEditor.destroy();
            questionEditor = null;
        }
        if (explanationEditor) {
            explanationEditor.destroy();
            explanationEditor = null;
        }

        tinymce.init({
            selector: '#qText',
            height: 250,
            menubar: false,
            skin: 'oxide-dark',
            content_css: 'dark',
            plugins: 'lists link code table charmap',
            toolbar: 'undo redo | blocks | bold italic underline strikethrough | bullist numlist | link table | charmap code | mathBtn',
            content_style: `
                body { 
                    font-family: 'Inter', sans-serif; 
                    font-size: 14px; 
                    color: #e2e8f0; 
                    background: #1e293b;
                    padding: 12px;
                }
                p { margin: 0 0 10px; }
            `,
            setup: function(editor) {
                questionEditor = editor;
                editor.ui.registry.addButton('mathBtn', {
                    text: '∑ Math',
                    tooltip: 'Sisipkan Rumus Matematika',
                    onAction: function() {
                        activeMathTarget = editor;
                        openMathEditor();
                    }
                });
            }
        });

        tinymce.init({
            selector: '#explanationText',
            height: 200,
            menubar: false,
            skin: 'oxide-dark',
            content_css: 'dark',
            plugins: 'lists link code table charmap',
            toolbar: 'undo redo | blocks | bold italic underline strikethrough | bullist numlist | link table | charmap code | mathBtn',
            content_style: `
                body { 
                    font-family: 'Inter', sans-serif; 
                    font-size: 14px; 
                    color: #e2e8f0; 
                    background: #1e293b;
                    padding: 12px;
                }
                p { margin: 0 0 10px; }
            `,
            setup: function(editor) {
                explanationEditor = editor;
                editor.ui.registry.addButton('mathBtn', {
                    text: '∑ Math',
                    tooltip: 'Sisipkan Rumus Matematika',
                    onAction: function() {
                        activeMathTarget = editor;
                        openMathEditor();
                    }
                });
            }
        });
    }

    function openQuestionModal(m = 'create') {
        mode = m;
        document.getElementById('modalTitle').innerText = mode === 'create' ? 'Tambah Soal Baru' : 'Edit Soal';
        if (mode === 'create') {
            document.getElementById('questionId').value = '';
            questionForm.reset();
            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('imagePlaceholder').style.display = 'block';
            document.getElementById('imageInput').value = '';
            if (questionEditor) questionEditor.setContent('');
            if (explanationEditor) explanationEditor.setContent('');
            document.getElementById('catSelect').value = '';
            document.getElementById('subCatSelect').innerHTML = '<option value="">-- Pilih Sub Pelajaran --</option>';
            document.getElementById('kodeSoal').value = '';
            document.getElementById('kodeSoalOptions').innerHTML = '';
            document.getElementById('typeSelect').value = 'pilihan_ganda';
            handleTypeChange();
        }
        questionModal.classList.add('active');
        
        // Initialize TinyMCE after modal is shown
        setTimeout(() => {
            initTinyMCE();
        }, 100);
    }

    function closeQuestionModal() {
        // Destroy editors before closing
        if (questionEditor) {
            questionEditor.destroy();
            questionEditor = null;
        }
        if (explanationEditor) {
            explanationEditor.destroy();
            explanationEditor = null;
        }
        questionModal.classList.remove('active');
    }

    function handleTypeChange() {
        const type = typeSelect.value;
        optionsList.innerHTML = '';
        
        if (type === 'multiple_benar_salah') {
            document.getElementById('addOptionBtn').style.display = 'block';
            document.getElementById('addOptionBtn').innerHTML = '<i class="fas fa-plus"></i> Tambah Pernyataan';
            document.getElementById('optionsLabelText').textContent = 'Pernyataan (Tentukan Benar/Salah)';
            addMBSItem('', 'benar');
            addMBSItem('', 'salah');
        } else {
            document.getElementById('addOptionBtn').innerHTML = '<i class="fas fa-plus"></i> Tambah Opsi';
            document.getElementById('optionsLabelText').textContent = 'Opsi Jawaban';
            document.getElementById('addOptionBtn').style.display = type === 'benar_salah' ? 'none' : 'block';

            if (type === 'benar_salah') {
                addOptionItem('Benar', false, type);
                addOptionItem('Salah', false, type);
            } else {
                addOptionItem('', false, type);
                addOptionItem('', false, type);
            }
        }
    }

    function addOption() {
        const type = typeSelect.value;
        if (type === 'multiple_benar_salah') {
            addMBSItem('', 'benar');
        } else {
            addOptionItem('', false, type);
        }
    }

    // Multiple Benar/Salah item
    function addMBSItem(text = '', correctValue = 'benar') {
        const index = optionsList.children.length;
        const div = document.createElement('div');
        div.className = 'mbs-statement-item';
        
        div.innerHTML = `
            <span style="color: var(--text-secondary); font-weight: 600; font-size: 0.85rem; min-width: 28px;">${index + 1}.</span>
            <input type="text" name="options[]" value="${text}" placeholder="Pernyataan ${index + 1}" required>
            <div class="mbs-toggle-group">
                <button type="button" class="mbs-toggle-btn ${correctValue === 'benar' ? 'active-benar' : ''}" onclick="setMBSAnswer(this, 'benar')">B</button>
                <button type="button" class="mbs-toggle-btn ${correctValue === 'salah' ? 'active-salah' : ''}" onclick="setMBSAnswer(this, 'salah')">S</button>
            </div>
            <input type="hidden" name="correct_answer[]" value="${correctValue}">
            <button type="button" class="btn-icon delete" onclick="this.parentElement.remove(); reindexMBS()" style="border:none; background:none;"><i class="fas fa-times"></i></button>
        `;
        optionsList.appendChild(div);
    }

    function setMBSAnswer(btn, value) {
        const parent = btn.closest('.mbs-statement-item');
        const hiddenInput = parent.querySelector('input[name="correct_answer[]"]');
        const buttons = parent.querySelectorAll('.mbs-toggle-btn');
        
        buttons.forEach(b => {
            b.classList.remove('active-benar', 'active-salah');
        });
        
        if (value === 'benar') {
            btn.classList.add('active-benar');
        } else {
            btn.classList.add('active-salah');
        }
        hiddenInput.value = value;
    }

    function reindexMBS() {
        const items = optionsList.querySelectorAll('.mbs-statement-item');
        items.forEach((item, i) => {
            item.querySelector('span').textContent = `${i + 1}.`;
            item.querySelector('input[type="text"]').placeholder = `Pernyataan ${i + 1}`;
        });
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
        
        // Sync TinyMCE content to textareas before submitting
        if (questionEditor) {
            questionEditor.save();
        }
        if (explanationEditor) {
            explanationEditor.save();
        }
        
        const formData = new FormData(this);
        const id = document.getElementById('questionId').value;
        const url = mode === 'create' ? "{{ route('admin.questions.store') }}" : `/admin/questions/${id}`;
        
        if (mode === 'edit') formData.append('_method', 'PUT');

        // For multiple_benar_salah, we need to convert correct_answer to indices of "benar" answers
        const type = formData.get('type');
        if (type === 'multiple_benar_salah') {
            const correctAnswers = formData.getAll('correct_answer[]');
            // Remove existing correct_answer entries
            formData.delete('correct_answer[]');
            // Add back indices where answer is 'benar'
            correctAnswers.forEach((val, idx) => {
                if (val === 'benar') {
                    formData.append('correct_answer[]', idx.toString());
                }
            });
            // If no "benar" answers, add a placeholder to avoid empty
            if (!formData.has('correct_answer[]')) {
                formData.append('correct_answer[]', '-1');
            }
        }

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

    // Load SubCategories via AJAX
    function loadSubCategoriesForForm(categoryId, selectedSubId = null) {
        const subCatSelect = document.getElementById('subCatSelect');
        subCatSelect.innerHTML = '<option value="">Memuat...</option>';
        if (!categoryId) {
            subCatSelect.innerHTML = '<option value="">-- Pilih Sub Pelajaran --</option>';
            loadKodeSoalOptions(null);
            return;
        }
        
        fetch(`/admin/sub-categories?category_id=${categoryId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(res => res.json())
            .then(data => {
                subCatSelect.innerHTML = '<option value="">-- Pilih Sub Pelajaran --</option>';
                if (data.status === 'success') {
                    data.data.forEach(sub => {
                        const isSelected = selectedSubId == sub.id ? 'selected' : '';
                        subCatSelect.innerHTML += `<option value="${sub.id}" ${isSelected}>${sub.name}</option>`;
                    });
                    if (selectedSubId) {
                        loadKodeSoalOptions(selectedSubId);
                    } else {
                        loadKodeSoalOptions(null);
                    }
                }
            });
    }

    function loadKodeSoalOptions(subCategoryId) {
        const kodeSoalOptions = document.getElementById('kodeSoalOptions');
        kodeSoalOptions.innerHTML = '';

        if (!subCategoryId) {
            return;
        }

        fetch(`{{ route('admin.questions.kode-soal') }}?sub_category_id=${subCategoryId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    data.data.forEach(kode => {
                        const option = document.createElement('option');
                        option.value = kode;
                        kodeSoalOptions.appendChild(option);
                    });
                }
            });
    }

    // Edit Logic
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
            loadSubCategoriesForForm(q.category_id, q.sub_category_id);
            document.getElementById('kodeSoal').value = q.kode_soal || '';
            document.getElementById('typeSelect').value = q.type;
            document.getElementById('scoreCorrect').value = q.score_correct || 1;
            document.getElementById('scoreIncorrect').value = q.score_incorrect || 0;
            
            if (q.question_image) {
                document.getElementById('imagePreview').src = `/storage/${q.question_image}`;
                document.getElementById('imagePreview').style.display = 'block';
                document.getElementById('imagePlaceholder').style.display = 'none';
            }

            optionsList.innerHTML = '';

            if (q.type === 'multiple_benar_salah') {
                document.getElementById('addOptionBtn').style.display = 'block';
                document.getElementById('addOptionBtn').innerHTML = '<i class="fas fa-plus"></i> Tambah Pernyataan';
                document.getElementById('optionsLabelText').textContent = 'Pernyataan (Tentukan Benar/Salah)';
                
                q.options.forEach((opt, idx) => {
                    const isBenar = q.correct_answer.includes(idx.toString());
                    addMBSItem(opt, isBenar ? 'benar' : 'salah');
                });
            } else {
                document.getElementById('optionsLabelText').textContent = 'Opsi Jawaban';
                document.getElementById('addOptionBtn').innerHTML = '<i class="fas fa-plus"></i> Tambah Opsi';
                document.getElementById('addOptionBtn').style.display = q.type === 'benar_salah' ? 'none' : 'block';
                
                q.options.forEach((opt, idx) => {
                    const isCorrect = q.correct_answer.includes(idx.toString());
                    addOptionItem(opt, isCorrect, q.type);
                });
            }

            // Set TinyMCE content after editors are initialized
            setTimeout(() => {
                if (questionEditor) {
                    questionEditor.setContent(q.question_text || '');
                }
                if (explanationEditor) {
                    explanationEditor.setContent(q.explanation || '');
                }
            }, 500);
        });
    }

    // Preview Logic
    function previewQuestion(id) {
        fetch(`/admin/questions/${id}`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            const q = data.data;
            document.getElementById('previewCategory').textContent = q.category?.name || 'Mata Pelajaran';
            
            const previewSub = document.getElementById('previewSubCategory');
            if (q.sub_category) {
                previewSub.textContent = q.sub_category.name;
                previewSub.style.display = 'inline-block';
            } else {
                previewSub.style.display = 'none';
            }
            
            let typeLabel = 'Pilihan Ganda';
            if (q.type === 'benar_salah') typeLabel = 'Benar / Salah';
            else if (q.type === 'multiple_choice') typeLabel = 'Multiple Choice';
            else if (q.type === 'multiple_benar_salah') typeLabel = 'Multiple Benar/Salah';
            document.getElementById('previewType').textContent = typeLabel;
            
            document.getElementById('previewScoreCorrect').textContent = q.score_correct || 1;
            document.getElementById('previewScoreIncorrect').textContent = q.score_incorrect || 0;
            
            if (q.question_image) {
                document.getElementById('previewImage').src = `/storage/${q.question_image}`;
                document.getElementById('previewImageContainer').style.display = 'block';
            } else {
                document.getElementById('previewImageContainer').style.display = 'none';
            }
            
            document.getElementById('previewText').innerHTML = q.question_text || '-';
            
            const optionsContainer = document.getElementById('previewOptions');
            optionsContainer.innerHTML = '';
            
            if (q.type === 'multiple_benar_salah') {
                q.options.forEach((opt, idx) => {
                    const isBenar = q.correct_answer.includes(idx.toString());
                    optionsContainer.innerHTML += `
                        <div class="preview-option-item">
                            <span style="flex: 1;">${opt}</span>
                            <span class="badge" style="background: ${isBenar ? 'rgba(16,185,129,0.2); color:#10b981' : 'rgba(239,68,68,0.2); color:#ef4444'}">Jawaban: ${isBenar ? 'Benar' : 'Salah'}</span>
                        </div>
                    `;
                });
            } else {
                q.options.forEach((opt, idx) => {
                    const isCorrect = q.correct_answer.includes(idx.toString());
                    optionsContainer.innerHTML += `
                        <div class="preview-option-item ${isCorrect ? 'correct' : ''}">
                            <div style="width: 24px; height: 24px; border-radius: 50%; background: ${isCorrect ? '#10b981' : 'rgba(255,255,255,0.1)'}; color: white; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: bold; flex-shrink: 0;">
                                ${isCorrect ? '<i class="fas fa-check"></i>' : String.fromCharCode(65 + idx)}
                            </div>
                            <span>${opt}</span>
                        </div>
                    `;
                });
            }
            
            if (q.explanation) {
                document.getElementById('previewExplanation').innerHTML = q.explanation;
                document.getElementById('previewExplanationContainer').style.display = 'block';
            } else {
                document.getElementById('previewExplanationContainer').style.display = 'none';
            }
            
            document.getElementById('previewModal').classList.add('active');
            
            // Render KaTeX for math formulas inside the preview
            setTimeout(() => {
                if (window.renderMathInElement) {
                    const renderOpts = {
                        delimiters: [
                            {left: '$$', right: '$$', display: true},
                            {left: '$', right: '$', display: false},
                            {left: '\\(', right: '\\)', display: false},
                            {left: '\\[', right: '\\]', display: true}
                        ],
                        throwOnError: false
                    };
                    renderMathInElement(document.getElementById('previewText'), renderOpts);
                    renderMathInElement(document.getElementById('previewExplanation'), renderOpts);
                    // Render in options too in case they have math
                    renderMathInElement(document.getElementById('previewOptions'), renderOpts);
                }
            }, 100);
        });
    }

    function closePreviewModal() {
        document.getElementById('previewModal').classList.remove('active');
    }

    // Client-side Search Filter
    document.getElementById('searchInput').addEventListener('input', function(e) {
        const term = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('.data-table tbody tr');

        rows.forEach(row => {
            if (row.cells.length < 6) return; // Skip empty row
            const category = row.cells[1].textContent.toLowerCase();
            const type = row.cells[2].textContent.toLowerCase();
            const kodeSoal = row.cells[3].textContent.toLowerCase();
            const text = row.cells[4].textContent.toLowerCase();
            
            if (category.includes(term) || type.includes(term) || kodeSoal.includes(term) || text.includes(term)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // ===== Math Editor Functions =====
    const mathModal = document.getElementById('mathEditorModal');
    const mathField = document.getElementById('mathLiveField');
    const mathPreview = document.getElementById('mathPreviewBox');
    const mathLatexDisplay = document.getElementById('mathLatexDisplay');
    let currentMathMode = 'visual';

    function openMathEditor() {
        mathModal.classList.add('active');
        if (mathField) mathField.value = '';
        document.getElementById('mathLatexInput').value = '';
        mathPreview.innerHTML = '<span style="color:var(--text-secondary);font-style:italic">Ketik rumus untuk melihat preview...</span>';
        mathLatexDisplay.textContent = '-';
        switchMathMode('visual', document.querySelector('.math-mode-tab'));
        setTimeout(() => { if (mathField) mathField.focus(); }, 200);
    }

    function closeMathEditor() {
        mathModal.classList.remove('active');
    }

    function switchMathMode(mode, btn) {
        currentMathMode = mode;
        document.querySelectorAll('.math-mode-tab').forEach(t => t.classList.remove('active'));
        if (btn) btn.classList.add('active');
        document.getElementById('mathVisualMode').style.display = mode === 'visual' ? 'block' : 'none';
        document.getElementById('mathLatexMode').style.display = mode === 'latex' ? 'block' : 'none';
    }

    function insertQuickFormula(latex) {
        if (mathField) {
            mathField.executeCommand(['insert', latex]);
            mathField.focus();
            updateMathPreview();
        }
    }

    function getLatexValue() {
        if (currentMathMode === 'visual' && mathField) return mathField.value || '';
        return document.getElementById('mathLatexInput').value || '';
    }

    function updateMathPreview() {
        const latex = getLatexValue();
        mathLatexDisplay.textContent = latex || '-';
        if (!latex) {
            mathPreview.innerHTML = '<span style="color:var(--text-secondary);font-style:italic">Ketik rumus untuk melihat preview...</span>';
            return;
        }
        try {
            mathPreview.innerHTML = katex.renderToString(latex, { throwOnError: false, displayMode: true });
        } catch(e) {
            mathPreview.innerHTML = '<span style="color:#ef4444">Error: ' + e.message + '</span>';
        }
    }

    function updateMathPreviewFromLatex() {
        const latex = document.getElementById('mathLatexInput').value;
        mathLatexDisplay.textContent = latex || '-';
        if (!latex) {
            mathPreview.innerHTML = '<span style="color:var(--text-secondary);font-style:italic">Ketik rumus...</span>';
            return;
        }
        try {
            mathPreview.innerHTML = katex.renderToString(latex, { throwOnError: false, displayMode: true });
        } catch(e) {
            mathPreview.innerHTML = '<span style="color:#ef4444">Error</span>';
        }
    }

    function insertMathFormula() {
        const latex = getLatexValue();
        if (!latex || !activeMathTarget) { closeMathEditor(); return; }
        activeMathTarget.insertContent(`<span class="math-tex">\\(${latex}\\)</span>&nbsp;`);
        closeMathEditor();
        showToast('Rumus berhasil disisipkan');
    }

    // Listen for MathLive input changes
    if (mathField) {
        mathField.addEventListener('input', updateMathPreview);
    }

    document.addEventListener("DOMContentLoaded", function() {
        if (window.renderMathInElement) {
            const cells = document.querySelectorAll('.math-render-cell');
            cells.forEach(cell => {
                renderMathInElement(cell, {
                    delimiters: [
                        {left: '$$', right: '$$', display: false},
                        {left: '$', right: '$', display: false},
                        {left: '\\(', right: '\\)', display: false},
                        {left: '\\[', right: '\\]', display: false}
                    ],
                    throwOnError: false
                });
            });
        }
        
        // Handle URL Parameters for auto-opening modal
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('action') === 'create') {
            openQuestionModal('create');
            
            const catId = urlParams.get('category_id');
            const subCatId = urlParams.get('sub_category_id');
            
            if (catId) {
                document.getElementById('catSelect').value = catId;
                if (subCatId) {
                    loadSubCategoriesForForm(catId, subCatId);
                    loadKodeSoalOptions(subCatId);
                } else {
                    loadSubCategoriesForForm(catId);
                }
            }
        }
    });
</script>
@endpush
