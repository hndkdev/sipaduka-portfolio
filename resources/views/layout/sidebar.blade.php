<aside class="sidebar bg-primary text-white p-3 d-flex flex-column" id="sidebar">
    {{-- Tombol Close (Mobile) --}}
    <button class="btn btn-link text-white p-0 mb-3 d-lg-none align-self-end" id="sidebarClose">
        <i class="fas fa-times fa-lg"></i>
    </button>

    {{-- LOGO --}}
    <div class="text-center mb-4 flex-shrink-0">
        <img 
             src="{{ asset('images/logo.jpg') }}" 
            alt="Logo SIPADUKA"
            class="img-fluid mb-2 border border-3 border-white sidebar-logo"
        >
        <h5 class="mb-1 fw-bold sidebar-title">SIPADUKA</h5>
        <small class="text-white opacity-75 sidebar-subtitle">Sistem Pendataan Penduduk & Keluarga
            <p>Kelurahan Panunggangan Barat.</p>

        </small>
    </div>

    {{-- Menu Navigation --}}
    <nav class="flex-grow-1 overflow-auto mb-3">
        <ul class="nav flex-column gap-1">
            {{-- ================= ADMIN ================= --}}
            @if(auth()->check() && auth()->user()->role === 'admin')
                <li class="nav-item">
                    <a href="{{ url('/admin') }}" 
                       class="nav-link text-white py-2 px-3 rounded {{ request()->is('admin') ? 'active bg-white bg-opacity-25' : '' }}">
                        <i class="fas fa-tachometer-alt sidebar-icon"></i> 
                        <span class="sidebar-text">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('rw.index') }}" 
                       class="nav-link text-white py-2 px-3 rounded {{ request()->routeIs('rw.index') ? 'active bg-white bg-opacity-25' : '' }}">
                        <i class="fas fa-users-between-lines sidebar-icon"></i> 
                        <span class="sidebar-text">Data RW</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('users.index') }}" 
                       class="nav-link text-white py-2 px-3 rounded {{ request()->routeIs('users.index') ? 'active bg-white bg-opacity-25' : '' }}">
                        <i class="fas fa-user-gear sidebar-icon"></i> 
                        <span class="sidebar-text">Manajemen User</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('keluarga-appscript.index') }}" 
                       class="nav-link text-white py-2 px-3 rounded {{ request()->routeIs('keluarga-appscript.index') ? 'active bg-white bg-opacity-25' : '' }}">
                        <i class="fas fa-house-user sidebar-icon"></i> 
                        <span class="sidebar-text">Data Keluarga</span>
                    </a>
                </li>

                {{-- Divider --}}
                <li class="nav-divider">
                    <hr class="my-2 opacity-25">
                </li>

                {{-- Ganti Password --}}
                <li class="nav-item">
                    <a href="{{ route('password.change') }}" 
                       class="nav-link text-white py-2 px-3 rounded {{ request()->routeIs('password.change') ? 'active bg-white bg-opacity-25' : '' }}">
                        <i class="fas fa-key sidebar-icon"></i> 
                        <span class="sidebar-text">Ganti Password</span>
                    </a>
                </li>
            @endif

            {{-- ================= RW ================= --}}
            @if(auth()->check() && auth()->user()->role === 'rw')
                <li class="nav-item">
                    <a href="{{ route('rw.dashboard') }}" 
                       class="nav-link text-white py-2 px-3 rounded {{ request()->routeIs('rw.dashboard') ? 'active bg-white bg-opacity-25' : '' }}">
                        <i class="fas fa-tachometer-alt sidebar-icon"></i> 
                        <span class="sidebar-text">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('keluarga-appscript.index') }}" 
                       class="nav-link text-white py-2 px-3 rounded {{ request()->routeIs('keluarga-appscript.index') ? 'active bg-white bg-opacity-25' : '' }}">
                        <i class="fas fa-house-user sidebar-icon"></i> 
                        <span class="sidebar-text">Data Keluarga</span>
                    </a>
                </li>

                {{-- Divider --}}
                <li class="nav-divider">
                    <hr class="my-2 opacity-25">
                </li>

                {{-- Ganti Password --}}
                <li class="nav-item">
                    <a href="{{ route('password.change') }}" 
                       class="nav-link text-white py-2 px-3 rounded {{ request()->routeIs('password.change') ? 'active bg-white bg-opacity-25' : '' }}">
                        <i class="fas fa-key sidebar-icon"></i> 
                        <span class="sidebar-text">Ganti Password</span>
                    </a>
                </li>
            @endif
        </ul>
    </nav>

    {{-- Logout Section --}}
    <div class="mt-auto flex-shrink-0">
        <hr class="text-white opacity-25 my-3">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-outline-light w-100 py-2">
                <i class="fas fa-sign-out-alt sidebar-icon"></i> 
                <span class="sidebar-text">Logout</span>
            </button>
        </form>
    </div>
</aside>

<style>
    /* Sidebar Base */
    .sidebar {
        position: fixed;
        top: 0;
        height: 100vh;
        overflow-y: auto;
        overflow-x: hidden;
        z-index: 1000;
        transition: all 0.3s ease;
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    }

    /* Desktop - Sidebar Tetap Terlihat */
    @media (min-width: 992px) {
        .sidebar {
            left: 0;
            width: 260px;
        }
        .sidebar-logo {
            width: 80px !important;
            height: 80px !important;
            object-fit: cover;
        }
        .sidebar-title,
        .sidebar-subtitle,
        .sidebar-text {
            display: block;
        }
        .sidebar-icon {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
    }

    /* Tablet - Sidebar Hidden */
    @media (min-width: 768px) and (max-width: 991.98px) {
        .sidebar {
            left: -250px;
            width: 250px;
        }
        .sidebar.active {
            left: 0;
        }
        .sidebar-logo {
            width: 60px !important;
            height: 60px !important;
        }
    }

    /* Mobile - Sidebar Hidden */
    @media (max-width: 767.98px) {
        .sidebar {
            left: -280px;
            width: 280px;
            padding: 1rem !important;
        }
        .sidebar.active {
            left: 0;
        }
        .sidebar-logo {
            width: 70px !important;
            height: 70px !important;
        }
    }

    /* Nav Divider */
    .nav-divider {
        list-style: none;
    }

    .nav-divider hr {
        border-color: rgba(255, 255, 255, 0.2);
    }

    /* Hover Effects */
    .sidebar .nav-link {
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
    }

    .sidebar .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.15) !important;
        transform: translateX(3px);
    }

    .sidebar .nav-link.active {
        font-weight: 600;
        background-color: rgba(255, 255, 255, 0.25) !important;
    }

    /* Scrollbar */
    .sidebar::-webkit-scrollbar {
        width: 5px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.3);
        border-radius: 3px;
    }

    .sidebar::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.5);
    }
</style>