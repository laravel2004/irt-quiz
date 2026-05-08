<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - IRT Exam System</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
    
    <div class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <i class="fas fa-graduation-cap" style="font-size: 1.8rem; color: var(--accent);"></i>
            <h1>IRT EXAM</h1>
        </div>

        <ul class="nav-menu">
            <li class="nav-item">
                <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-th-large"></i>
                    <span>Hotspot</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.categories.index') }}" class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                    <i class="fas fa-tags"></i>
                    <span>Kategori Soal</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.questions.index') }}" class="nav-link {{ request()->routeIs('admin.questions.*') ? 'active' : '' }}">
                    <i class="fas fa-book"></i>
                    <span>Bank Soal</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.sessions.index') }}" class="nav-link {{ request()->routeIs('admin.sessions.*') ? 'active' : '' }}">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Sesi Ujian</span>
                </a>
            </li>
            <!-- <li class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fas fa-user-plus"></i>
                    <span>Enrollment</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fas fa-calculator"></i>
                    <span>Penilaian IRT</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fas fa-users"></i>
                    <span>Manajemen User</span>
                </a>
            </li> -->
        </ul>

        <div style="margin-top: auto; padding-top: 24px; border-top: 1px solid var(--glass-border);">
            <a href="{{ route('login') }}" class="nav-link" style="color: #ef4444;">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <div class="mobile-header">
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-graduation-cap" style="font-size: 1.2rem; color: var(--accent);"></i>
            <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.1rem; font-weight: 700; margin: 0;">IRT EXAM</h1>
        </div>
    </div>

    <div class="main-content">
        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
            <div>
                <h2 style="font-family: 'Outfit', sans-serif; font-size: 1.5rem;">@yield('header_title', 'Overview')</h2>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">Welcome back, Administrator</p>
            </div>
            <div style="display: flex; align-items: center; gap: 20px;">
                <div style="position: relative;">
                    <i class="fas fa-bell" style="color: var(--text-secondary); font-size: 1.2rem; cursor: pointer;"></i>
                    <span style="position: absolute; top: -5px; right: -5px; width: 10px; height: 10px; background: #ef4444; border-radius: 50%; border: 2px solid var(--bg-dark);"></span>
                </div>
                <div class="glass" style="display: flex; align-items: center; gap: 12px; padding: 8px 16px; border-radius: 50px; cursor: pointer;">
                    <img src="https://ui-avatars.com/api/?name=Admin+IRT&background=3b82f6&color=fff" alt="Avatar" style="width: 32px; height: 32px; border-radius: 50%;">
                    <span style="font-weight: 500; font-size: 0.9rem;">Admin IRT</span>
                    <i class="fas fa-chevron-down" style="font-size: 0.8rem; color: var(--text-secondary);"></i>
                </div>
            </div>
        </header>

        @yield('content')
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Custom Confirm Modal -->
    <div class="modal-overlay confirm-modal" id="confirmModal">
        <div class="modal-content glass animate-fade-in">
            <div class="confirm-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 style="margin-bottom: 12px; font-family: 'Outfit', sans-serif;">Apakah Anda yakin?</h3>
            <p id="confirmMessage" style="color: var(--text-secondary); margin-bottom: 32px;">Tindakan ini tidak dapat dibatalkan.</p>
            <div style="display: flex; gap: 12px;">
                <button type="button" class="btn-primary" style="background: transparent; border: 1px solid var(--glass-border); color: var(--text-secondary); flex: 1;" onclick="closeConfirm(false)">Batal</button>
                <button type="button" id="confirmBtn" class="btn-primary" style="background: #ef4444; flex: 1;" onclick="closeConfirm(true)">Ya, Hapus</button>
            </div>
        </div>
    </div>

    @stack('scripts')
    <script>
        // Toast System
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                <span>${message}</span>
            `;
            container.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('fade-out');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Custom Confirm System
        let confirmCallback = null;
        function customConfirm(message, callback, btnText = 'Ya, Hapus', btnColor = '#ef4444') {
            document.getElementById('confirmMessage').innerText = message;
            document.getElementById('confirmBtn').innerText = btnText;
            document.getElementById('confirmBtn').style.background = btnColor;
            document.getElementById('confirmModal').classList.add('active');
            confirmCallback = callback;
        }

        function closeConfirm(result) {
            document.getElementById('confirmModal').classList.remove('active');
            if (result && confirmCallback) {
                confirmCallback();
            }
            confirmCallback = null;
        }

        // Mobile Sidebar Toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            
            // Prevent body scroll when sidebar is open
            if (sidebar.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = 'auto';
            }
        }
    </script>
</body>
</html>
