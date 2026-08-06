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
            skin: 'oxide',
            content_css: false,
            plugins: 'lists link code table charmap',
            toolbar: 'undo redo | blocks | bold italic underline strikethrough | bullist numlist | link table | charmap code | mathBtn',
            content_style: `
                body { 
                    font-family: 'Inter', sans-serif; 
                    font-size: 14px; 
                    color: #0f172a; 
                    background: #ffffff;
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
            skin: 'oxide',
            content_css: false,
            plugins: 'lists link code table charmap',
            toolbar: 'undo redo | blocks | bold italic underline strikethrough | bullist numlist | link table | charmap code | mathBtn',
            content_style: `
                body { 
                    font-family: 'Inter', sans-serif; 
                    font-size: 14px; 
                    color: #0f172a; 
                    background: #ffffff;
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

    function openQuestionModal(m = 'create', defaultCategoryId = null, defaultSubCategoryId = null) {
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
            document.getElementById('catSelect').value = defaultCategoryId || '';
            document.getElementById('subCatSelect').innerHTML = '<option value="">-- Pilih Sub Pelajaran --</option>';
            if (defaultCategoryId) {
                loadSubCategoriesForForm(defaultCategoryId, defaultSubCategoryId);
            }
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
        optionEditors.clear();
        optionImageInputs.clear();
        questionModal.classList.remove('active');
    }

    function handleTypeChange() {
        const type = typeSelect.value;
        optionEditors.clear();
        optionImageInputs.clear();
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
    function addMBSItem(value = '', correctValue = 'benar') {
        const optionId = `option_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;
        const index = optionsList.children.length;
        const div = document.createElement('div');
        div.className = 'mbs-statement-item';
        div.dataset.optionId = optionId;
        
        div.innerHTML = `
            <span class="mbs-number" style="color: var(--text-secondary); font-weight: 600; font-size: 0.85rem; min-width: 28px; margin-top: 8px;">${index + 1}.</span>
            <div style="display:flex; flex-direction: column; flex: 1; min-width: 0; gap: 8px;">
                <div class="editor-mount"></div>
                <div class="mbs-toggle-group" style="align-self: flex-start; margin-bottom: 8px;">
                    <button type="button" class="mbs-toggle-btn ${correctValue === 'benar' ? 'active-benar' : ''}" onclick="setMBSAnswer(this, 'benar')">B</button>
                    <button type="button" class="mbs-toggle-btn ${correctValue === 'salah' ? 'active-salah' : ''}" onclick="setMBSAnswer(this, 'salah')">S</button>
                </div>
                <input type="hidden" class="mbs-hidden-val" value="${correctValue}">
            </div>
            <button type="button" class="btn-icon delete" onclick="const item = this.closest('.mbs-statement-item'); optionEditors.delete(item.dataset.optionId); item.remove(); reindexMBS();" style="border:none; background:none; align-self: flex-start; margin-top: 8px;" title="Hapus Pernyataan"><i class="fas fa-times"></i></button>
        `;
        
        const editorMount = div.querySelector('.editor-mount');
        const editor = createOptionEditor(value);
        editorMount.appendChild(editor.wrapper);
        optionsList.appendChild(div);
        
        optionEditors.set(optionId, editor.content);
        optionImageInputs.set(optionId, editor.fileInput);
    }

    function setMBSAnswer(btn, value) {
        const parent = btn.closest('.mbs-statement-item');
        const hiddenInput = parent.querySelector('.mbs-hidden-val');
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
            const span = item.querySelector('.mbs-number');
            if(span) span.textContent = `${i + 1}.`;
        });
    }

    function addOptionItem(text = '', isCorrect = false, type = 'pilihan_ganda') {
        if (type === 'benar_salah') {
            const index = optionsList.children.length;
            const div = document.createElement('div');
            div.className = `option-item ${isCorrect ? 'correct' : ''}`;
            div.innerHTML = `
                <input type="radio" name="correct_answer[]" value="${index}" ${isCorrect ? 'checked' : ''} onchange="handleCorrectChange(this)">
                <input type="text" name="options[]" value="${text}" placeholder="Pilihan ${String.fromCharCode(65 + index)}" readonly required>
            `;
            optionsList.appendChild(div);
            return;
        }

        addQuestionOption(text, isCorrect, type);
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

    const optionEditors = new Map();
    const optionImageInputs = new Map();
    let optionEditorUid = 0;

    function createOptionEditor(initialHtml = '') {
        const editorId = `optionEditor_${Date.now()}_${optionEditorUid++}`;
        const wrapper = document.createElement('div');
        wrapper.className = 'option-editor-wrapper';
        wrapper.innerHTML = `
            <div class="option-editor-toolbar">
                <button type="button" class="math-quick-btn" data-action="bold"><b>B</b></button>
                <button type="button" class="math-quick-btn" data-action="italic"><i>I</i></button>
                <button type="button" class="math-quick-btn" data-action="underline"><u>U</u></button>
                <button type="button" class="math-quick-btn" data-action="math">∑ Rumus</button>
                <button type="button" class="option-image-btn" data-action="image"><i class="fas fa-image"></i> Gambar</button>
                <input type="file" accept="image/*" class="option-editor-hidden-input">
            </div>
            <div class="option-editor-content" contenteditable="true" data-placeholder="Ketik jawaban, rumus, atau tempel gambar di sini...">${initialHtml || '<span class="option-editor-placeholder">Ketik jawaban, rumus, atau tempel gambar di sini...</span>'}</div>
        `;
        const content = wrapper.querySelector('.option-editor-content');
        const fileInput = wrapper.querySelector('.option-editor-hidden-input');
        const toolbarButtons = wrapper.querySelectorAll('[data-action]');

        toolbarButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const action = this.dataset.action;
                content.focus();
                if (action === 'math') {
                    activeMathTarget = {
                        insertContent(html) {
                            content.innerHTML = content.innerHTML.replace('<span class="option-editor-placeholder">Ketik jawaban, rumus, atau tempel gambar di sini...</span>', '');
                            content.insertAdjacentHTML('beforeend', html);
                        }
                    };
                    openMathEditor();
                } else if (action === 'image') {
                    fileInput.click();
                } else {
                    document.execCommand(action, false, null);
                }
            });
        });

        fileInput.addEventListener('change', function() {
            const file = this.files && this.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                content.innerHTML = content.innerHTML.replace('<span class="option-editor-placeholder">Ketik jawaban, rumus, atau tempel gambar di sini...</span>', '');
                content.insertAdjacentHTML('beforeend', `<img src="${e.target.result}" alt="Gambar jawaban">`);
            };
            reader.readAsDataURL(file);
        });

        content.addEventListener('input', function() {
            if (content.textContent.trim() || content.querySelector('img, .math-tex')) {
                const placeholder = content.querySelector('.option-editor-placeholder');
                if (placeholder) placeholder.remove();
            }
        });

        content.addEventListener('paste', function(e) {
            const items = e.clipboardData?.items || [];
            for (const item of items) {
                if (item.type && item.type.startsWith('image/')) {
                    const file = item.getAsFile();
                    if (!file) continue;
                    e.preventDefault();
                    const reader = new FileReader();
                    reader.onload = function(ev) {
                        content.innerHTML = content.innerHTML.replace('<span class="option-editor-placeholder">Ketik jawaban, rumus, atau tempel gambar di sini...</span>', '');
                        content.insertAdjacentHTML('beforeend', `<img src="${ev.target.result}" alt="Gambar tempel">`);
                    };
                    reader.readAsDataURL(file);
                    break;
                }
            }
        });

        return { wrapper, content, fileInput };
    }

    function addQuestionOption(value = '', checked = false, type = 'pilihan_ganda') {
        const optionId = `option_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;
        const optionWrap = document.createElement('div');
        const inputType = type === 'multiple_choice' ? 'checkbox' : 'radio';
        optionWrap.className = `option-item ${checked ? 'correct' : ''}`;
        optionWrap.dataset.optionId = optionId;
        optionWrap.innerHTML = `
            <input type="${inputType}" name="correct_answer[]" value="${optionId}" ${checked ? 'checked' : ''} onchange="handleCorrectChange(this)" style="margin-top: 8px;">
            <div style="flex: 1;"></div>
            <button type="button" class="btn-icon delete" style="flex-shrink: 0; border:none; background:none; margin-top: 4px;" onclick="const item = this.closest('.option-item'); optionEditors.delete(item.dataset.optionId); item.remove();" title="Hapus Opsi"><i class="fas fa-times"></i></button>
        `;
        const editorMount = optionWrap.querySelector('div');
        const editor = createOptionEditor(value);
        editorMount.appendChild(editor.wrapper);
        optionsList.appendChild(optionWrap);
        optionEditors.set(optionId, editor.content);
        optionImageInputs.set(optionId, editor.fileInput);
        return optionId;
    }

    function getOptionPayload() {
        const payload = [];
        document.querySelectorAll('.option-item, .mbs-statement-item').forEach((item, index) => {
            const optionId = item.dataset.optionId;
            if (!optionId) return;
            const content = optionEditors.get(optionId);
            const checker = item.querySelector('input[type="radio"], input[type="checkbox"]');
            const hiddenVal = item.querySelector('.mbs-hidden-val');
            
            payload.push({
                id: optionId,
                index,
                label: String.fromCharCode(65 + index),
                html: content ? content.innerHTML : '',
                isChecked: !!checker?.checked,
                hiddenValue: hiddenVal ? hiddenVal.value : null
            });
        });
        return payload;
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

        if (typeSelect.value === 'pilihan_ganda' || typeSelect.value === 'multiple_choice' || typeSelect.value === 'multiple_benar_salah') {
            formData.delete('options[]');
            formData.delete('correct_answer[]');
            formData.delete('correct_answer');

            const optionPayload = getOptionPayload();
            optionPayload.forEach((item, idx) => {
                formData.append('options[]', item.html);
                if (typeSelect.value === 'multiple_benar_salah') {
                    if (item.hiddenValue === 'benar') {
                        formData.append('correct_answer[]', idx.toString());
                    }
                } else {
                    if (item.isChecked) {
                        formData.append('correct_answer[]', item.label);
                    }
                }
            });

            if (typeSelect.value === 'multiple_benar_salah' && !formData.has('correct_answer[]')) {
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
                setTimeout(() => {
                    if (mode === 'create') {
                        const currentUrl = new URL(window.location.href);
                        currentUrl.searchParams.set('action', 'create');
                        const catId = document.getElementById('catSelect').value;
                        const subCatId = document.getElementById('subCatSelect').value;
                        if (catId) currentUrl.searchParams.set('category_id', catId);
                        if (subCatId) currentUrl.searchParams.set('sub_category_id', subCatId);
                        window.location.href = currentUrl.toString();
                    } else {
                        location.reload();
                    }
                }, 1000);
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
            
            // Set textarea values BEFORE initializing TinyMCE
            document.getElementById('qText').value = q.question_text || '';
            document.getElementById('explanationText').value = q.explanation || '';
            
            openQuestionModal('edit');
            document.getElementById('questionId').value = q.id;
            document.getElementById('catSelect').value = q.category_id;
            loadSubCategoriesForForm(q.category_id, q.sub_category_id);
            document.getElementById('kodeSoal').value = q.kode_soal || '';
            document.getElementById('typeSelect').value = q.type;
            document.getElementById('scoreCorrect').value = q.score_correct || 1;
            document.getElementById('scoreIncorrect').value = q.score_incorrect || 0;
            
            if (q.question_image) {
                let imgUrl = q.question_image.startsWith('http') || q.question_image.startsWith('/') ? q.question_image : `{{ asset('storage') }}/${q.question_image}`;
                document.getElementById('imagePreview').src = imgUrl;
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
                    const label = String.fromCharCode(65 + idx);
                    const isCorrect = q.correct_answer.includes(idx.toString()) || q.correct_answer.includes(label);
                    addOptionItem(opt, isCorrect, q.type);
                });
            }

            // Fallback: set TinyMCE content after editors are presumably initialized
            setTimeout(() => {
                if (questionEditor) {
                    questionEditor.setContent(q.question_text || '');
                }
                if (explanationEditor) {
                    explanationEditor.setContent(q.explanation || '');
                }
            }, 800);
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
                let imgUrl = q.question_image.startsWith('http') || q.question_image.startsWith('/') ? q.question_image : `{{ asset('storage') }}/${q.question_image}`;
                document.getElementById('previewImage').src = imgUrl;
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
                            <div class="preview-option-html">${opt}</div>
                            <span class="badge" style="background: ${isBenar ? 'rgba(16,185,129,0.2); color:#10b981' : 'rgba(239,68,68,0.2); color:#ef4444'}">Jawaban: ${isBenar ? 'Benar' : 'Salah'}</span>
                        </div>
                    `;
                });
            } else {
                q.options.forEach((opt, idx) => {
                    const label = String.fromCharCode(65 + idx);
                    const isCorrect = q.correct_answer.includes(idx.toString()) || q.correct_answer.includes(label);
                    optionsContainer.innerHTML += `
                        <div class="preview-option-item ${isCorrect ? 'correct' : ''}">
                            <div style="width: 24px; height: 24px; border-radius: 50%; background: ${isCorrect ? '#10b981' : '#eff6ff'}; color: #0f172a; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: bold; flex-shrink: 0;">
                                ${isCorrect ? '<i class="fas fa-check"></i>' : String.fromCharCode(65 + idx)}
                            </div>
                            <div class="preview-option-html">${opt}</div>
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

    // Removed client-side search, handled via backend now

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
        } else if (urlParams.get('action') === 'edit') {
            const qId = urlParams.get('question_id');
            if (qId) {
                editQuestion(qId);
            }
        }
    });
</script>
