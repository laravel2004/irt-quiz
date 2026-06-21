<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'JagoBelajar Tryout')</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <!-- KaTeX for math rendering -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.css">
    <script src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/contrib/auto-render.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --bg-dark: #f8fafc;
            --bg-card: #ffffff;
            --accent: #2563eb;
            --accent-rgb: 37, 99, 235;
            --text-secondary: #475569;
            --glass-border: #e2e8f0;
        }

        body {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            color: #0f172a;
            font-family: 'Inter', sans-serif;
            margin: 0;
            min-height: 100vh;
        }

        .glass {
            background: #ffffff;
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        }

        .btn-primary {
            background: var(--accent);
            color: #ffffff;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            font-family: 'Outfit', sans-serif;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(var(--accent-rgb), 0.18);
        }

        .form-input {
            width: 100%;
            background: #ffffff;
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 14px 16px;
            color: #0f172a;
            font-family: inherit;
            transition: all 0.3s;
            box-sizing: border-box;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--accent);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 0.9rem; color: var(--text-secondary); }

        .badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            background: #dbeafe;
            color: #1d4ed8;
        }
        .badge.active { background: #dcfce7; color: #166534; }

        .animate-fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card-hover:hover {
            transform: translateY(-5px);
            border-color: var(--accent);
            box-shadow: 0 15px 30px rgba(15, 23, 42, 0.12);
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(8px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }
        .chart-wrap {
            position: relative;
            width: 100%;
            height: 320px;
        }

        @media (max-width: 768px) {
            .chart-wrap {
                height: 240px;
            }

            .chart-legend-stack {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .chart-header-stack {
                flex-direction: column !important;
                align-items: flex-start !important;
            }

            .chart-header-stack .badge {
                white-space: normal !important;
            }
        }
    </style>
</head>
<body>
    @yield('content')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            if (typeof renderMathInElement === 'function') {
                renderMathInElement(document.body, {
                    delimiters: [
                        {left: '\\(', right: '\\)', display: false},
                        {left: '\\[', right: '\\]', display: true},
                        {left: '$$', right: '$$', display: true},
                        {left: '$', right: '$', display: false}
                    ],
                    throwOnError: false
                });
            }
        });
    </script>
</body>
</html>
