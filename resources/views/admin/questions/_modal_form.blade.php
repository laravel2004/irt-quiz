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
                <div style="background: #ffffff; border: 1px solid var(--glass-border); border-radius: 16px; padding: 24px;">
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

                <!-- Gambar Soal Section -->
                <div style="background: #ffffff; border: 1px solid var(--glass-border); border-radius: 16px; padding: 24px; display: flex; flex-direction: column;">
                    <h4 style="margin-bottom: 20px; color: var(--text-primary); font-family: 'Outfit', sans-serif; display: flex; align-items: center; gap: 8px; font-size: 1.1rem;">
                        <div style="background: rgba(245, 158, 11, 0.2); color: #f59e0b; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-image"></i>
                        </div>
                        Gambar Soal (Opsional)
                    </h4>
                    
                    <div class="form-group" style="margin-bottom: 0; display: flex; flex-direction: column;">
                        <div class="image-preview-container" onclick="document.getElementById('imageInput').click()" style="min-height: 250px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
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
                <div style="background: #ffffff; border: 1px solid var(--glass-border); border-radius: 16px; padding: 24px; display: flex; flex-direction: column;">
                    <h4 style="margin-bottom: 20px; color: var(--text-primary); font-family: 'Outfit', sans-serif; display: flex; align-items: center; gap: 8px; font-size: 1.1rem;">
                        <div style="background: rgba(16, 185, 129, 0.2); color: #10b981; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-list-ul"></i>
                        </div>
                        <span id="optionsLabelText">Opsi Jawaban</span>
                    </h4>
                    
                    <div id="optionsContainer" style="display: flex; flex-direction: column;">
                        <div id="optionsList" style="max-height: 350px; overflow-y: auto; padding-right: 8px; margin-bottom: 12px;">
                            <!-- Dynamic Options Based on Type -->
                        </div>
                        <button type="button" id="addOptionBtn" class="btn-primary" style="background: rgba(59, 130, 246, 0.1); border: 1px dashed var(--accent); color: var(--accent); width: 100%; font-weight: 600;" onclick="addOption()">
                            <i class="fas fa-plus"></i> Tambah Opsi
                        </button>
                    </div>
                </div>

                <!-- Konten Soal (Teks) Section -->
                <div style="background: #ffffff; border: 1px solid var(--glass-border); border-radius: 16px; padding: 24px;">
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
                <div style="background: #ffffff; border: 1px solid var(--glass-border); border-radius: 16px; padding: 24px;">
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
                <button type="button" class="btn-primary" style="background: #eff6ff; border: 1px solid var(--glass-border); color: var(--text-primary); min-width: 120px;" onclick="closeQuestionModal()">
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
            <button class="btn-primary" onclick="closePreviewModal()" style="background: #eff6ff; border: 1px solid var(--glass-border); color: #0f172a;">Tutup Preview</button>
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
                Ketik rumus langsung atau gunakan tombol cepat di bawah. Gunakan keyboard: <code style="background: #e2e8f0; padding: 2px 6px; border-radius: 4px;">^</code> untuk pangkat, <code style="background: #e2e8f0; padding: 2px 6px; border-radius: 4px;">/</code> untuk pecahan.
            </p>
            <math-field id="mathLiveField" virtual-keyboard-mode="manual"></math-field>

            <!-- Quick formula buttons -->
            <div style="margin-bottom: 8px;">
                <span class="math-preview-label">Rumus Cepat</span>
            </div>
            <div class="math-quick-formulas">
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\frac{a}{b}')" title="Pecahan">â…Ÿ</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('x^{2}')" title="Pangkat">xÂ²</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('x_{n}')" title="Subskrip">xâ‚™</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\sqrt{x}')" title="Akar">âˆšx</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\sqrt[n]{x}')" title="Akar-n">â¿âˆšx</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\sum_{i=1}^{n}')" title="Sigma">âˆ‘</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\int_{a}^{b}')" title="Integral">âˆ«</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\lim_{x \\\\to \\\\infty}')" title="Limit">lim</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\pi')" title="Pi">Ï€</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\alpha')" title="Alpha">Î±</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\beta')" title="Beta">Î²</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\theta')" title="Theta">Î¸</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\Delta')" title="Delta">Î”</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\pm')" title="Plus Minus">Â±</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\times')" title="Kali">Ã—</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\div')" title="Bagi">Ã·</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\neq')" title="Tidak sama dengan">â‰ </button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\leq')" title="Kurang sama dengan">â‰¤</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\geq')" title="Lebih sama dengan">â‰¥</button>
                <button type="button" class="math-quick-btn" onclick="insertQuickFormula('\\\\infty')" title="Infinity">âˆž</button>
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

