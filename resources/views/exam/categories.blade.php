<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Mata Pelajaran - {{ $session->name }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background-color: #f8fafc;
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            padding: 40px 20px;
        }
        .header {
            max-width: 900px;
            margin: 0 auto 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .hub-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .category-card {
            background: #f8fafc;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }
        .category-card:hover {
            background: #eff6ff;
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, 0.1);
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .status-not-started { background: #eff6ff; color: #475569; }
        .status-ongoing { background: #dbeafe; color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.2); }
        .status-finished { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        
        @media (max-width: 768px) {
            .category-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }
            .card-actions {
                width: 100%;
            }
            .card-actions button, .card-actions a {
                width: 100%;
                justify-content: center;
            }
        }
        
        @keyframes pulse-glow {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
            70% { box-shadow: 0 0 0 15px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
        .btn-finish-all {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #0f172a;
            height: 60px;
            font-size: 1.2rem;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            transition: all 0.3s ease;
            animation: pulse-glow 2s infinite;
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }
        .btn-finish-all:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
        }
    </style>
</head>
<body>

    @if(session('error'))
    <div style="max-width: 900px; margin: 0 auto 24px;">
        <div style="padding: 16px; background: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444; border-radius: 8px;">
            <p style="color: #ef4444; margin: 0; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
            </p>
        </div>
    </div>
    @endif

    <div class="header flex-stack-mobile">
        <div>
            <h2 style="font-family: 'Outfit', sans-serif; margin-bottom: 4px; font-size: 1.8rem;">Daftar Mata Pelajaran</h2>
            <p style="color: #475569; font-size: 0.95rem;">Peserta: <strong>{{ $participant->name }}</strong></p>
        </div>
        <div>
            <div class="glass" style="padding: 12px 20px; border-radius: 12px; display: inline-flex; align-items: center; gap: 12px;">
                <i class="fas fa-layer-group" style="color: var(--accent);"></i>
                <div>
                    <div style="font-size: 0.75rem; color: #475569;">Sesi Ujian</div>
                    <div style="font-weight: 600;">{{ $session->name }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="hub-container">
        @php
            $allFinished = true;
        @endphp

        @foreach($session->sessionCategories as $sc)
            @php
                $status = $categoryStatuses->get($sc->id);
                $isStarted = $status && $status->started_at;
                $isFinished = $status && $status->finished_at;
                if (!$isFinished) $allFinished = false;
            @endphp
            <div class="category-card">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div style="width: 56px; height: 56px; background: #eff6ff; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: var(--accent);">
                        <i class="fas fa-book"></i>
                    </div>
                    <div>
                        <h3 style="font-family: 'Outfit', sans-serif; margin-bottom: 8px; font-size: 1.2rem;">{{ $sc->category->name }}</h3>
                        <div style="display: flex; gap: 16px; font-size: 0.85rem; color: #475569; flex-wrap: wrap; margin-bottom: 8px;">
                            <span style="display: flex; align-items: center; gap: 6px;"><i class="fas fa-clock"></i> {{ $sc->duration }} Menit</span>
                            <span style="display: flex; align-items: center; gap: 6px;"><i class="fas fa-list-ol"></i> {{ $sc->total_questions }} Soal</span>
                        </div>
                        @if($isFinished)
                            <span class="status-badge status-finished"><i class="fas fa-check-circle"></i> Selesai Dikerjakan</span>
                        @elseif($isStarted)
                            <span class="status-badge status-ongoing"><i class="fas fa-spinner fa-spin"></i> Sedang Dikerjakan</span>
                        @else
                            <span class="status-badge status-not-started"><i class="fas fa-lock-open"></i> Belum Dikerjakan</span>
                        @endif
                    </div>
                </div>
                
                <div class="card-actions">
                    @if($isFinished)
                        <button class="btn-primary" disabled style="background: #eff6ff; color: #475569; cursor: not-allowed; border: none;">
                            <i class="fas fa-check"></i> Selesai
                        </button>
                    @elseif($isStarted)
                        <a href="{{ route('exam.main', ['code' => $session->code, 'id' => $sc->id]) }}" class="btn-primary" style="text-decoration: none; display: flex; align-items: center; gap: 8px; background: #3b82f6;">
                            Lanjutkan <i class="fas fa-arrow-right"></i>
                        </a>
                    @else
                        <form action="{{ route('exam.start_category', ['code' => $session->code, 'id' => $sc->id]) }}" method="POST" id="start-form-{{ $sc->id }}">
                            @csrf
                            <button type="button" class="btn-primary" onclick="confirmStart('{{ $sc->id }}', '{{ $sc->category->name }}', '{{ $sc->duration }}')">
                                Mulai Kerjakan <i class="fas fa-play" style="margin-left: 8px;"></i>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach

        <!-- Submit All Action -->
        <div style="margin-top: 40px; padding-top: 40px; border-top: 1px dashed rgba(255,255,255,0.1); text-align: center;">
            @if($allFinished)
                <h4 style="margin-bottom: 16px; color: #10b981;">Semua mata pelajaran telah selesai dikerjakan!</h4>
                <form action="{{ route('exam.finish', $session->code) }}" method="POST" id="finishSessionForm">
                    @csrf
                    <button type="button" class="btn-finish-all" onclick="confirmFinish()">
                        <i class="fas fa-flag-checkered" style="font-size: 1.3rem;"></i> Akhiri Ujian Sesi Ini
                    </button>
                </form>
            @else
                <p style="color: #475569; font-size: 0.9rem;">
                    Tombol "Akhiri Ujian Sesi" akan muncul setelah Anda menyelesaikan seluruh mata pelajaran di atas.
                </p>
            @endif
        </div>
    </div>

    <script>
        function confirmStart(formId, categoryName, duration) {
            Swal.fire({
                title: 'Mulai Ujian?',
                html: `Anda akan mengerjakan <strong>${categoryName}</strong>.<br><br>Waktu pengerjaan <strong>${duration} menit</strong> akan langsung berjalan dan tidak bisa dijeda.`,
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: 'transparent',
                confirmButtonText: 'Mulai Sekarang',
                cancelButtonText: 'Batal',
                background: '#ffffff',
                color: '#0f172a'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('start-form-' + formId).submit();
                }
            })
        }

        function confirmFinish() {
            Swal.fire({
                title: 'Akhiri Seluruh Ujian?',
                text: "Anda telah menyelesaikan semua mata pelajaran. Anda tidak dapat kembali ke ujian setelah mengakhiri sesi.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: 'transparent',
                confirmButtonText: 'Ya, Akhiri Ujian!',
                cancelButtonText: 'Batal',
                background: '#ffffff',
                color: '#0f172a'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Menyimpan Hasil...',
                        text: 'Mohon tunggu sebentar',
                        allowOutsideClick: false,
                        background: '#ffffff',
                        color: '#0f172a',
                        didOpen: () => {
                            Swal.showLoading()
                            
                            // Submit via AJAX
                            const form = document.getElementById('finishSessionForm');
                            fetch(form.action, {
                                method: 'POST',
                                body: new FormData(form),
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                                    'Accept': 'application/json'
                                }
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    window.location.href = "{{ route('exam.success', $session->code) }}";
                                } else {
                                    Swal.fire({icon: 'error', title: 'Gagal', text: 'Gagal mengakhiri ujian', background: '#ffffff', color: '#0f172a'});
                                }
                            });
                        }
                    });
                }
            })
        }
    </script>
</body>
</html>
