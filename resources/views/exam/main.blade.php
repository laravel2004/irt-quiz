<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $session->name }} - Exam</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body {
            background: #020617;
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .exam-header {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 16px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 100;
        }
        .main-layout {
            display: grid;
            grid-template-columns: 1fr 320px;
            flex: 1;
            overflow: hidden;
        }
        .question-area {
            padding: 40px;
            overflow-y: auto;
            position: relative;
        }
        .sidebar-nav {
            background: rgba(15, 23, 42, 0.4);
            border-left: 1px solid rgba(255, 255, 255, 0.1);
            padding: 24px;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }
        .timer-box {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid var(--accent);
            padding: 12px 20px;
            border-radius: 12px;
            color: var(--accent);
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .question-card {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 40px;
            max-width: 900px;
            margin: 0 auto 100px;
        }
        .nav-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin-top: 20px;
        }
        .nav-btn {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--glass-border);
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .nav-btn.active { background: var(--accent); border-color: var(--accent); }
        .nav-btn.answered { border-color: #10b981; color: #10b981; background: rgba(16, 185, 129, 0.05); }

        .option-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--glass-border);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 16px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .option-item:hover { background: rgba(255, 255, 255, 0.06); border-color: var(--accent); }
        .option-item.selected { background: rgba(59, 130, 246, 0.1); border-color: var(--accent); }
        
        .option-radio {
            width: 20px;
            height: 20px;
            border: 2px solid var(--glass-border);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .selected .option-radio { border-color: var(--accent); }
        .selected .option-radio::after {
            content: '';
            width: 10px;
            height: 10px;
            background: var(--accent);
            border-radius: 50%;
        }

        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 320px;
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(10px);
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body>
    <header class="exam-header">
        <div style="display: flex; align-items: center; gap: 20px;">
            <div style="font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 1.2rem; color: white;">
                <i class="fas fa-graduation-cap" style="color: var(--accent); margin-right: 8px;"></i> {{ $session->name }}
            </div>
            <div style="height: 24px; width: 1px; background: rgba(255,255,255,0.1);"></div>
            <div style="color: var(--text-secondary); font-size: 0.9rem;">
                {{ $participant->name }} ({{ $participant->access_code }})
            </div>
        </div>
        <div class="timer-box" id="timer">
            <i class="fas fa-clock"></i> <span id="timeDisplay">00:00:00</span>
        </div>
    </header>

    <div class="main-layout">
        <div class="question-area" id="questionArea">
            <!-- Questions will be rendered here by JS -->
        </div>

        <aside class="sidebar-nav">
            <h4 style="font-family: 'Outfit', sans-serif; margin-bottom: 4px;">Navigasi Soal</h4>
            <p style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 20px;">Klik nomor untuk berpindah soal.</p>
            
            <div class="nav-grid" id="navGrid">
                <!-- Navigation buttons will be rendered here -->
            </div>

            <div style="margin-top: auto; padding-top: 40px;">
                <button class="btn-primary" style="width: 100%; height: 50px; background: #ef4444;" onclick="confirmSubmit()">
                    <i class="fas fa-paper-plane"></i> Selesai Ujian
                </button>
            </div>
        </aside>
    </div>

    <div class="bottom-nav">
        <button class="btn-primary" style="background: transparent; border: 1px solid var(--glass-border);" id="prevBtn" onclick="prevQuestion()">
            <i class="fas fa-chevron-left"></i> Sebelumnya
        </button>
        <button class="btn-primary" style="min-width: 150px;" id="nextBtn" onclick="nextQuestion()">
            Selanjutnya <i class="fas fa-chevron-right"></i>
        </button>
    </div>

    <!-- Confirmation Modal (Optional but good) -->

    <script>
        const questions = @json($questions);
        const sessionKey = `exam_answers_{{ $participant->id }}`;
        let currentIdx = 0;
        let answers = JSON.parse(localStorage.getItem(sessionKey) || '{}');
        let remainingSeconds = Math.floor({{ $remainingSeconds }});

        function renderNav() {
            const grid = document.getElementById('navGrid');
            grid.innerHTML = '';
            questions.forEach((q, i) => {
                const btn = document.createElement('button');
                btn.className = `nav-btn ${i === currentIdx ? 'active' : ''} ${answers[q.id] ? 'answered' : ''}`;
                btn.innerText = i + 1;
                btn.onclick = () => jumpTo(i);
                grid.appendChild(btn);
            });
        }

        function renderQuestion() {
            const q = questions[currentIdx];
            const area = document.getElementById('questionArea');
            const selectedVal = answers[q.id];

            let optionsHtml = '';
            const options = typeof q.options === 'string' ? JSON.parse(q.options) : q.options;

            if (q.type === 'pilihan_ganda' || q.type === 'benar_salah') {
                optionsHtml = options.map((opt, i) => `
                    <div class="option-item ${selectedVal == opt ? 'selected' : ''}" onclick="selectOption('${q.id}', '${opt}')">
                        <div class="option-radio"></div>
                        <div style="color: white; font-size: 1rem;">${opt}</div>
                    </div>
                `).join('');
            } else if (q.type === 'multiple_choice') {
                // For multiple choice, handled differently
                const currentAnswers = Array.isArray(selectedVal) ? selectedVal : [];
                optionsHtml = options.map((opt, i) => `
                    <div class="option-item ${currentAnswers.includes(opt) ? 'selected' : ''}" onclick="toggleOption('${q.id}', '${opt}')">
                        <div class="option-radio" style="border-radius: 4px;"></div>
                        <div style="color: white; font-size: 1rem;">${opt}</div>
                    </div>
                `).join('');
            }

            area.innerHTML = `
                <div class="question-card animate-fade-in">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 24px;">
                        <span class="badge" style="background: rgba(59, 130, 246, 0.1); color: var(--accent);">SOAL NO ${currentIdx + 1}</span>
                        <span class="badge" style="background: rgba(255,255,255,0.05); color: var(--text-secondary);">${q.category.name}</span>
                    </div>
                    
                    <div style="color: white; font-size: 1.25rem; line-height: 1.6; margin-bottom: 40px; font-weight: 500;">
                        ${q.question_text}
                        ${q.question_image ? `<div style="margin-top: 20px;"><img src="/storage/${q.question_image}" style="max-width: 100%; border-radius: 12px;"></div>` : ''}
                    </div>

                    <div class="options-container">
                        ${optionsHtml}
                    </div>

                    <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: flex-end;">
                        <button class="btn-primary" style="background: transparent; border: 1px solid #ef4444; color: #ef4444; font-size: 0.85rem; height: 38px; min-width: auto; padding: 0 16px;" onclick="clearAnswer('${q.id}')">
                            <i class="fas fa-eraser"></i> Kosongkan Jawaban
                        </button>
                    </div>
                </div>
            `;

            // Update buttons
            document.getElementById('prevBtn').disabled = currentIdx === 0;
            document.getElementById('nextBtn').innerText = currentIdx === questions.length - 1 ? 'Selesai' : 'Selanjutnya';
            
            renderNav();
        }

        function selectOption(qId, val) {
            answers[qId] = val;
            localStorage.setItem(sessionKey, JSON.stringify(answers));
            renderQuestion();
        }

        function clearAnswer(qId) {
            if (confirm('Kosongkan jawaban untuk soal ini?')) {
                delete answers[qId];
                localStorage.setItem(sessionKey, JSON.stringify(answers));
                renderQuestion();
            }
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

        function jumpTo(i) { currentIdx = i; renderQuestion(); }
        function nextQuestion() {
            if (currentIdx < questions.length - 1) {
                currentIdx++; renderQuestion();
            } else {
                confirmSubmit();
            }
        }
        function prevQuestion() { if (currentIdx > 0) { currentIdx--; renderQuestion(); } }

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
                
                if (remainingSeconds < 300) { // Red timer if < 5 mins
                    document.getElementById('timer').style.color = '#ef4444';
                    document.getElementById('timer').style.borderColor = '#ef4444';
                }
            }, 1000);
        }

        function confirmSubmit() {
            if (confirm('Apakah Anda yakin ingin menyelesaikan ujian? Anda tidak dapat kembali setelah mengumpulkan.')) {
                autoSubmit();
            }
        }

        function autoSubmit() {
            fetch("{{ route('exam.submit') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ answers: answers })
            })
            .then(res => res.json())
            .then(data => {
                localStorage.removeItem(sessionKey);
                window.location.href = "{{ route('exam.finish') }}";
            });
        }

        // Init
        renderQuestion();
        startTimer();
    </script>
</body>
</html>
