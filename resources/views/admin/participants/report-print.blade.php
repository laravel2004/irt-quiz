<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raport - {{ $reportCard->user->name }}</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3b82f6;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --purple: #8b5cf6;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --bg-light: #f8fafc;
            --border: #e2e8f0;
        }
        
        body {
            font-family: 'Outfit', sans-serif;
            color: var(--text-main);
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background: #f1f5f9;
        }

        .print-container {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            padding: 50px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            border-radius: 16px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--border);
            padding-bottom: 24px;
            margin-bottom: 32px;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .logo-area img {
            height: 60px;
            width: auto;
        }

        .logo-text h1 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--primary);
        }

        .logo-text p {
            margin: 0;
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .student-info {
            background: var(--bg-light);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 40px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            border: 1px solid var(--border);
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-item .label {
            font-size: 0.85rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .info-item .value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-main);
        }

        h2.section-title {
            font-size: 1.3rem;
            color: var(--text-main);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 8px;
            display: inline-block;
            margin-bottom: 24px;
        }

        /* Chart Styles */
        .chart-container {
            margin-bottom: 40px;
        }

        .bar-row {
            margin-bottom: 20px;
        }

        .bar-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .bar-track {
            height: 24px;
            background: var(--bg-light);
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
        }

        .bar-segment {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.75rem;
            font-weight: 600;
            transition: width 0.5s ease;
        }

        .bar-correct { background-color: var(--success); }
        .bar-incorrect { background-color: var(--danger); }

        .legend {
            display: flex;
            gap: 16px;
            justify-content: flex-end;
            margin-bottom: 16px;
            font-size: 0.9rem;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .legend-color {
            width: 14px;
            height: 14px;
            border-radius: 4px;
        }

        /* AI Analysis Styles */
        .ai-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }
        
        .ai-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 12px;
        }

        .ai-header h2 {
            margin: 0;
            font-size: 1.4rem;
            color: var(--text-main);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .ai-header i {
            color: var(--primary);
        }

        .ai-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        .ai-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 24px;
            page-break-inside: avoid;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .ai-card.full-width {
            grid-column: 1 / -1;
        }

        .ai-card h4 {
            margin: 0 0 16px 0;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 12px;
        }

        .ai-card p {
            margin: 0;
            white-space: pre-line;
            line-height: 1.6;
            color: var(--text-main);
            text-align: justify;
        }

        .card-ringkasan h4 { color: var(--primary); }
        .card-kelebihan h4 { color: var(--success); }
        .card-kekurangan h4 { color: var(--danger); }
        .card-rekomendasi h4 { color: var(--purple); }

        /* Print Specific Styles */
        @media print {
            body {
                background: #fff;
            }
            .print-container {
                box-shadow: none;
                margin: 0;
                padding: 0;
                width: 100%;
                max-width: 100%;
            }
            .no-print {
                display: none !important;
            }
            @page {
                margin: 1.5cm;
            }
            .bar-correct {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .bar-incorrect {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .ai-card {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>

    <!-- Tombol aksi untuk screen view -->
    <div class="no-print" style="text-align: center; padding: 20px; background: #fff; border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <button onclick="window.print()" style="background: var(--primary); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-family: 'Outfit'; font-weight: 600; font-size: 1rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fas fa-print"></i> Cetak PDF / Print Sekarang
        </button>
        <button onclick="window.close()" style="background: #fff; color: var(--text-main); border: 1px solid var(--border); padding: 12px 24px; border-radius: 8px; font-family: 'Outfit'; font-weight: 600; font-size: 1rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; margin-left: 12px;">
            <i class="fas fa-times"></i> Tutup Preview
        </button>
    </div>

    <div class="print-container">
        <!-- Header -->
        <div class="header">
            <div class="logo-area">
                <img src="{{ asset('img/logo.png') }}" alt="JagoBelajar Logo" onerror="this.src='https://ui-avatars.com/api/?name=JB&background=3b82f6&color=fff&size=60'">
                <div class="logo-text">
                    <h1>JagoBelajar Tryout</h1>
                    <p>Laporan Hasil Ujian & Evaluasi Peserta</p>
                </div>
            </div>
            <div style="text-align: right; color: var(--text-muted); font-size: 0.9rem;">
                <strong>Tanggal Cetak:</strong><br>
                {{ now()->format('d M Y, H:i') }} WIB
            </div>
        </div>

        <!-- Student Data -->
        <div class="student-info">
            <div class="info-item">
                <span class="label">Nama Lengkap</span>
                <span class="value">{{ $reportCard->user->name }}</span>
            </div>
            <div class="info-item">
                <span class="label">Email Peserta</span>
                <span class="value">{{ $reportCard->user->email }}</span>
            </div>
            <div class="info-item">
                <span class="label">Tanggal Generate Raport</span>
                <span class="value">{{ $reportCard->updated_at->format('d M Y, H:i') }} WIB</span>
            </div>
            <div class="info-item">
                <span class="label">Status Raport</span>
                <span class="value" style="color: var(--success);"><i class="fas fa-check-circle"></i> Selesai</span>
            </div>
        </div>

        @php 
            $reportData = $reportCard->report_data; 
        @endphp

        <!-- Grafik Performa (Horizontal Bar Chart) -->
        @if($reportData && count($reportData) > 0)
        <div class="chart-container">
            <h2 class="section-title"><i class="fas fa-chart-bar" style="margin-right: 8px;"></i> Grafik Performa per Mata Pelajaran</h2>
            
            <div class="legend">
                <div class="legend-item">
                    <div class="legend-color bar-correct"></div>
                    <span>Jawaban Benar</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color bar-incorrect"></div>
                    <span>Jawaban Salah</span>
                </div>
            </div>

            @foreach($reportData as $category)
                @php
                    $totalSoal = $category['total_soal'];
                    $totalBenar = $category['total_benar'];
                    $totalSalah = $category['total_salah'];
                    
                    $persenBenar = $totalSoal > 0 ? round(($totalBenar / $totalSoal) * 100) : 0;
                    $persenSalah = $totalSoal > 0 ? round(($totalSalah / $totalSoal) * 100) : 0;
                @endphp
                
                <div class="bar-row">
                    <div class="bar-label" style="font-size: 1.1rem; color: var(--primary);">
                        <strong>{{ $category['category_name'] }}</strong>
                        <span style="color: var(--text-muted); font-size: 0.95rem;">{{ $totalSoal }} Soal</span>
                    </div>
                    <div class="bar-track">
                        @if($persenBenar > 0)
                            <div class="bar-segment bar-correct" style="width: {{ $persenBenar }}%;" title="{{ $totalBenar }} Benar">
                                {{ $persenBenar > 5 ? $totalBenar . ' (' . $persenBenar . '%)' : '' }}
                            </div>
                        @endif
                        
                        @if($persenSalah > 0)
                            <div class="bar-segment bar-incorrect" style="width: {{ $persenSalah }}%;" title="{{ $totalSalah }} Salah">
                                {{ $persenSalah > 5 ? $totalSalah . ' (' . $persenSalah . '%)' : '' }}
                            </div>
                        @endif
                    </div>
                </div>

                @if(!empty($category['sub_categories']))
                    <div style="margin-left: 20px; margin-bottom: 24px; padding-left: 16px; border-left: 2px solid var(--border);">
                        @foreach($category['sub_categories'] as $sub)
                            @php
                                $subSoal = $sub['total_soal'];
                                $subBenar = $sub['total_benar'];
                                $subSalah = $sub['total_salah'];
                                
                                $subPersenBenar = $subSoal > 0 ? round(($subBenar / $subSoal) * 100) : 0;
                                $subPersenSalah = $subSoal > 0 ? round(($subSalah / $subSoal) * 100) : 0;
                            @endphp
                            
                            <div class="bar-row" style="margin-bottom: 12px;">
                                <div class="bar-label" style="font-size: 0.9rem; margin-bottom: 4px;">
                                    <span>{{ $sub['sub_category_name'] }}</span>
                                    <span style="color: var(--text-muted); font-size: 0.85rem;">{{ $subSoal }} Soal</span>
                                </div>
                                <div class="bar-track" style="height: 16px; border-radius: 8px;">
                                    @if($subPersenBenar > 0)
                                        <div class="bar-segment bar-correct" style="width: {{ $subPersenBenar }}%; font-size: 0.65rem;" title="{{ $subBenar }} Benar">
                                            {{ $subPersenBenar > 10 ? $subBenar : '' }}
                                        </div>
                                    @endif
                                    
                                    @if($subPersenSalah > 0)
                                        <div class="bar-segment bar-incorrect" style="width: {{ $subPersenSalah }}%; font-size: 0.65rem;" title="{{ $subSalah }} Salah">
                                            {{ $subPersenSalah > 10 ? $subSalah : '' }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endforeach
        </div>
        @endif

        <!-- AI Analysis Section -->
        @if($reportCard->ai_analysis_status === 'completed' && !empty($reportCard->ai_analysis))
            @php $ai = $reportCard->ai_analysis; @endphp
            <div class="ai-section">
                <div class="ai-header">
                    <i class="fas fa-brain" style="font-size: 1.8rem;"></i>
                    <h2>Analisis Raport Siswa</h2>
                </div>

                <div class="ai-grid">
                    @if(!empty($ai['ringkasan']))
                    <div class="ai-card card-ringkasan full-width">
                        <h4><i class="fas fa-chart-line"></i> Ringkasan Performa</h4>
                        <p>{{ $ai['ringkasan'] }}</p>
                    </div>
                    @endif

                    @if(!empty($ai['kelebihan']))
                    <div class="ai-card card-kelebihan">
                        <h4><i class="fas fa-arrow-up"></i> Kekuatan & Kelebihan</h4>
                        <p>{{ $ai['kelebihan'] }}</p>
                    </div>
                    @endif

                    @if(!empty($ai['kekurangan']))
                    <div class="ai-card card-kekurangan">
                        <h4><i class="fas fa-arrow-down"></i> Area Perbaikan (Kekurangan)</h4>
                        <p>{{ $ai['kekurangan'] }}</p>
                    </div>
                    @endif

                    @if(!empty($ai['rekomendasi']))
                    <div class="ai-card card-rekomendasi full-width">
                        <h4><i class="fas fa-lightbulb"></i> Rekomendasi Belajar</h4>
                        <p>{{ $ai['rekomendasi'] }}</p>
                    </div>
                    @endif
                </div>
            </div>
        @endif

    </div>

    <script>
        // Auto trigger print if ?print=1 is present in URL
        document.addEventListener("DOMContentLoaded", function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('print') === '1') {
                // Beri sedikit jeda agar font & css selesai render
                setTimeout(() => {
                    window.print();
                }, 800);
            }
        });
    </script>
</body>
</html>
