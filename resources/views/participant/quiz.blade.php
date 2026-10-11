@extends('layouts.app')

@section('title', 'Quiz Hari Ini')

@section('content')
<style>
    .quiz-stage { min-height: 100vh; padding: 24px 16px 64px; display: flex; flex-direction: column; align-items: center;
        background: linear-gradient(135deg, #eef2ff 0%, #e0f2fe 50%, #fdf4ff 100%); background-size: 200% 200%; animation: quizBg 14s ease infinite; }
    @keyframes quizBg { 0%,100% { background-position: 0 50%; } 50% { background-position: 100% 50%; } }
    .quiz-wrap { width: 100%; max-width: 760px; }
    .quiz-top { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 16px; }
    .quiz-exit { color: #475569; text-decoration: none; font-weight: 600; }
    .quiz-xp { background: #fff; border: 1px solid #e2e8f0; border-radius: 99px; padding: 8px 16px; font-weight: 700; color: #7c3aed; }
    .quiz-progress { height: 10px; background: #e2e8f0; border-radius: 99px; overflow: hidden; }
    .quiz-progress-bar { height: 100%; width: 0; background: linear-gradient(90deg, #2563eb, #8b5cf6); border-radius: 99px; transition: width .6s cubic-bezier(.22,1,.36,1); }
    .quiz-dots { display: flex; gap: 8px; justify-content: center; margin: 14px 0 20px; }
    .quiz-dot { width: 12px; height: 12px; border-radius: 50%; background: #cbd5e1; transition: all .3s; }
    .quiz-dot.done { background: #93c5fd; }
    .quiz-dot.active { background: #2563eb; transform: scale(1.35); box-shadow: 0 0 0 6px rgba(37,99,235,.15); }
    .quiz-dot.ok { background: #10b981; } .quiz-dot.bad { background: #ef4444; }
    .quiz-card { background: #fff; border-radius: 24px; padding: 28px; border: 1px solid #e2e8f0; box-shadow: 0 20px 50px rgba(37,99,235,.12); }
    .quiz-card.enter { animation: cardIn .5s cubic-bezier(.22,1,.36,1); }
    .quiz-card.leave { animation: cardOut .3s ease forwards; }
    @keyframes cardIn  { from { opacity: 0; transform: translateX(40px) scale(.97); } to { opacity: 1; transform: none; } }
    @keyframes cardOut { to { opacity: 0; transform: translateX(-40px) scale(.97); } }
    .q-number { color: #7c3aed; font-weight: 700; font-size: .85rem; letter-spacing: .04em; text-transform: uppercase; margin-bottom: 8px; }
    .q-text { font-size: 1.15rem; line-height: 1.7; color: #0f172a; margin-bottom: 20px; }
    .q-text img, .q-body img { max-width: 100%; height: auto; border-radius: 12px; }
    .q-opt { display: flex; align-items: center; gap: 14px; width: 100%; text-align: left; background: #f8fafc; border: 2px solid #e2e8f0;
        border-radius: 16px; padding: 14px 16px; margin-bottom: 10px; cursor: pointer; font: inherit; color: #0f172a; transition: transform .2s, border-color .2s, background .2s; }
    .q-opt:hover { transform: translateY(-2px); border-color: #93c5fd; }
    .q-key { flex: 0 0 34px; height: 34px; border-radius: 10px; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-weight: 700; }
    .q-opt.selected { border-color: #2563eb; background: #eff6ff; animation: pop .25s ease; }
    .q-opt.selected .q-key { background: #2563eb; color: #fff; }
    @keyframes pop { 50% { transform: scale(1.03); } }
    .q-opt.is-correct, .q-mbs.is-correct { border-color: #10b981 !important; background: #ecfdf5 !important; animation: pulseOk .8s ease 2; }
    .q-opt.is-wrong, .q-mbs.is-wrong { border-color: #ef4444 !important; background: #fef2f2 !important; animation: shake .5s ease; }
    @keyframes pulseOk { 50% { box-shadow: 0 0 0 10px rgba(16,185,129,.15); } }
    @keyframes shake { 20%,60% { transform: translateX(-8px); } 40%,80% { transform: translateX(8px); } }
    .q-mbs { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 14px; margin-bottom: 10px; border: 2px solid #e2e8f0; border-radius: 16px; background: #f8fafc; flex-wrap: wrap; }
    .q-mbs-text { flex: 1; min-width: 180px; }
    .q-mbs-btns { display: flex; gap: 8px; }
    .q-mbs-btn { border: 2px solid #cbd5e1; background: #fff; border-radius: 12px; padding: 8px 16px; font-weight: 600; cursor: pointer; transition: all .2s; }
    .q-mbs-btn.sel-benar { background: #10b981; border-color: #10b981; color: #fff; }
    .q-mbs-btn.sel-salah { background: #ef4444; border-color: #ef4444; color: #fff; }
    .q-actions { display: flex; justify-content: flex-end; margin-top: 18px; }
    .q-feedback { margin-top: 16px; }
    .q-banner { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: 16px; font-weight: 700; animation: cardIn .4s ease; }
    .q-banner.ok { background: #ecfdf5; color: #047857; } .q-banner.bad { background: #fef2f2; color: #b91c1c; }
    .q-explain { margin-top: 12px; padding: 16px; border-radius: 16px; background: #faf5ff; border: 1px dashed #c4b5fd; animation: slideDown .4s ease; }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: none; } }
    .quiz-summary { text-align: center; padding: 16px 0; }
    .quiz-trophy { font-size: 4rem; animation: trophy 1.2s ease; display: inline-block; }
    @keyframes trophy { 0% { transform: scale(0) rotate(-30deg); } 60% { transform: scale(1.25) rotate(8deg); } 100% { transform: scale(1) rotate(0); } }
    .quiz-star { font-size: 2rem; color: #e2e8f0; margin: 0 4px; opacity: 0; animation: starIn .5s ease forwards; }
    .quiz-star.on { color: #f59e0b; }
    @keyframes starIn { from { opacity: 0; transform: scale(0) rotate(-90deg); } to { opacity: 1; transform: none; } }
    .hidden { display: none !important; }
    .btn-loading { opacity: .7; pointer-events: none; }
    @media (prefers-reduced-motion: reduce) { .quiz-stage, .quiz-stage * { animation: none !important; transition: none !important; } }
</style>

<div class="quiz-stage">
    <div class="quiz-wrap">
        <div class="quiz-top">
            <a href="{{ route('participant.dashboard') }}" class="quiz-exit"><i class="fas fa-arrow-left"></i> Keluar</a>
            <div class="quiz-xp"><i class="fas fa-star" style="color:#f59e0b"></i> <span id="xpChip">{{ $totalXp }}</span> XP</div>
        </div>
        <div class="quiz-progress"><div class="quiz-progress-bar" id="progressBar"></div></div>
        <div class="quiz-dots" id="dots"></div>

        <div class="quiz-card enter" id="card">
            <div class="q-number" id="qNumber"></div>
            <div class="q-text" id="qText"></div>
            <img id="qImage" class="hidden" alt="Gambar soal" style="max-width:100%; border-radius:12px; margin-bottom:16px;">
            <div id="qOptions"></div>
            <div class="q-feedback" id="qFeedback"></div>
            <div class="q-actions">
                <button type="button" class="btn-primary" id="btnSubmit" disabled><i class="fas fa-paper-plane"></i> Jawab</button>
                <button type="button" class="btn-primary hidden" id="btnNext"></button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
<script>
(function () {
    const QUESTIONS = @json($questions);
    const TOTAL = {{ $total }};
    const ANSWERED_BEFORE = {{ $answeredCount }};
    const ANSWER_URL = @json(route('participant.quiz.answer', ['attempt' => '__ID__']));
    const DASHBOARD_URL = @json(route('participant.dashboard'));
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const REDUCE = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const IS_PREVIEW = @json($isPreview ?? false);

    const $ = id => document.getElementById(id);
    const card = $('card'), btnSubmit = $('btnSubmit'), btnNext = $('btnNext');

    let idx = 0, locked = false, sel = null, correctCount = 0, gainedXp = 0, totalXp = {{ $totalXp }};

    function renderMath(el) {
        if (typeof renderMathInElement !== 'function') return;
        renderMathInElement(el, {
            delimiters: [
                {left: '\\(', right: '\\)', display: false}, {left: '\\[', right: '\\]', display: true},
                {left: '$$', right: '$$', display: true}, {left: '$', right: '$', display: false}
            ],
            throwOnError: false
        });
    }

    function countUp(el, to, ms = 900) {
        if (REDUCE) { el.textContent = to; return; }
        const from = parseInt(el.textContent) || 0, t0 = performance.now();
        (function step(t) {
            const p = Math.min((t - t0) / ms, 1);
            el.textContent = Math.round(from + (to - from) * (1 - Math.pow(1 - p, 3)));
            if (p < 1) requestAnimationFrame(step);
        })(t0);
    }

    function boom(opts) { if (window.confetti && !REDUCE) confetti(Object.assign({ particleCount: 120, spread: 70, origin: { y: .7 } }, opts)); }

    function drawDots() {
        $('dots').innerHTML = Array.from({ length: TOTAL }, (_, i) => {
            if (IS_PREVIEW) {
                const q = QUESTIONS[i];
                const dotClass = q && q.is_correct ? 'ok' : 'bad';
                return `<span class="quiz-dot ${dotClass}" data-d="${i}"></span>`;
            }
            return `<span class="quiz-dot ${i < ANSWERED_BEFORE ? 'done' : ''}" data-d="${i}"></span>`;
        }).join('');
    }
    function setDot(state) {
        document.querySelectorAll('.quiz-dot').forEach(d => d.classList.remove('active'));
        const d = document.querySelector(`.quiz-dot[data-d="${ANSWERED_BEFORE + idx}"]`);
        if (d) { d.classList.remove('active'); if (state) d.classList.add(state); else d.classList.add('active'); }
    }
    function setProgress() { $('progressBar').style.width = (((IS_PREVIEW ? idx : (ANSWERED_BEFORE + idx)) + 1) / TOTAL * 100) + '%'; }

    function optionsHtml(q) {
        if (q.type === 'multiple_benar_salah') {
            return q.options.map((opt, i) => `
                <div class="q-mbs" data-i="${i}">
                    <div class="q-mbs-text">${i + 1}. ${opt}</div>
                    <div class="q-mbs-btns">
                        <button type="button" class="q-mbs-btn" data-v="benar">Benar</button>
                        <button type="button" class="q-mbs-btn" data-v="salah">Salah</button>
                    </div>
                </div>`).join('');
        }
        return q.options.map((opt, i) => `
            <button type="button" class="q-opt" data-i="${i}">
                <span class="q-key">${String.fromCharCode(65 + i)}</span>
                <span class="q-body">${opt}</span>
            </button>`).join('');
    }

    function ready(q) {
        if (q.type === 'multiple_choice') return sel.size > 0;
        if (q.type === 'multiple_benar_salah') return Object.keys(sel).length === q.options.length;
        return sel !== null;
    }
    function payload(q) { return q.type === 'multiple_choice' ? [...sel] : sel; }

    function renderPreviewFeedback(q) {
        const correctSet = new Set((q.correct_indices || []).map(String));
        const userAns = q.user_answer;

        if (q.type === 'multiple_benar_salah') {
            document.querySelectorAll('.q-mbs').forEach(row => {
                const rowI = row.dataset.i;
                const userVal = userAns && userAns[rowI];
                const shouldVal = correctSet.has(rowI) ? 'benar' : 'salah';

                row.querySelectorAll('.q-mbs-btn').forEach(btn => {
                    btn.disabled = true;
                    btn.style.cursor = 'default';
                    if (btn.dataset.v === userVal) {
                        btn.classList.add(userVal === 'benar' ? 'sel-benar' : 'sel-salah');
                    }
                });
                row.classList.add(userVal === shouldVal ? 'is-correct' : 'is-wrong');
            });
        } else {
            document.querySelectorAll('.q-opt').forEach(o => {
                const i = o.dataset.i;
                o.disabled = true;
                o.style.cursor = 'default';
                const chosen = q.type === 'multiple_choice'
                    ? (Array.isArray(userAns) && userAns.map(String).includes(i))
                    : String(userAns) === String(i);

                if (chosen) {
                    o.classList.add('selected');
                }
                if (correctSet.has(i)) {
                    o.classList.add('is-correct');
                } else if (chosen) {
                    o.classList.add('is-wrong');
                }
            });
        }

        $('qFeedback').innerHTML = `
            <div class="q-banner ${q.is_correct ? 'ok' : 'bad'}">
                <i class="fas ${q.is_correct ? 'fa-check-circle' : 'fa-times-circle'}" style="font-size:1.6rem"></i>
                <span>${q.is_correct ? 'Jawaban Benar! (+' + q.xp_earned + ' XP)' : 'Jawaban Kurang Tepat (+0 XP)'}</span>
            </div>
            ${q.explanation ? `<div class="q-explain"><strong><i class="fas fa-lightbulb"></i> Pembahasan</strong><div style="margin-top:8px">${q.explanation}</div></div>` : ''}`;

        renderMath($('qFeedback'));
    }

    function render() {
        const q = QUESTIONS[idx];
        sel = q.type === 'multiple_choice' ? new Set() : (q.type === 'multiple_benar_salah' ? {} : null);
        locked = false;

        card.className = 'quiz-card'; void card.offsetWidth; card.classList.add('enter');
        $('qNumber').textContent = `Soal ${IS_PREVIEW ? (idx + 1) : (ANSWERED_BEFORE + idx + 1)} dari ${TOTAL}${IS_PREVIEW ? ' (Preview)' : ''}`;
        $('qText').innerHTML = q.question_text;
        const img = $('qImage');
        if (q.question_image) { img.src = q.question_image; img.classList.remove('hidden'); } else { img.classList.add('hidden'); }
        $('qOptions').innerHTML = optionsHtml(q);
        $('qFeedback').innerHTML = '';

        if (IS_PREVIEW) {
            locked = true;
            btnSubmit.classList.add('hidden');
            renderPreviewFeedback(q);
            btnNext.innerHTML = idx === QUESTIONS.length - 1 ? 'Selesai & Dashboard <i class="fas fa-home"></i>' : 'Lanjut <i class="fas fa-arrow-right"></i>';
            btnNext.classList.remove('hidden');
        } else {
            btnSubmit.classList.remove('hidden', 'btn-loading'); btnSubmit.disabled = true;
            btnNext.classList.add('hidden');
        }

        setProgress(); 
        if (!IS_PREVIEW) setDot(null);
        renderMath(card);
    }

    $('qOptions').addEventListener('click', e => {
        if (locked) return;
        const q = QUESTIONS[idx];
        const opt = e.target.closest('.q-opt');
        if (opt) {
            const i = opt.dataset.i;
            if (q.type === 'multiple_choice') {
                sel.has(i) ? sel.delete(i) : sel.add(i);
                opt.classList.toggle('selected');
            } else {
                document.querySelectorAll('.q-opt').forEach(o => o.classList.remove('selected'));
                opt.classList.add('selected'); sel = i;
            }
        }
        const mb = e.target.closest('.q-mbs-btn');
        if (mb) {
            const row = mb.closest('.q-mbs');
            sel[row.dataset.i] = mb.dataset.v;
            row.querySelectorAll('.q-mbs-btn').forEach(b => b.classList.remove('sel-benar', 'sel-salah'));
            mb.classList.add(mb.dataset.v === 'benar' ? 'sel-benar' : 'sel-salah');
        }
        btnSubmit.disabled = !ready(q);
    });

    btnSubmit.addEventListener('click', async () => {
        if (locked) return;
        locked = true; btnSubmit.disabled = true; btnSubmit.classList.add('btn-loading');
        const q = QUESTIONS[idx];
        try {
            const res = await fetch(ANSWER_URL.replace('__ID__', q.attempt_id), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ answer: payload(q) })
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                await Swal.fire({ icon: 'info', title: 'Oops', text: data.message || 'Gagal menyimpan jawaban.' });
                if ([403, 404, 409, 410].includes(res.status)) { location.href = DASHBOARD_URL; return; }
                locked = false; btnSubmit.disabled = false; btnSubmit.classList.remove('btn-loading');
                return;
            }
            showFeedback(q, data);
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Koneksi bermasalah', text: 'Coba lagi.' });
            locked = false; btnSubmit.disabled = false; btnSubmit.classList.remove('btn-loading');
        }
    });

    function showFeedback(q, data) {
        const correctSet = new Set((data.correct_indices || []).map(String));

        if (q.type === 'multiple_benar_salah') {
            document.querySelectorAll('.q-mbs').forEach(row => {
                const should = correctSet.has(row.dataset.i) ? 'benar' : 'salah';
                row.classList.add(sel[row.dataset.i] === should ? 'is-correct' : 'is-wrong');
            });
        } else {
            document.querySelectorAll('.q-opt').forEach(o => {
                const i = o.dataset.i;
                const chosen = q.type === 'multiple_choice' ? sel.has(i) : sel === i;
                if (correctSet.has(i)) o.classList.add('is-correct');
                else if (chosen) o.classList.add('is-wrong');
            });
        }

        setDot(data.is_correct ? 'ok' : 'bad');
        if (data.is_correct) { correctCount++; gainedXp += data.xp_earned; boom(); }
        totalXp = data.total_xp; countUp($('xpChip'), totalXp);

        $('qFeedback').innerHTML = `
            <div class="q-banner ${data.is_correct ? 'ok' : 'bad'}">
                <i class="fas ${data.is_correct ? 'fa-check-circle' : 'fa-times-circle'}" style="font-size:1.6rem"></i>
                <span>${data.is_correct ? 'Benar! +<span id="gainXp">0</span> XP' : 'Belum tepat, jangan menyerah!'}</span>
            </div>
            ${data.explanation ? `<div class="q-explain"><strong><i class="fas fa-lightbulb"></i> Pembahasan</strong><div style="margin-top:8px">${data.explanation}</div></div>` : ''}`;
        if (data.is_correct) countUp($('gainXp'), data.xp_earned, 700);
        renderMath($('qFeedback'));

        btnSubmit.classList.add('hidden');
        btnNext.innerHTML = idx === QUESTIONS.length - 1 ? 'Lihat Hasil <i class="fas fa-flag-checkered"></i>' : 'Lanjut <i class="fas fa-arrow-right"></i>';
        btnNext.classList.remove('hidden');
    }

    btnNext.addEventListener('click', () => {
        if (IS_PREVIEW && idx === QUESTIONS.length - 1) {
            location.href = DASHBOARD_URL;
            return;
        }
        card.classList.remove('enter'); card.classList.add('leave');
        setTimeout(() => { idx++; idx < QUESTIONS.length ? render() : summary(); }, REDUCE ? 0 : 300);
    });

    function summary() {
        $('progressBar').style.width = '100%';
        const ratio = correctCount / QUESTIONS.length;
        const stars = ratio === 1 ? 3 : (ratio >= .5 ? 2 : (correctCount > 0 ? 1 : 0));
        card.className = 'quiz-card'; void card.offsetWidth; card.classList.add('enter');
        card.innerHTML = `
            <div class="quiz-summary">
                <div class="quiz-trophy">${stars === 3 ? '🏆' : '🎯'}</div>
                <h2 style="font-family:'Outfit',sans-serif; margin:12px 0 4px;">Quiz hari ini selesai!</h2>
                <p style="color:#475569; margin:0 0 16px;">${correctCount} dari ${QUESTIONS.length} jawaban benar</p>
                <div>${[1, 2, 3].map(n => `<i class="fas fa-star quiz-star ${n <= stars ? 'on' : ''}" style="animation-delay:${n * .2}s"></i>`).join('')}</div>
                <p style="font-size:1.4rem; font-weight:800; color:#7c3aed; margin:20px 0;">+<span id="sumXp">0</span> XP</p>
                <a href="${DASHBOARD_URL}" class="btn-primary" style="text-decoration:none;"><i class="fas fa-home"></i> Kembali ke Dashboard</a>
            </div>`;
        countUp($('sumXp'), gainedXp, 1200);
        if (stars === 3) { boom({ particleCount: 200, spread: 100 }); }
    }

    drawDots();
    render();
})();
</script>
@endsection
