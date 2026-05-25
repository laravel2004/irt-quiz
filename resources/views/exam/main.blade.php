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
            background: #0f172a;
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            font-family: 'Inter', sans-serif;
            margin: 0;
            color: white;
        }
        .top-navbar {
            background: #1e293b;
            border-bottom: 2px solid #3b82f6;
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
            background: #0f172a;
        }
        .exam-container {
            background: #1e293b;
            border-radius: 12px;
            flex: 1;
            display: flex;
            flex-direction: column;
            border: 1px solid #334155;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .exam-header-bar {
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1px solid #334155;
        }
        .exam-body {
            flex: 1;
            display: flex;
            overflow: hidden;
        }
        .exam-left-pane {
            flex: 1;
            border-right: 2px dashed #475569;
            padding: 30px;
            overflow-y: auto;
            display: none;
        }
        .exam-right-pane {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }
        .exam-footer {
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #334155;
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
            color: white;
            transition: opacity 0.2s;
        }
        .btn-pill:hover { opacity: 0.9; }
        .btn-pill:disabled { opacity: 0.5; cursor: not-allowed; visibility: hidden; }
        .btn-blue { background: #2563eb; }
        .btn-red { background: #ef4444; }
        .btn-yellow { background: #eab308; color: #000; }
        .btn-outline { background: transparent; border: 1px solid #cbd5e1; color: white; }

        .font-sizes span { cursor: pointer; margin-right: 12px; transition: color 0.2s; }
        .font-sizes span:hover { color: #3b82f6; }

        .nav-sidebar {
            position: fixed;
            right: -350px;
            top: 0;
            bottom: 0;
            width: 350px;
            background: #1e293b;
            z-index: 1000;
            transition: right 0.3s ease-in-out;
            border-left: 1px solid #334155;
            display: flex;
            flex-direction: column;
            box-shadow: -5px 0 20px rgba(0,0,0,0.5);
        }
        .nav-sidebar.open {
            right: 0;
        }
        .overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.6);
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
            background: #334155;
            color: white;
            border: 2px solid transparent;
            font-size: 14px;
            transition: all 0.2s;
        }
        .nav-grid-btn.answered { background: #10b981; color: white; }
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
            background: rgba(255, 255, 255, 0.05);
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
            background: rgba(255,255,255,0.03);
            border-radius: 8px;
            border: 1px solid #334155;
            gap: 20px;
        }
        .mbs-btn {
            padding: 8px 20px;
            border-radius: 20px;
            border: 1px solid #64748b;
            background: transparent;
            color: white;
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
        .question-text table td, .question-text table th { border: 1px solid rgba(255,255,255,0.2); padding: 8px; }

        @media (max-width: 768px) {
            .exam-header-bar { flex-direction: column; gap: 16px; }
            .exam-header-bar > div:last-child { width: 100%; align-items: stretch; }
            .btn-pill { font-size: 13px; padding: 8px 16px; }
            .exam-left-pane { display: none !important; } /* Hide image pane on mobile, or we could stack it */
            .exam-body { flex-direction: column; }
            .exam-left-pane, .exam-right-pane { border-right: none; border-bottom: 1px dashed #475569; }
        }
    </style>
</head>
<body>
    <header class="top-navbar">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="background: rgba(59, 130, 246, 0.1); padding: 8px; border-radius: 8px;">
                <img src="{{ asset('img/logo.png') }}" alt="Logo JagoBelajar" style="height: 32px; filter: drop-shadow(0 0 2px rgba(255,255,255,0.5));">
            </div>
            <div>
                <div style="color: white; font-weight: 700; font-size: 15px; letter-spacing: 0.5px;">Bimbel & Try Out Online</div>
                <div style="color: #94a3b8; font-size: 12px; font-weight: 500;">APLIKASI ANBK</div>
            </div>
        </div>
        <div style="color: white; font-size: 14px; display: flex; align-items: center; gap: 10px;">
            <span style="color: #cbd5e1;">{{ $participant->access_code }} - {{ $participant->name }}</span>
            <i class="fas fa-user-circle" style="font-size: 24px; color: #3b82f6;"></i>
        </div>
    </header>

    <div class="overlay" id="overlay" onclick="toggleNav()"></div>
    
    <div class="nav-sidebar" id="navSidebar">
        <div style="padding: 20px; border-bottom: 1px solid #334155; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="color: white; margin: 0; font-family: 'Outfit', sans-serif;">Daftar Soal</h3>
            <button onclick="toggleNav()" style="background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 20px;"><i class="fas fa-times"></i></button>
        </div>
        <div style="padding: 20px; overflow-y: auto; flex: 1;">
            <div id="navGrid" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px;">
                <!-- Navigation buttons will be rendered here -->
            </div>
        </div>
        <div style="padding: 24px; border-top: 1px solid #334155;">
            <button class="btn-pill btn-red" style="width: 100%; justify-content: center; padding: 14px;" onclick="confirmSubmit()">
                <i class="fas fa-paper-plane"></i> Kumpul Mapel Ini
            </button>
        </div>
    </div>

    <div class="main-content">
        <div class="exam-container">
            <div class="exam-header-bar">
                <div>
                    <h2 style="color: white; margin: 0 0 12px 0; font-size: 20px; font-weight: 600;" id="soalNomorText">Soal nomor 1</h2>
                    <div class="font-sizes" style="font-size: 13px; color: #94a3b8; display: flex; align-items: center;">
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
                    <div style="font-weight: 600; font-size: 15px; color: #cbd5e1;" id="categoryNameText">Matematika</div>
                </div>
            </div>

            <div class="exam-body">
                <div class="exam-left-pane" id="examLeftPane">
                    <!-- Image content -->
                </div>
                
                <div class="exam-right-pane" id="examRightPane">
                    <div class="question-text" id="questionText" style="font-size: var(--question-font-size); color: white; margin-bottom: 32px; line-height: 1.7;">
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
                    const escapedOpt = opt.replace(/'/g, "\\'").replace(/"/g, '&quot;');
                    const isSelected = selectedVal == opt;
                    return `
                    <label class="option-row" onclick="selectOption('${q.id}', '${escapedOpt}')">
                        <div class="radio-btn ${isSelected ? 'selected' : ''}"></div>
                        <div style="font-size: var(--question-font-size); color: #f8fafc; flex: 1;">${opt}</div>
                    </label>`;
                }).join('');
            } else if (q.type === 'multiple_choice') {
                const currentAnswers = Array.isArray(selectedVal) ? selectedVal : [];
                optionsHtml = options.map((opt, i) => {
                    const escapedOpt = opt.replace(/'/g, "\\'").replace(/"/g, '&quot;');
                    const isSelected = currentAnswers.includes(opt);
                    return `
                    <label class="option-row" onclick="toggleOption('${q.id}', '${escapedOpt}')">
                        <div class="check-btn ${isSelected ? 'selected' : ''}">
                            ${isSelected ? '<i class="fas fa-check" style="color: white; font-size: 12px;"></i>' : ''}
                        </div>
                        <div style="font-size: var(--question-font-size); color: #f8fafc; flex: 1;">${opt}</div>
                    </label>`;
                }).join('');
            } else if (q.type === 'multiple_benar_salah') {
                const currentMBS = (typeof selectedVal === 'object' && selectedVal !== null && !Array.isArray(selectedVal)) ? selectedVal : {};
                optionsHtml = options.map((opt, i) => {
                    const val = currentMBS[i.toString()] || null;
                    return `
                    <div class="mbs-row">
                        <div style="flex: 1; font-size: var(--question-font-size); color: #f8fafc; line-height: 1.5;">${i + 1}. ${opt}</div>
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
                leftPane.innerHTML = `<img src="/storage/${q.question_image}" style="max-width: 100%; border-radius: 8px; border: 1px solid #334155;">`;
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
                    background: '#1e293b',
                    color: '#fff',
                    confirmButtonColor: '#3b82f6'
                });
                return;
            }

            Swal.fire({
                title: 'Kumpulkan Mata Pelajaran?',
                text: 'Apakah Anda yakin ingin menyelesaikan mata pelajaran ini? Anda tidak dapat kembali setelah mengumpulkan.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: 'transparent',
                confirmButtonText: 'Ya, Kumpulkan',
                cancelButtonText: 'Batal',
                background: '#1e293b',
                color: '#fff'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Menyimpan...',
                        allowOutsideClick: false,
                        background: '#1e293b',
                        color: '#fff',
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
                        background: '#1e293b',
                        color: '#fff',
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
                        background: '#1e293b',
                        color: '#fff'
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
