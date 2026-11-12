<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SIPADUKA')</title>

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>

    {{-- Tombol Hamburger (Mobile & Tablet) --}}
    <button class="btn btn-primary hamburger-btn d-lg-none" type="button" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    {{-- Sidebar --}}
    @include('layout.sidebar')

    {{-- Overlay untuk Mobile --}}
    <div class="sidebar-overlay d-lg-none" id="sidebarOverlay"></div>

    {{-- Main Content --}}
    <main class="main-content p-4">
        @yield('content')
    </main>

    @stack('scripts')

    <style>
        body {
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        /* Hamburger Button */
        .hamburger-btn {
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1001;
            width: 45px;
            height: 45px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .hamburger-btn:hover {
            transform: scale(1.05);
        }

        /* Sidebar Overlay */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Main Content Responsive */
        .main-content {
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        /* Desktop */
        @media (min-width: 992px) {
            .main-content {
                margin-left: 260px;
            }
        }

        /* Tablet & Mobile */
        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0;
                padding-top: 70px !important;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarClose = document.getElementById('sidebarClose');
            const sidebarOverlay = document.getElementById('sidebarOverlay');

            function openSidebar() {
                sidebar.classList.add('active');
                sidebarOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeSidebar() {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', openSidebar);
            }

            if (sidebarClose) {
                sidebarClose.addEventListener('click', closeSidebar);
            }

            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', closeSidebar);
            }

            // Auto close saat klik menu (mobile)
            const navLinks = sidebar.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth < 992) {
                        closeSidebar();
                    }
                });
            });
        });
    </script>
</body>
</html>