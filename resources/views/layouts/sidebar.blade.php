<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'CTM'))</title>

    @auth
        <script>
            window.__ctm = {
                userId: @json(auth()->id()),
                role: @json(auth()->user()->role ?? null),
                categoryId: @json(auth()->user()->category_id ?? null),
            };
        </script>
    @endauth

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @vite(['resources/js/app.js'])

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */
        .sidebar {
            width: 260px;
            background: linear-gradient(135deg, #1e3a8a 0%, #2d5a96 100%);
            color: white;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            overflow-y: auto;
            padding: 20px 0;
            box-shadow: 2px 0 8px rgba(0,0,0,0.1);
            z-index: 1000;
            transition: transform 0.3s ease-in-out;
        }

        .sidebar.hidden-mobile {
            transform: translateX(-100%);
        }

        .hamburger-btn {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1100;
            background: #1e3a8a;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 12px;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .hamburger-btn i {
            font-size: 20px;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        .sidebar-overlay.active {
            display: block;
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
            text-align: center;
        }

        .sidebar-header h4 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            font-size: 12px;
            opacity: 0.8;
            margin: 0;
        }

        .sidebar-nav {
            list-style: none;
        }

        .sidebar-nav li {
            margin: 0;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 12px 14px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: background-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
            margin: 4px 12px;
            border-radius: 12px;
            position: relative;
        }

        .sidebar-nav a::before {
            content: '';
            position: absolute;
            left: 0;
            top: 10px;
            bottom: 10px;
            width: 3px;
            border-radius: 999px;
            background: transparent;
        }

        .sidebar-nav a:hover {
            background-color: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(2px);
        }

        .sidebar-nav a:hover::before {
            background: #fbbf24;
        }

        .sidebar-nav a.active {
            background-color: rgba(255,255,255,0.15);
            color: white;
            font-weight: 500;
        }

        .sidebar-nav a.active::before {
            background: #fbbf24;
        }

        .sidebar-nav i {
            width: 22px;
            margin-right: 0;
            text-align: center;
        }

        .sidebar-nav span {
            margin-left: 12px;
        }

        .sidebar-divider {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            background: rgba(0,0,0,0.1);
        }

        .sidebar-footer a {
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
            padding: 10px;
            border-radius: 4px;
            transition: all 0.3s;
        }

        .sidebar-footer a:hover {
            background-color: rgba(255,255,255,0.15);
        }

        /* MAIN CONTENT */
        .main-wrapper {
            flex: 1;
            margin-left: 260px;
            display: flex;
            flex-direction: column;
        }

        /* TOP HEADER */
        .topbar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 500;
        }

        .topbar-title {
            font-size: 20px;
            font-weight: 600;
            color: #1e3a8a;
            margin: 0;
        }

        .topbar-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            background: #f3f4f6;
            border-radius: 20px;
            font-size: 14px;
        }

        .user-name {
            font-weight: 500;
            color: #1f2937;
        }

        /* CONTENT AREA */
        .content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }

        /* ALERTS */
        .alert {
            border: none;
            border-radius: 6px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        /* RESPONSIVE */
        @media (max-width: 992px) {
            .sidebar {
                width: 220px;
            }

            .main-wrapper {
                margin-left: 220px;
            }

            .content {
                padding: 20px;
            }

            .topbar {
                padding: 12px 20px;
            }

            .topbar-title {
                font-size: 18px;
            }

            /* Table responsive */
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            table {
                min-width: 600px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 260px;
                transform: translateX(-100%);
            }

            .sidebar.show-mobile {
                transform: translateX(0);
            }

            .hamburger-btn {
                display: block;
            }

            .main-wrapper {
                margin-left: 0;
            }

            .sidebar-footer {
                position: relative;
                border-top: 1px solid rgba(255,255,255,0.1);
            }

            .content {
                padding: 15px;
            }

            .topbar {
                padding: 12px 15px;
                padding-left: 60px; /* Space for hamburger */
            }

            .topbar-title {
                font-size: 16px;
            }

            /* Button groups */
            .btn-group {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
            }

            .btn-sm {
                font-size: 12px;
                padding: 4px 8px;
            }

            /* Cards */
            .card {
                margin-bottom: 15px;
            }

            /* Grid columns - stack on mobile */
            .col-lg-8, .col-lg-4, .col-md-6 {
                width: 100%;
                padding: 0 15px;
            }

            .row.g-3 {
                gap: 15px 0;
            }
        }

        @media (max-width: 576px) {
            .topbar {
                flex-direction: column;
                gap: 8px;
                padding: 12px;
            }

            .topbar-title {
                width: 100%;
                text-align: center;
                font-size: 15px;
            }

            .topbar-user {
                width: 100%;
                justify-content: center;
            }

            .user-badge {
                font-size: 12px;
                padding: 5px 10px;
            }

            .content {
                padding: 10px;
            }

            /* Tables - scroll horizontal on very small screens */
            table {
                font-size: 13px;
            }

            table th, table td {
                padding: 8px 6px;
            }

            /* Stack buttons vertically */
            .d-flex.gap-2, .d-flex.gap-3 {
                flex-direction: column;
                width: 100%;
            }

            .btn {
                width: 100%;
                margin-bottom: 8px;
            }

            /* Panel adjustments */
            .panel {
                border-radius: 10px;
            }

            .panel-header {
                font-size: 14px;
                padding: 10px 12px;
            }

            /* Alert adjustments */
            .alert {
                font-size: 13px;
                padding: 10px;
            }

            /* Form controls */
            .form-control, .form-select {
                font-size: 14px;
            }

            /* Badge sizing */
            .badge {
                font-size: 11px;
            }
        }

        @media (max-width: 576px) {
            .topbar {
                flex-direction: column;
                gap: 8px;
                padding: 12px;
                padding-left: 55px;
            }

            .topbar-title {
                width: 100%;
                text-align: center;
                font-size: 15px;
            }

            .topbar-user {
                width: 100%;
                justify-content: center;
            }

            .user-badge {
                font-size: 12px;
                padding: 5px 10px;
            }

            .content {
                padding: 10px;
            }

            table {
                font-size: 12px;
            }

            table th, table td {
                padding: 8px 6px;
            }

            .btn {
                width: 100%;
                margin-bottom: 8px;
                font-size: 13px;
            }

            .form-control, .form-select {
                font-size: 14px;
            }

            .badge {
                font-size: 11px;
            }

            .sidebar-nav a {
                padding: 10px 15px;
                font-size: 14px;
            }
        }

        @media (max-width: 400px) {
            .sidebar-header h4 {
                font-size: 15px;
            }

            .topbar-title {
                font-size: 13px;
            }

            .btn {
                font-size: 12px;
                padding: 6px 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Hamburger Button -->
    <button class="hamburger-btn" id="hamburger-btn" onclick="toggleSidebar()">
        <i class="fa-solid fa-bars"></i>
    </button>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h4><i class="fa-solid fa-cloud me-2"></i>CTM</h4>
            <p>Cloud Ticketing Manufacturing</p>
        </div>

        <ul class="sidebar-nav">
            <li>
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                @auth
                    @if(auth()->user()->role !== 'super_admin')
                        <a href="{{ route('tickets.index') }}" class="nav-link {{ request()->routeIs('tickets.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-ticket"></i>
                            <span>Tiket</span>
                            @php
                                $openCount = (int) ($sidebarOpenTicketsCount ?? 0);
                                $showOpenBadge = in_array((auth()->user()->role ?? null), ['admin', 'customer'], true);
                            @endphp
                            <span id="sidebar-open-badge" class="badge bg-danger ms-auto" style="{{ (!$showOpenBadge || $openCount <= 0) ? 'display:none;' : '' }}">
                                <span id="sidebar-open-count">{{ $openCount }}</span>
                            </span>
                        </a>
                    @endif
                @else
                    <a href="{{ route('tickets.index') }}" class="nav-link {{ request()->routeIs('tickets.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-ticket"></i>
                        <span>Tiket</span>
                    </a>
                @endauth
            </li>

            @auth
                @if(auth()->user()->role !== 'super_admin')
                    <li>
                        <a href="{{ route('discussions.index') }}" class="nav-link {{ request()->routeIs('discussions.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-comments"></i>
                            <span>Diskusi</span>
                            @php $unread = (int) ($sidebarUnreadDiscussionCount ?? 0); @endphp
                            <span id="sidebar-unread-badge" class="badge bg-danger ms-auto" style="{{ ($unread <= 0) ? 'display:none;' : '' }}">
                                <span id="sidebar-unread-count">{{ $unread }}</span>
                            </span>
                        </a>
                    </li>
                @endif
            @endauth

            @auth
                @if(auth()->user()->role === 'customer')
                    <li>
                        <a href="{{ route('tickets.create') }}" class="nav-link {{ request()->routeIs('tickets.create') ? 'active' : '' }}">
                            <i class="fa-solid fa-circle-plus"></i>
                            <span>Buat Tiket</span>
                        </a>
                    </li>
                @endif

                @if(auth()->user()->role !== 'super_admin')
                    <li>
                        <a href="{{ route('barcode.scan') }}" class="nav-link {{ request()->routeIs('barcode.scan') ? 'active' : '' }}">
                            <i class="fa-solid fa-qrcode"></i>
                            <span>Scan Barcode</span>
                        </a>
                    </li>
                @endif

                {{-- Edit Profile visible to all authenticated users --}}
                <li>
                    <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-user-pen"></i>
                        <span>Edit Profile</span>
                    </a>
                </li>

                @if(auth()->user()->role === 'super_admin')
                    <li class="sidebar-divider">
                        <a href="{{ route('admin.users.index', ['role' => 'admin']) }}" class="nav-link {{ request()->routeIs('admin.users.*') && request('role') === 'admin' ? 'active' : '' }}">
                            <i class="fa-solid fa-user-shield"></i>
                            <span>Kelola Admin</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.users.index', ['role' => 'agent']) }}" class="nav-link {{ request()->routeIs('admin.users.*') && request('role') === 'agent' ? 'active' : '' }}">
                            <i class="fa-solid fa-user-gear"></i>
                            <span>Daftar Agent</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.users.index', ['role' => 'customer']) }}" class="nav-link {{ request()->routeIs('admin.users.*') && request('role') === 'customer' ? 'active' : '' }}">
                            <i class="fa-solid fa-users"></i>
                            <span>Daftar Customer</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.categories.index') }}" class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-list"></i>
                            <span>Kategori</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.ticketReports.index') }}" class="nav-link {{ request()->routeIs('admin.ticketReports.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-file-lines"></i>
                            <span>Laporan Tiket</span>
                        </a>
                    </li>
                @endif

                @if(auth()->user()->role === 'admin')
                    <li class="sidebar-divider">
                        <a href="{{ route('admin.technicians.index') }}" class="nav-link {{ (request()->routeIs('admin.technicians.index') || request()->routeIs('admin.technicians.create') || request()->routeIs('admin.technicians.edit') || request()->routeIs('admin.technicians.store') || request()->routeIs('admin.technicians.update') || request()->routeIs('admin.technicians.destroy')) ? 'active' : '' }}">
                            <i class="fa-solid fa-user-gear"></i>
                            <span>Kelola Teknisi</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.technicians.tracking') }}" class="nav-link {{ request()->routeIs('admin.technicians.tracking') ? 'active' : '' }}">
                            <i class="fa-solid fa-chart-line"></i>
                            <span>Tracking Teknisi</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.reports.index') }}" class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-file-lines"></i>
                            <span>Laporan</span>
                        </a>
                    </li>
                @endif
            @endauth
        </ul>

        <!-- SIDEBAR FOOTER -->
        <div class="sidebar-footer">
            @auth
                <div style="color: white; font-size: 13px; margin-bottom: 10px; text-align: center;">
                    <div class="fw-semibold">{{ auth()->user()->name }}</div>
                    @php
                        $role = auth()->user()->role;
                    @endphp
                    @if($role === 'super_admin')
                        <span style="font-size: 11px; opacity: 0.8;"><i class="fa-solid fa-crown me-1"></i>Super Admin</span>
                    @elseif($role === 'admin')
                        <span style="font-size: 11px; opacity: 0.8;"><i class="fa-solid fa-crown me-1"></i>Admin</span>
                    @elseif($role === 'agent')
                        <span style="font-size: 11px; opacity: 0.8;"><i class="fa-solid fa-headset me-1"></i>Agent</span>
                    @else
                        <span style="font-size: 11px; opacity: 0.8;"><i class="fa-solid fa-user me-1"></i>Customer</span>
                    @endif
                </div>
                <form method="POST" action="{{ route('logout') }}" class="d-block" id="logoutForm">
                    @csrf
                    <button type="button" class="btn btn-sm btn-light w-100" onclick="confirmLogout()">
                        <i class="fa-solid fa-right-from-bracket me-2"></i>Logout
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="btn btn-sm btn-light w-100">
                    <i class="fa-solid fa-right-to-bracket me-2"></i>Login
                </a>
            @endauth
        </div>
    </aside>

    <!-- MAIN WRAPPER -->
    <div class="main-wrapper">
        <!-- TOP HEADER -->
        <div class="topbar">
            <h2 class="topbar-title">@yield('page-title', 'Dashboard')</h2>
            <div class="topbar-user">
                @auth
                    <div class="user-badge">
                        <i class="fa-solid fa-circle-user"></i>
                        <span class="user-name">{{ auth()->user()->name }}</span>
                    </div>
                    @php
                        $jobdesk = null;
                        try {
                            $jobdesk = auth()->user()->category->name ?? null;
                        } catch (\Throwable $e) {
                            $jobdesk = null;
                        }
                    @endphp
                    @if(in_array(auth()->user()->role ?? null, ['admin','agent'], true))
                        <span class="badge text-bg-light text-dark" title="Jobdesk/Kategori">
                            <i class="fa-solid fa-tags me-1"></i>
                            {{ (auth()->user()->role ?? '') === 'admin' ? 'Admin' : 'Teknisi' }}
                            @if(!empty($jobdesk ?? null))
                                — {{ $jobdesk ?? '' }}
                            @endif
                        </span>
                    @endif
                @endauth
            </div>
        </div>

        <!-- CONTENT -->
        <div class="content">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i>
                    <strong>Berhasil!</strong> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i>
                    <strong>Error!</strong> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');

            if (sidebar.classList.contains('show-mobile')) {
                sidebar.classList.remove('show-mobile');
                overlay.classList.remove('active');
            } else {
                sidebar.classList.add('show-mobile');
                overlay.classList.add('active');
            }
        }

        // Close sidebar when clicking on a link (mobile)
        if (window.innerWidth <= 768) {
            document.querySelectorAll('.sidebar-nav a').forEach(link => {
                link.addEventListener('click', () => {
                    const sidebar = document.getElementById('sidebar');
                    const overlay = document.getElementById('sidebar-overlay');
                    sidebar.classList.remove('show-mobile');
                    overlay.classList.remove('active');
                });
            });
        }

        function confirmLogout() {
            Swal.fire({
                title: 'Apakah Anda ingin logout?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Logout',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('logoutForm').submit();
                }
            });
        }

        // Show success alert if logout successful
        @if(session('logout_success'))
            Swal.fire({
                icon: 'success',
                title: 'Anda berhasil logout',
                showConfirmButton: false,
                timer: 1500
            });
        @endif
    </script>

    @php
        $fn = session('floating_notification');
        $fnType = is_array($fn) ? ($fn['type'] ?? 'info') : 'info';
        $fnTitle = is_array($fn) ? ($fn['title'] ?? null) : null;
        $fnMessage = is_array($fn) ? ($fn['message'] ?? null) : null;
        $fnSound = is_array($fn) ? ($fn['sound'] ?? null) : null;

        $toastBg = match ($fnType) {
            'success' => 'success',
            'danger', 'error' => 'danger',
            'warning' => 'warning',
            default => 'info',
        };
    @endphp

    <audio id="notifSoundNew" preload="auto" src="{{ \Illuminate\Support\Facades\Vite::asset('resources/music/message1.mp3') }}"></audio>
    <audio id="notifSoundStatus" preload="auto" src="{{ \Illuminate\Support\Facades\Vite::asset('resources/music/message2.mp3') }}"></audio>

    @if(!empty($fnTitle) || !empty($fnMessage))
        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 2000;">
            <div id="floatingToast" class="toast align-items-center text-bg-{{ $toastBg }} border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4500">
                <div class="d-flex">
                    <div class="toast-body">
                        @if(!empty($fnTitle))
                            <div class="fw-semibold">{{ $fnTitle }}</div>
                        @endif
                        @if(!empty($fnMessage))
                            <div>{{ $fnMessage }}</div>
                        @endif
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>
    @endif

    @yield('scripts')

    @auth
        <script>
            (function () {
                const liveUrl = @json(route('live.summary'));
                const userId = @json(auth()->user()->id ?? null);
                if (!liveUrl || !userId) return;

                const keyLatestTicket = `live:lastAssignedTicket:${userId}`;
                let lastTicketId = localStorage.getItem(keyLatestTicket);
                lastTicketId = lastTicketId ? parseInt(lastTicketId, 10) : null;

                function setBadge(idBadge, idCount, value, shouldShow) {
                    const badge = document.getElementById(idBadge);
                    const countEl = document.getElementById(idCount);
                    if (!badge || !countEl) return;
                    const n = parseInt(value ?? 0, 10) || 0;
                    countEl.textContent = String(n);
                    const show = (shouldShow !== false) && n > 0;
                    badge.style.display = show ? '' : 'none';
                }

                async function refreshLiveSummary() {
                    try {
                        const res = await fetch(liveUrl, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            cache: 'no-store'
                        });

                        if (!res.ok) return;
                        const data = await res.json();

                        // Update sidebar badges
                        setBadge('sidebar-open-badge', 'sidebar-open-count', data.openTicketsCount, data.showOpenBadge);
                        setBadge('sidebar-unread-badge', 'sidebar-unread-count', data.unreadDiscussionCount, true);

                        // Agent: notify when there is a new/changed assignment (best-effort)
                        if (data.role === 'agent' && data.latestAssignedTicketId) {
                            const currentId = parseInt(data.latestAssignedTicketId, 10);
                            if (currentId && lastTicketId && currentId !== lastTicketId) {
                                if (window.Swal && typeof Swal.fire === 'function') {
                                    Swal.fire({
                                        toast: true,
                                        position: 'top-end',
                                        icon: 'info',
                                        title: 'Ada tiket baru ditugaskan',
                                        showConfirmButton: false,
                                        timer: 3000,
                                        timerProgressBar: true
                                    });
                                }
                            }
                            lastTicketId = currentId;
                            localStorage.setItem(keyLatestTicket, String(currentId));
                        }
                    } catch (e) {
                        // silent
                    }
                }

                // Expose for Echo-triggered refresh (no polling, no reload)
                window.CTMLive = window.CTMLive || {};
                window.CTMLive.refreshLiveSummary = refreshLiveSummary;

                // Initial fetch
                refreshLiveSummary();
            })();
        </script>
    @endauth

    <script>
        (function () {
            const toastEl = document.getElementById('floatingToast');
            if (!toastEl || !window.bootstrap || !bootstrap.Toast) return;
            const toast = new bootstrap.Toast(toastEl);
            toast.show();

            function playSound(key) {
                const id = (key === 'status') ? 'notifSoundStatus' : 'notifSoundNew';
                const audio = document.getElementById(id);
                if (!audio || typeof audio.play !== 'function') return;
                try {
                    const p = audio.play();
                    if (p && typeof p.catch === 'function') p.catch(() => {});
                } catch (e) {}
            }

            const key = @json($fnSound);
            playSound(key);
        })();
    </script>

    @auth
        <script>
            (function () {
                if (!window.bootstrap || !bootstrap.Toast) return;

                const userRole = @json(auth()->user()->role ?? null);
                const userId = @json(auth()->user()->id ?? null);
                const isAdminRole = (userRole === 'admin' || userRole === 'super_admin');

                const pollUrl = "{{ route('notifications.poll') }}";
                const storageKey = 'ct_notif_since_v1';
                const statusMapKey = 'ct_ticket_status_map_v1';
                const newUnassignedKey = 'ct_ticket_new_unassigned_notified_v1';
                const intervalMs = 8000;
                const maxToastsPerPoll = 3;

                function playSound(key) {
                    if (!key) return;
                    const id = (key === 'status') ? 'notifSoundStatus' : 'notifSoundNew';
                    const audio = document.getElementById(id);
                    if (!audio || typeof audio.play !== 'function') return;
                    try {
                        const p = audio.play();
                        if (p && typeof p.catch === 'function') p.catch(() => {});
                    } catch (e) {}
                }

                function loadJson(key, fallback) {
                    try {
                        const raw = localStorage.getItem(key);
                        if (!raw) return fallback;
                        const parsed = JSON.parse(raw);
                        return (parsed && typeof parsed === 'object') ? parsed : fallback;
                    } catch (e) {
                        return fallback;
                    }
                }

                function saveJson(key, value) {
                    try { localStorage.setItem(key, JSON.stringify(value || {})); } catch (e) {}
                }

                function ensureToastContainer() {
                    let c = document.getElementById('floatingToastContainer');
                    if (c) return c;
                    c = document.createElement('div');
                    c.id = 'floatingToastContainer';
                    c.className = 'toast-container position-fixed top-0 end-0 p-3';
                    c.style.zIndex = '2000';
                    document.body.appendChild(c);
                    return c;
                }

                function toastBg(type) {
                    switch ((type || 'info').toLowerCase()) {
                        case 'success': return 'success';
                        case 'danger':
                        case 'error': return 'danger';
                        case 'warning': return 'warning';
                        default: return 'info';
                    }
                }

                function showToast(type, title, message, soundKey, eventKey) {
                    const container = ensureToastContainer();
                    const bg = toastBg(type);
                    const el = document.createElement('div');
                    el.className = 'toast align-items-center text-bg-' + bg + ' border-0';
                    el.setAttribute('role', 'alert');
                    el.setAttribute('aria-live', 'assertive');
                    el.setAttribute('aria-atomic', 'true');
                    el.setAttribute('data-bs-delay', '4500');

                    el.innerHTML =
                        '<div class="d-flex">' +
                            '<div class="toast-body">' +
                                (title ? '<div class="fw-semibold">' + String(title) + '</div>' : '') +
                                (message ? '<div>' + String(message) + '</div>' : '') +
                            '</div>' +
                            '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' +
                        '</div>';

                    container.appendChild(el);
                    const bsToast = new bootstrap.Toast(el);
                    bsToast.show();
                    playSound(soundKey);

                    el.addEventListener('hidden.bs.toast', function () {
                        try { el.remove(); } catch (e) {}
                        // If user manually closed a summary toast, suppress it for this login/session
                        if (eventKey && typeof eventKey === 'string' && userId) {
                            if (eventKey === 'admin_open_summary' || eventKey === 'customer_open_summary' || eventKey === 'agent_open_summary') {
                                const k = 'ct_summary_dismiss_' + eventKey + '_' + userId;
                                try { sessionStorage.setItem(k, '1'); } catch (e) {}
                            }
                        }
                    });
                }

                function getSince() {
                    const v = localStorage.getItem(storageKey);
                    if (v) return v;
                    const nowIso = new Date().toISOString();
                    localStorage.setItem(storageKey, nowIso);
                    return nowIso;
                }

                function setSince(v) {
                    if (!v) return;
                    try { localStorage.setItem(storageKey, v); } catch (e) {}
                }

                async function poll() {
                    const since = getSince();
                    const url = pollUrl + '?since=' + encodeURIComponent(since);

                    const res = await fetch(url, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin'
                    });

                    if (!res.ok) return;
                    const data = await res.json();

                    if (data && data.serverNow) {
                        setSince(data.serverNow);
                    }

                    const items = (data && Array.isArray(data.items)) ? data.items : [];
                    if (items.length === 0) return;

                    const statusMap = loadJson(statusMapKey, {});
                    const newUnassignedNotified = loadJson(newUnassignedKey, {});
                    const displayItems = [];

                    for (const it of items) {
                        let soundKey = null;

                        const ticketId = (it && it.ticket_id != null) ? String(it.ticket_id) : null;
                        const status = (it && it.status != null) ? String(it.status) : null;
                        const event = (it && it.event != null) ? String(it.event) : null;
                        const hasAgent = !(it && (it.agent_id == null || it.agent_id === ''));

                        if (ticketId) {
                            if (event === 'ticket_created') {
                                if (status) statusMap[ticketId] = status;

                                if (isAdminRole && !hasAgent && status === 'open') {
                                    if (!newUnassignedNotified[ticketId]) {
                                        soundKey = 'new';
                                        newUnassignedNotified[ticketId] = data && data.serverNow ? data.serverNow : (new Date().toISOString());
                                    }
                                }
                            } else {
                                if (status) {
                                    const prev = statusMap[ticketId];
                                    if (prev && prev !== status) {
                                        soundKey = 'status';
                                    }
                                    statusMap[ticketId] = status;
                                }
                            }
                        }

                        // Suppress summary toasts if user already closed them this session
                        if (event === 'admin_open_summary' || event === 'customer_open_summary' || event === 'agent_open_summary') {
                            const suppressKey = 'ct_summary_dismiss_' + event + '_' + userId;
                            try {
                                if (sessionStorage.getItem(suppressKey)) {
                                    continue;
                                }
                            } catch (e) {}
                        }

                        displayItems.push({ item: it, soundKey, event });
                    }

                    saveJson(statusMapKey, statusMap);
                    saveJson(newUnassignedKey, newUnassignedNotified);

                    const showItems = displayItems.slice(0, maxToastsPerPoll);
                    for (let i = 0; i < showItems.length; i++) {
                        const { item: it, soundKey, event } = showItems[i];
                        showToast(it.type || 'info', it.title || 'Notifikasi', it.message || '', soundKey, event);
                    }
                    if (displayItems.length > maxToastsPerPoll) {
                        showToast('info', 'Notifikasi', 'Dan ' + (displayItems.length - maxToastsPerPoll) + ' lainnya', null, null);
                    }
                }

                // Start polling after initial load
                setTimeout(function () {
                    poll().catch(() => {});
                    setInterval(function () { poll().catch(() => {}); }, intervalMs);
                }, 1500);
            })();
        </script>
    @endauth
</body>
</html>
