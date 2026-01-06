<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Cloud Ticketing'))</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .navbar-modern {
            background: linear-gradient(135deg, #0ea5e9 0%, #6366f1 100%);
        }
        .navbar-modern .nav-link.active {
            font-weight: 600;
        }
        footer.site-footer {
            background: #0f172a;
            color: rgba(255,255,255,0.85);
            padding: 40px 0;
            margin-top: 40px;
        }
        footer.site-footer a { color: #93c5fd; text-decoration: none; }
        footer.site-footer a:hover { color: #bfdbfe; }
    </style>
</head>
<body>
    @php
        $noNavbar = trim($__env->yieldContent('no-navbar')) !== '';
        $noFooter = trim($__env->yieldContent('no-footer')) !== '';
        $fullwidth = trim($__env->yieldContent('fullwidth')) !== '';
    @endphp

    @if(!$noNavbar)
        <nav class="navbar navbar-expand-lg navbar-dark navbar-modern shadow-sm">
            <div class="container">
                <a class="navbar-brand fw-bold" href="{{ route('home') }}"><i class="fa-solid fa-cloud me-2"></i>CloudTicket</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('tickets.*') ? 'active' : '' }}" href="{{ route('tickets.index') }}">Tiket</a></li>
                        @auth
                            @if(auth()->user()->role === 'admin')
                            <li class="nav-item"><a class="nav-link" href="{{ route('admin.users.index') }}">Pengguna</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('admin.categories.index') }}">Kategori</a></li>
                            @endif
                        @endauth
                    </ul>

                    <ul class="navbar-nav ms-auto">
                        @auth
                            <li class="nav-item">
                                <span class="navbar-text me-3">{{ auth()->user()->name }}
                                    @php
                                        $role = auth()->user()->role;
                                    @endphp
                                    @if($role === 'admin')
                                        <span class="badge text-bg-danger ms-2">admin</span>
                                    @elseif($role === 'agent')
                                        <span class="badge text-bg-warning ms-2">agent</span>
                                    @else
                                        <span class="badge text-bg-success ms-2">customer</span>
                                    @endif
                                </span>
                            </li>
                            <li class="nav-item">
                                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-light btn-sm" type="submit">Keluar</button>
                                </form>
                            </li>
                        @else
                            <li class="nav-item"><a class="btn btn-light btn-sm" href="{{ route('login') }}">Masuk</a></li>
                        @endauth
                    </ul>
                </div>
            </div>
        </nav>
    @endif

    <main class="{{ $fullwidth ? 'p-0' : 'container py-4' }}">
        @if(!$fullwidth)
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
        @endif

        @yield('content')
    </main>

    @if(!$noFooter)
        <footer class="site-footer">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="fw-semibold"><i class="fa-solid fa-cloud me-2"></i>CloudTicket</div>
                        <div class="text-white-50">Modern Customer Support Platform</div>
                    </div>
                    <div class="col-md-6 text-md-end mt-3 mt-md-0">
                        <a href="{{ route('home') }}" class="me-3">Beranda</a>
                        <a href="{{ route('login') }}" class="me-3">Masuk</a>
                        <a href="{{ route('register') }}">Daftar</a>
                        <div class="text-white-50 mt-2">&copy; {{ date('Y') }} CloudTicket. All rights reserved.</div>
                    </div>
                </div>
            </div>
        </footer>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
