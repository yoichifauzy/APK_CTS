@extends('layouts.bootstrap')

@section('title', 'CTM (Cloud Ticketing Manufacturing)')

@section('no-navbar', '1')
@section('no-footer', '1')
@section('fullwidth', '1')

@section('content')
<style>
    /* Landing only */
    body { overflow-x: hidden; }

    .hero { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 100px 20px; position: relative; overflow: hidden; min-height: 600px; display: flex; align-items: center; }
    .hero::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,138.7C960,139,1056,117,1152,106.7C1248,96,1344,96,1392,96L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom; background-size: cover; }
    .feature-card { border: none; border-radius: 20px; padding: 30px; height: 100%; background: white; box-shadow: 0 10px 30px rgba(0,0,0,0.08); transition: transform 0.3s, box-shadow 0.3s; }
    .feature-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0,0,0,0.12); }
    .feature-icon { width: 70px; height: 70px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 20px; }
    .stat-box { text-align: center; padding: 30px; border-radius: 16px; background: white; box-shadow: 0 8px 20px rgba(0,0,0,0.06); }
    .cta-section { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 80px 20px; border-radius: 30px; margin: 60px 0; }
    .btn-modern { border-radius: 12px; padding: 14px 32px; font-weight: 600; transition: all 0.3s; border: none; }
    .btn-modern:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
    .process-step { text-align: center; padding: 20px; }
    .process-number { width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, #667eea, #764ba2); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700; margin: 0 auto 20px; }

    .ctm-logo {
        width: 220px;
        height: 220px;
        border-radius: 28px;
        background: rgba(255,255,255,0.14);
        border: 2px solid rgba(255,255,255,0.18);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        backdrop-filter: blur(6px);
    }
    .ctm-logo .mark {
        width: 96px;
        height: 96px;
        border-radius: 24px;
        background: rgba(255,255,255,0.22);
        border: 2px solid rgba(255,255,255,0.25);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
        letter-spacing: 1px;
        font-size: 34px;
        line-height: 1;
    }
    .ctm-logo .name { margin-top: 12px; font-weight: 800; letter-spacing: .3px; font-size: 20px; }
    .ctm-logo .tag { opacity: .9; font-size: 12px; }

    /* Responsive hero */
    @media (max-width: 992px) {
        .hero { padding: 80px 20px; min-height: 500px; }
        .hero h1 { font-size: 2.5rem !important; }
        .hero p { font-size: 1rem !important; }
    }

    @media (max-width: 768px) {
        .hero { padding: 60px 15px; min-height: auto; text-align: center; }
        .hero h1 { font-size: 2rem !important; text-align: center; }
        .hero p { font-size: 0.95rem !important; text-align: center; }
        .hero .row { justify-content: center; }
        .hero .col-lg-6:first-child { order: 1; }
        .hero .col-lg-6:last-child { order: 2; margin-top: 20px; }
        .d-flex.gap-3 { justify-content: center; }
        .feature-card { margin-bottom: 20px; }
        .stat-box { margin-bottom: 20px; }
        .cta-section { padding: 50px 20px; margin: 40px 0; }
    }

    @media (max-width: 576px) {
        .hero { padding: 40px 10px; }
        .hero h1 { font-size: 1.75rem !important; }
        .hero .col-lg-6:last-child div { font-size: 8rem !important; }
        .btn-modern { padding: 12px 24px; font-size: 14px; width: 100%; }
        .d-flex.gap-3 { flex-direction: column; gap: 10px !important; }
    }
</style>

{{-- Hero Section --}}
<div class="container-fluid px-0 hero">
    <div class="container" style="position: relative; z-index: 1;">
        <div class="row align-items-center justify-content-center">
            <div class="col-lg-6 col-md-8 mb-5 mb-lg-0" style="padding-left: 40px;">
                <h1 style="font-size: 3.2rem; font-weight: 800; margin-bottom: 20px; line-height: 1.2;">
                    CTM<br><span style="color: #fbbf24;">Cloud Ticketing Manufacturing</span>
                </h1>
                <p style="font-size: 1.15rem; margin-bottom: 28px; opacity: 0.95; line-height: 1.8;">
                    Platform ticketing untuk kebutuhan manufacturing: cepat, terstruktur, dan mudah dipantau.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    @guest
                        <a href="{{ route('register') }}" class="btn btn-light btn-lg btn-modern">
                            <i class="fa-solid fa-rocket me-2"></i>Mulai Registrasi
                        </a>
                        <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg btn-modern">
                            <i class="fa-solid fa-right-to-bracket me-2"></i>Masuk
                        </a>
                    @else
                        <a href="{{ route('tickets.index') }}" class="btn btn-light btn-lg btn-modern">
                            <i class="fa-solid fa-chart-line me-2"></i>Dashboard
                        </a>
                    @endguest
                </div>
            </div>
            <div class="col-lg-6">
                <div class="text-center">
                    <div class="ctm-logo">
                        <div class="mark">CTM</div>
                        <div class="name">CTM</div>
                        <div class="tag">Cloud Ticketing Manufacturing</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Features Section --}}
<div class="container my-5 py-5">
    <div class="text-center mb-5">
        <h2 style="font-size: 2.3rem; font-weight: 700; margin-bottom: 12px;"><i class="fa-solid fa-star me-2"></i>Fitur Unggulan</h2>
        <p class="text-muted" style="font-size: 1.05rem;">Semua yang Anda butuhkan untuk customer support yang efektif</p>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="feature-card">
                <div class="feature-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);"><i class="fa-solid fa-bolt"></i></div>
                <h4 style="font-weight: 700; margin-bottom: 12px;">Real-Time Sync</h4>
                <p class="text-muted">Semua update tersimpan otomatis di cloud. Akses dari mana saja, kapan saja.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card">
                <div class="feature-icon" style="background: linear-gradient(135deg, #f093fb, #f5576c);"><i class="fa-solid fa-users"></i></div>
                <h4 style="font-weight: 700; margin-bottom: 12px;">Multi-Role</h4>
                <p class="text-muted">Admin, Agent, dan Customer - masing-masing punya akses sesuai kebutuhan.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card">
                <div class="feature-icon" style="background: linear-gradient(135deg, #fbbf24, #f59e0b);"><i class="fa-solid fa-chart-line"></i></div>
                <h4 style="font-weight: 700; margin-bottom: 12px;">Analytics</h4>
                <p class="text-muted">Dashboard lengkap dengan statistik dan insight untuk keputusan yang lebih baik.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card">
                <div class="feature-icon" style="background: linear-gradient(135deg, #34d399, #10b981);"><i class="fa-solid fa-comments"></i></div>
                <h4 style="font-weight: 700; margin-bottom: 12px;">Comment System</h4>
                <p class="text-muted">Komunikasi dua arah yang jelas antara customer dan support team.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card">
                <div class="feature-icon" style="background: linear-gradient(135deg, #60a5fa, #3b82f6);"><i class="fa-solid fa-paperclip"></i></div>
                <h4 style="font-weight: 700; margin-bottom: 12px;">File Attachments</h4>
                <p class="text-muted">Upload screenshot, dokumen, atau file apapun untuk memperjelas masalah.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card">
                <div class="feature-icon" style="background: linear-gradient(135deg, #a78bfa, #8b5cf6);"><i class="fa-solid fa-bell"></i></div>
                <h4 style="font-weight: 700; margin-bottom: 12px;">Status Tracking</h4>
                <p class="text-muted">Pantau progres tiket dari Open sampai Closed dengan timeline yang jelas.</p>
            </div>
        </div>
    </div>
</div>

{{-- Process Section --}}
<div class="container my-5 py-5">
    <div class="text-center mb-5">
        <h2 style="font-size: 2.3rem; font-weight: 700; margin-bottom: 12px;"><i class="fa-solid fa-rocket me-2"></i>Cara Kerja</h2>
        <p class="text-muted" style="font-size: 1.05rem;">Hanya 4 langkah sederhana</p>
    </div>

    <div class="row g-4">
        <div class="col-md-3 col-6">
            <div class="process-step">
                <div class="process-number">1</div>
                <h5 style="font-weight: 700; margin-bottom: 12px;">Buat Tiket</h5>
                <p class="text-muted small">Customer membuat tiket dengan deskripsi masalah</p>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="process-step">
                <div class="process-number">2</div>
                <h5 style="font-weight: 700; margin-bottom: 12px;">Assign Agent</h5>
                <p class="text-muted small">Admin menugaskan tiket ke agent yang sesuai</p>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="process-step">
                <div class="process-number">3</div>
                <h5 style="font-weight: 700; margin-bottom: 12px;">Proses</h5>
                <p class="text-muted small">Agent menangani dan update progress tiket</p>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="process-step">
                <div class="process-number">4</div>
                <h5 style="font-weight: 700; margin-bottom: 12px;">Selesai</h5>
                <p class="text-muted small">Tiket ditutup setelah masalah terselesaikan</p>
            </div>
        </div>
    </div>
</div>

{{-- Stats Section --}}
<div class="container-fluid px-0" style="background: #f8fafc; padding: 60px 20px; margin: 60px 0;">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-3 col-6">
                <div class="stat-box">
                    <h2 style="color: #667eea; font-weight: 700; font-size: 3rem; margin-bottom: 8px;">100%</h2>
                    <p class="text-muted mb-0">Cloud-Based</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-box">
                    <h2 style="color: #f093fb; font-weight: 700; font-size: 3rem; margin-bottom: 8px;">24/7</h2>
                    <p class="text-muted mb-0">Availability</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-box">
                    <h2 style="color: #10b981; font-weight: 700; font-size: 3rem; margin-bottom: 8px;">∞</h2>
                    <p class="text-muted mb-0">Scalable</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-box">
                    <h2 style="color: #f59e0b; font-weight: 700; font-size: 3rem; margin-bottom: 8px;">Fast</h2>
                    <p class="text-muted mb-0">Response Time</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- CTA Section --}}
<div class="container">
    <div class="cta-section text-center">
        <h2 style="font-size: 2.3rem; font-weight: 800; margin-bottom: 16px;">Siap Meningkatkan Layanan Anda?</h2>
        <p style="font-size: 1.1rem; margin-bottom: 32px; opacity: 0.95;">
            Mulai kelola tiket customer support Anda dengan lebih efektif dan efisien menggunakan platform cloud kami
        </p>
        <div class="d-flex justify-content-center flex-wrap gap-3">
            @guest
                <a href="{{ route('register') }}" class="btn btn-light btn-lg btn-modern" style="background: white; color: #f5576c;">
                    <i class="fa-solid fa-rocket me-2"></i>Daftar Sekarang
                </a>
                <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg btn-modern">
                    <i class="fa-solid fa-right-to-bracket me-2"></i>Masuk
                </a>
            @else
                <a href="{{ route('tickets.create') }}" class="btn btn-light btn-lg btn-modern" style="background: white; color: #f5576c;">
                    <i class="fa-solid fa-circle-plus me-2"></i>Buat Tiket Baru
                </a>
                <a href="{{ route('tickets.index') }}" class="btn btn-outline-light btn-lg btn-modern">
                    <i class="fa-solid fa-ticket me-2"></i>Lihat Tiket
                </a>
            @endguest
        </div>
    </div>
</div>

@endsection
