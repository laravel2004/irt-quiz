<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $session->name }} - Exam</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- KaTeX for math rendering -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.css">
    <script src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/contrib/auto-render.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --question-font-size: 16px;
        }
        body {
            background: #f8fafc;
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            font-family: 'Inter', sans-serif;
            margin: 0;
            color: #0f172a;
        }
        .top-navbar {
            background: #ffffff;
            border-bottom: 1px solid #dbeafe;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 100;
        }
        .main-content {
            flex: 1;
            padding: 24px;
            display: flex;
            overflow: hidden;
            background: #f8fafc;
        }
        .exam-container {
            background: #ffffff;
            border-radius: 12px;
            flex: 1;
            display: flex;
            flex-direction: column;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .exam-header-bar {
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1px solid #e2e8f0;
        }
        .exam-body {
            flex: 1;
            display: flex;
            overflow: hidden;
            min-width: 0;
        }
        .exam-left-pane {
            flex: 1 1 0;
            min-width: 0;
            max-width: 50%;
            border-right: 2px dashed #cbd5e1;
            padding: 30px;
            overflow: auto;
            display: none;
            position: relative;
            z-index: 1;
        }
        .exam-left-pane img {
            max-width: 100% !important;
            height: auto !important;
            object-fit: contain;
            display: block;
            pointer-events: none;
        }
        .exam-right-pane {
            flex: 1 1 0;
            min-width: 0;
            padding: 30px;
            overflow-y: auto;
            position: relative;
            z-index: 5;
            pointer-events: auto;
        }
        #optionsList {
            position: relative;
            z-index: 6;
        }
        .exam-footer {
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #e2e8f0;
        }

        /* Buttons */
        .btn-pill {
            border-radius: 30px;
            padding: 10px 24px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
            color: #0f172a;
            transition: opacity 0.2s;
        }
        .btn-pill:hover { opacity: 0.9; }
        .btn-pill:disabled { opacity: 0.5; cursor: not-allowed; visibility: hidden; }
        .btn-blue { background: #2563eb; }
        .btn-red { background: #ef4444; }
        .btn-yellow { background: #eab308; color: #000; }
        .btn-outline { background: transparent; border: 1px solid #cbd5e1; color: #0f172a; }

        .font-sizes span { cursor: pointer; margin-right: 12px; transition: color 0.2s; }
        .font-sizes span:hover { color: #3b82f6; }

        .nav-sidebar {
            position: fixed;
            right: -350px;
            top: 0;
            bottom: 0;
            width: 350px;
            background: #ffffff;
            z-index: 1000;
            transition: right 0.3s ease-in-out;
            border-left: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            box-shadow: -5px 0 20px rgba(15,23,42,0.12);
        }
        .nav-sidebar.open {
            right: 0;
        }
        .overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15,23,42,0.25);
            backdrop-filter: blur(2px);
            z-index: 999;
            display: none;
        }
        .overlay.open { display: block; }

        .nav-grid-btn {
            aspect-ratio: 1;
            border-radius: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            cursor: pointer;
            background: #f1f5f9;
            color: #0f172a;
            border: 2px solid transparent;
            font-size: 14px;
            transition: all 0.2s;
        }
        .nav-grid-btn.answered { background: #10b981; color: #0f172a; }
        .nav-grid-btn.doubtful { background: #eab308; color: black; }
        .nav-grid-btn.active { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5); }
        .nav-grid-btn:hover { filter: brightness(1.1); }

        /* Options */
        .option-row {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            cursor: pointer;
            margin-bottom: 16px;
            padding: 12px;
            border-radius: 8px;
            transition: background 0.2s;
        }
        .option-row:hover {
            background: #eff6ff;
        }
        .option-row, .option-row * { cursor: pointer; }
        .option-content { min-width: 0; overflow-wrap: anywhere; word-break: break-word; }
        .option-content img, .question-text img { max-width: 100% !important; height: auto !important; display: block; border-radius: 8px; pointer-events: none; }
        .option-row,
        .option-row * {
            cursor: pointer;
        }
        .option-content {
            min-width: 0;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .option-content img,
        .question-text img {
            max-width: 100% !important;
            height: auto !important;
            display: block;
            border-radius: 8px;
            pointer-events: none;
        }
        .radio-btn {
            width: 20px; height: 20px;
            border-radius: 50%;
            border: 2px solid #94a3b8;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 2px;
            flex-shrink: 0;
        }
        .radio-btn.selected {
            border-color: #3b82f6;
        }
        .radio-btn.selected::after {
            content: '';
            width: 10px; height: 10px;
            background: #3b82f6;
            border-radius: 50%;
        }

        .check-btn {
            width: 20px; height: 20px;
            border-radius: 4px;
            border: 2px solid #94a3b8;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 2px;
            flex-shrink: 0;
        }
        .check-btn.selected {
            background: #3b82f6;
            border-color: #3b82f6;
        }

        .mbs-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding: 16px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            gap: 20px;
        }
        .mbs-btn {
            padding: 8px 20px;
            border-radius: 20px;
            border: 1px solid #cbd5e1;
            background: transparent;
            color: #0f172a;
            cursor: pointer;
            font-weight: 500;
        }
        .mbs-btn.selected-benar {
            background: #10b981;
            border-color: #10b981;
        }
        .mbs-btn.selected-salah {
            background: #ef4444;
            border-color: #ef4444;
        }

        /* HTML content in questions */
        .question-text p { margin: 0 0 10px; }
        .question-text ul, .question-text ol { margin: 0 0 10px 20px; }
        .question-text table { border-collapse: collapse; margin: 10px 0; }
        .question-text table td, .question-text table th { border: 1px solid #cbd5e1; padding: 8px; }

        @media (max-width: 768px) {
            body { height: auto; min-height: 100vh; overflow-y: auto; }
            .main-content { padding: 12px; height: auto; overflow: visible; }
            .exam-container { height: auto; }
            .exam-header-bar { flex-direction: column; gap: 16px; padding: 16px; }
            .exam-header-bar > div:last-child { width: 100%; align-items: flex-start !important; }
            .exam-header-bar > div:last-child > div:first-child { flex-direction: column; align-items: stretch !important; width: 100%; }
            .exam-header-bar > div:last-child > div:first-child > button,
            .exam-header-bar > div:last-child > div:first-child > div { width: 100%; justify-content: center; }
            .btn-pill { font-size: 13px; padding: 10px 16px; }
            .exam-left-pane { display: none !important; }
            .exam-body { flex-direction: column; overflow: visible; }
            .exam-right-pane { padding: 16px; overflow-y: visible; }
            .exam-footer { flex-direction: column; gap: 12px; padding: 16px; align-items: stretch; }
            .exam-footer button { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
    <header class="top-navbar">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="background: #dbeafe; padding: 8px; border-radius: 8px;">
                <img src="{{ asset('img/logo.png') }}" alt="Logo JagoBelajar" style="height: 32px; filter: none;">
            </div>
            <div>
                <div style="color: #0f172a; font-weight: 700; font-size: 15px; letter-spacing: 0.5px;">Bimbel & Try Out Online</div>
            </div>
        </div>
        <div style="color: #0f172a; font-size: 14px; display: flex; align-items: center; gap: 10px;">
            <span style="color: #475569;">{{ $participant->access_code }} - {{ $participant->name }}</span>
            <i class="fas fa-user-circle" style="font-size: 24px; color: #3b82f6;"></i>
        </div>
    </header>

    <div class="overlay" id="overlay" onclick="toggleNav()"></div>

    <div class="nav-sidebar" id="navSidebar">
        <div style="padding: 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="color: #0f172a; margin: 0; font-family: 'Outfit', sans-serif;">Daftar Soal</h3>
            <button onclick="toggleNav()" style="background: none; border: none; color: #64748b; cursor: pointer; font-size: 20px;"><i class="fas fa-times"></i></button>
        </div>
        <div style="padding: 20px; overflow-y: auto; flex: 1;">
            <div id="navGrid" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px;">
                <!-- Navigation buttons will be rendered here -->
            </div>
        </div>
        <div style="padding: 24px; border-top: 1px solid #e2e8f0;">
            <button class="btn-pill btn-red" style="width: 100%; justify-content: center; padding: 14px;" onclick="confirmSubmit()">
                <i class="fas fa-paper-plane"></i> Kumpul Mapel Ini
            </button>
        </div>
    </div>

    <div class="main-content">
        <div class="exam-container">
            <div class="exam-header-bar">
                <div>
                    <h2 style="color: #0f172a; margin: 0 0 12px 0; font-size: 20px; font-weight: 600;" id="soalNomorText">Soal nomor 1</h2>
                    <div class="font-sizes" style="font-size: 13px; color: #64748b; display: flex; align-items: center;">
                        <span style="margin-right: 12px;">Ukuran font soal:</span>
                        <span style="font-size: 12px;" onclick="changeFontSize(14)">A</span>
                        <span style="font-size: 16px;" onclick="changeFontSize(16)">A</span>
                        <span style="font-size: 20px;" onclick="changeFontSize(20)">A</span>
                    </div>
                </div>
                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 12px;">
                    <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap; justify-content: flex-end;">
                        <button class="btn-pill btn-blue" style="font-size: 13px; padding: 6px 16px;">INFORMASI SOAL</button>
                        <div class="btn-pill btn-outline" style="font-size: 13px; padding: 6px 16px; cursor: default; border-color: #475569;">
                            Sisa Waktu : <span id="timeDisplay" style="font-weight: 700; margin-left: 4px; font-family: monospace; font-size: 14px;">00:00:00</span>
                        </div>
                        <button class="btn-pill btn-blue" onclick="toggleNav()" style="font-size: 13px; padding: 6px 16px;">
                            Daftar Soal <i class="fas fa-th-list"></i>
                        </button>
                    </div>
                    <div style="font-weight: 600; font-size: 15px; color: #475569;" id="categoryNameText">Matematika</div>
                </div>
            </div>

            <div class="exam-body">
                <div class="exam-left-pane" id="examLeftPane">
                    <!-- Image content -->
                </div>

                <div class="exam-right-pane" id="examRightPane">
                    <div class="question-text" id="questionText" style="font-size: var(--question-font-size); color: #0f172a; margin-bottom: 32px; line-height: 1.7;">
                        <!-- Question Text -->
                    </div>
                    <div class="options-list" id="optionsList">
                        <!-- Options -->
                    </div>
                </div>
            </div>

            <div class="exam-footer">
                <button id="prevBtn" class="btn-pill btn-red" onclick="prevQuestion()" style="padding: 12px 28px;">
                    <i class="fas fa-chevron-left"></i> Soal sebelumnya
                </button>

                <button id="raguBtn" class="btn-pill btn-yellow" onclick="toggleDoubtfulCurrent()" style="padding: 12px 32px;">
                    <div style="width: 16px; height: 16px; border: 2px solid #000; border-radius: 4px; display: flex; align-items: center; justify-content: center; margin-right: 4px; background: white;" id="raguCheckboxBox">
                        <i class="fas fa-check" id="raguCheckIcon" style="font-size: 10px; display: none;"></i>
                    </div>
                    Ragu-ragu
                </button>

                <button id="nextBtn" class="btn-pill btn-blue" onclick="nextQuestion()" style="padding: 12px 28px;">
                    Soal berikutnya <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
        const questions = @json($questions);
        const sessionKey = `exam_answers_{{ $participant->id }}`;
        const doubtfulKey = `exam_doubtfuls_{{ $participant->id }}`;
        let currentIdx = 0;
        let answers = JSON.parse(localStorage.getItem(sessionKey) || '{}');
        let doubtfuls = JSON.parse(localStorage.getItem(doubtfulKey) || '{}');
        let remainingSeconds = Math.floor({{ $remainingSeconds }});

        function changeFontSize(size) {
            document.documentElement.style.setProperty('--question-font-size', size + 'px');
        }

        function renderNav() {
            const grid = document.getElementById('navGrid');
            grid.innerHTML = '';
            questions.forEach((q, i) => {
                const btn = document.createElement('div');
                const isDoubtful = doubtfuls[q.id];
                const isAnswered = q.type === 'multiple_benar_salah'
                    ? (answers[q.id] && Object.keys(answers[q.id]).length > 0)
                    : (answers[q.id] && (!Array.isArray(answers[q.id]) || answers[q.id].length > 0));

                btn.className = `nav-grid-btn ${i === currentIdx ? 'active' : ''} ${isDoubtful ? 'doubtful' : (isAnswered ? 'answered' : '')}`;
                btn.innerText = i + 1;
                btn.onclick = () => {
                    currentIdx = i;
                    renderQuestion();
                    if (window.innerWidth <= 768) toggleNav();
                };
                grid.appendChild(btn);
            });
        }

        function renderQuestion() {
            const q = questions[currentIdx];

            document.getElementById('soalNomorText').innerText = `Soal nomor ${currentIdx + 1}`;
            document.getElementById('categoryNameText').innerText = q.category.name;

            const selectedVal = answers[q.id];
            let optionsHtml = '';
            const options = typeof q.options === 'string' ? JSON.parse(q.options) : q.options;

            if (q.type === 'pilihan_ganda' || q.type === 'benar_salah') {
                optionsHtml = options.map((opt, i) => {
                    const isSelected = selectedVal === i.toString() || selectedVal === opt;
                    return `
                    <div class="option-row" data-question-id="${q.id}" data-option-index="${i}" data-option-type="single" role="button" tabindex="0">
                        <div class="radio-btn ${isSelected ? 'selected' : ''}"></div>
                        <div class="option-content" style="font-size: var(--question-font-size); color: #0f172a; flex: 1; min-width: 0;">${opt}</div>
                    </div>`;
                }).join('');
            } else if (q.type === 'multiple_choice') {
                const currentAnswers = Array.isArray(selectedVal) ? selectedVal : [];
                optionsHtml = options.map((opt, i) => {
                    const isSelected = currentAnswers.includes(i.toString()) || currentAnswers.includes(opt);
                    return `
                    <div class="option-row" data-question-id="${q.id}" data-option-index="${i}" data-option-type="multi" role="button" tabindex="0">
                        <div class="check-btn ${isSelected ? 'selected' : ''}">
                            ${isSelected ? '<i class="fas fa-check" style="color: #0f172a; font-size: 12px;"></i>' : ''}
                        </div>
                        <div class="option-content" style="font-size: var(--question-font-size); color: #0f172a; flex: 1; min-width: 0;">${opt}</div>
                    </div>`;
                }).join('');
            } else if (q.type === 'multiple_benar_salah') {
                const currentMBS = (typeof selectedVal === 'object' && selectedVal !== null && !Array.isArray(selectedVal)) ? selectedVal : {};
                optionsHtml = options.map((opt, i) => {
                    const val = currentMBS[i.toString()] || null;
                    return `
                    <div class="mbs-row">
                        <div style="flex: 1; font-size: var(--question-font-size); color: #0f172a; line-height: 1.5;">${i + 1}. ${opt}</div>
                        <div style="display: flex; gap: 8px;">
                            <button class="mbs-btn ${val === 'benar' ? 'selected-benar' : ''}" onclick="selectMBS('${q.id}', ${i}, 'benar')">Benar</button>
                            <button class="mbs-btn ${val === 'salah' ? 'selected-salah' : ''}" onclick="selectMBS('${q.id}', ${i}, 'salah')">Salah</button>
                        </div>
                    </div>`;
                }).join('');
            }

            document.getElementById('questionText').innerHTML = q.question_text;
            document.getElementById('optionsList').innerHTML = optionsHtml;

            const leftPane = document.getElementById('examLeftPane');
            if (q.question_image) {
                leftPane.style.display = 'block';
                leftPane.innerHTML = `<img src="/storage/${q.question_image}" alt="Gambar soal" style="max-width: 100%; height: auto; border-radius: 8px; border: 1px solid #e2e8f0; pointer-events: none;">`;
            } else {
                leftPane.style.display = 'none';
            }

            // Update footer buttons
            const isDoubtful = doubtfuls[q.id];
            const checkIcon = document.getElementById('raguCheckIcon');
            if (isDoubtful) {
                checkIcon.style.display = 'block';
            } else {
                checkIcon.style.display = 'none';
            }

            document.getElementById('prevBtn').disabled = currentIdx === 0;

            if (currentIdx === questions.length - 1) {
                document.getElementById('nextBtn').innerHTML = 'Selesai <i class="fas fa-check"></i>';
                document.getElementById('nextBtn').style.background = '#10b981';
            } else {
                document.getElementById('nextBtn').innerHTML = 'Soal berikutnya <i class="fas fa-chevron-right"></i>';
                document.getElementById('nextBtn').style.background = '#2563eb';
            }

            renderNav();

            // Render KaTeX math formulas
            if (typeof renderMathInElement === 'function') {
                renderMathInElement(document.getElementById('examRightPane'), {
                    delimiters: [
                        {left: '\\(', right: '\\)', display: false},
                        {left: '\\[', right: '\\]', display: true},
                        {left: '$$', right: '$$', display: true},
                        {left: '$', right: '$', display: false}
                    ],
                    throwOnError: false
                });
            }
        }


        function getCurrentOptionByIndex(index) {
            const currentQuestion = questions[currentIdx];
            const options = typeof currentQuestion.options === 'string' ? JSON.parse(currentQuestion.options) : currentQuestion.options;
            return options[index];
        }

        function selectOptionByIndex(qId, index) {
            selectOption(qId, index.toString());
        }

        function toggleOptionByIndex(qId, index) {
            toggleOption(qId, index.toString());
        }

        function selectOption(qId, val) {
            answers[qId] = val;
            localStorage.setItem(sessionKey, JSON.stringify(answers));
            renderQuestion();
        }

        function toggleOption(qId, val) {
            let current = answers[qId] || [];
            if (!Array.isArray(current)) current = [];

            if (current.includes(val)) {
                current = current.filter(x => x !== val);
            } else {
                current.push(val);
            }
            answers[qId] = current;
            localStorage.setItem(sessionKey, JSON.stringify(answers));
            renderQuestion();
        }

        function selectMBS(qId, statementIdx, value) {
            if (!answers[qId] || typeof answers[qId] !== 'object' || Array.isArray(answers[qId])) {
                answers[qId] = {};
            }
            answers[qId][statementIdx.toString()] = value;
            localStorage.setItem(sessionKey, JSON.stringify(answers));
            renderQuestion();
        }

        function toggleNav() {
            document.getElementById('navSidebar').classList.toggle('open');
            document.getElementById('overlay').classList.toggle('open');
        }

        function nextQuestion() {
            if (currentIdx < questions.length - 1) {
                currentIdx++;
                renderQuestion();
            } else {
                confirmSubmit();
            }
        }

        function prevQuestion() {
            if (currentIdx > 0) {
                currentIdx--;
                renderQuestion();
            }
        }

        function toggleDoubtfulCurrent() {
            const qId = questions[currentIdx].id;
            if (doubtfuls[qId]) {
                delete doubtfuls[qId];
            } else {
                doubtfuls[qId] = true;
            }
            localStorage.setItem(doubtfulKey, JSON.stringify(doubtfuls));
            renderQuestion();
        }


        document.addEventListener('click', function(event) {
            const optionRow = event.target.closest('.option-row');
            if (!optionRow) return;
            const qId = optionRow.dataset.questionId;
            const index = Number(optionRow.dataset.optionIndex);
            const type = optionRow.dataset.optionType;
            if (Number.isNaN(index) || !qId) return;
            if (type === 'multi') {
                toggleOptionByIndex(qId, index);
            } else {
                selectOptionByIndex(qId, index);
            }
        });

        document.addEventListener('keydown', function(event) {
            const optionRow = event.target.closest('.option-row');
            if (!optionRow) return;
            if (event.key !== 'Enter' && event.key !== ' ') return;
            event.preventDefault();
            const qId = optionRow.dataset.questionId;
            const index = Number(optionRow.dataset.optionIndex);
            const type = optionRow.dataset.optionType;
            if (Number.isNaN(index) || !qId) return;
            if (type === 'multi') {
                toggleOptionByIndex(qId, index);
            } else {
                selectOptionByIndex(qId, index);
            }
        });

        function startTimer() {
            const display = document.getElementById('timeDisplay');
            const interval = setInterval(() => {
                if (remainingSeconds <= 0) {
                    clearInterval(interval);
                    autoSubmit();
                    return;
                }
                remainingSeconds--;

                const h = Math.floor(remainingSeconds / 3600);
                const m = Math.floor((remainingSeconds % 3600) / 60);
                const s = Math.floor(remainingSeconds % 60);

                display.innerText = `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;

                if (remainingSeconds < 300) {
                    display.style.color = '#ef4444';
                }
            }, 1000);
        }

        function confirmSubmit() {
            const doubtfulCount = Object.keys(doubtfuls).length;
            if (doubtfulCount > 0) {
                Swal.fire({
                    title: 'Perhatian!',
                    text: `Masih ada ${doubtfulCount} soal yang bertanda Ragu-ragu. Harap selesaikan sebelum mengumpulkan mata pelajaran ini.`,
                    icon: 'warning',
                    background: '#ffffff',
                    color: '#0f172a',
                    confirmButtonColor: '#3b82f6',
                    buttonsStyling: true,
                    customClass: { popup: 'swal2-popup', title: 'swal2-title', confirmButton: 'swal2-confirm' }
                });
                return;
            }

            Swal.fire({
                title: 'Kumpulkan Mata Pelajaran?',
                text: 'Apakah Anda yakin ingin menyelesaikan mata pelajaran ini? Anda tidak dapat kembali setelah mengumpulkan.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#ffffff',
                confirmButtonText: 'Ya, Kumpulkan',
                cancelButtonText: 'Batal',
                background: '#ffffff',
                color: '#0f172a',
                customClass: {
                    cancelButton: 'swal2-cancel',
                    confirmButton: 'swal2-confirm'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Menyimpan...',
                        allowOutsideClick: false,
                        background: '#ffffff',
                        color: '#0f172a',
                        buttonsStyling: true,
                        customClass: { popup: 'swal2-popup', title: 'swal2-title' },
                        didOpen: () => Swal.showLoading()
                    });
                    autoSubmit();
                }
            });
        }

        function autoSubmit() {
            let payloadAnswers = {};
            for (let qId in answers) {
                payloadAnswers[qId] = {
                    answer: answers[qId],
                    is_doubtful: doubtfuls[qId] ? true : false
                };
            }
            for (let qId in doubtfuls) {
                if (!payloadAnswers[qId]) {
                    payloadAnswers[qId] = {
                        answer: null,
                        is_doubtful: true
                    };
                }
            }

            fetch("{{ route('exam.submit_category', ['code' => $session->code, 'id' => $sessionCategory->id]) }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ answers: payloadAnswers, finish_category: true })
            })
            .then(res => res.json())
            .then(data => {
                localStorage.removeItem(sessionKey);
                localStorage.removeItem(doubtfulKey);
                window.location.href = "{{ route('exam.categories', $session->code) }}";
            });
        }

        // Anti-Cheat: Detect tab switching
        let tabViolations = 0;
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'hidden') {
                tabViolations++;
                if (tabViolations >= 3) {
                    Swal.fire({
                        title: 'Pelanggaran Terdeteksi!',
                        text: 'Anda telah berpindah tab sebanyak 3 kali. Ujian mata pelajaran ini akan dikumpulkan secara otomatis.',
                        icon: 'error',
                        allowOutsideClick: false,
                        background: '#ffffff',
                        color: '#0f172a',
                        buttonsStyling: true,
                        customClass: { popup: 'swal2-popup', title: 'swal2-title' },
                        showConfirmButton: false,
                        timer: 3000
                    }).then(() => {
                        autoSubmit();
                    });
                } else {
                    Swal.fire({
                        title: 'Peringatan Pelanggaran!',
                        html: `Sistem mendeteksi Anda meninggalkan halaman ujian.<br>Peringatan <strong>${tabViolations}/3</strong>.<br><br>Jika mencapai 3 kali, mata pelajaran ini akan diselesaikan secara paksa!`,
                        icon: 'warning',
                        confirmButtonColor: '#ef4444',
                        confirmButtonText: 'Saya Mengerti',
                        background: '#ffffff',
                        color: '#0f172a',
                        buttonsStyling: true,
                        customClass: { popup: 'swal2-popup', title: 'swal2-title', confirmButton: 'swal2-confirm' },
                    });
                }
            }
        });

        // Init
        renderQuestion();
        startTimer();
    </script>
</body>
</html>


