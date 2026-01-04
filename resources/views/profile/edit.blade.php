@extends('layouts.sidebar')

@section('page-title')
    <i class="fa-solid fa-user-gear me-2"></i>Edit Profile
@endsection
@section('title', 'Edit Profile')

@section('content')
<style>
    .profile-card { border: none; border-radius: 16px; box-shadow: 0 8px 20px rgba(15,23,42,0.08); overflow: hidden; }
    .profile-header { background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%); color: white; padding: 24px; }
    .profile-avatar { width: 80px; height: 80px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 700; border: 4px solid rgba(255,255,255,0.3); }
    .section-divider { border-top: 2px solid #e5e7eb; margin: 24px 0; }
    .info-card { border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; background: #f8fafc; }

    /* Responsive profile */
    @media (max-width: 992px) {
        .col-lg-8, .col-lg-4 {
            width: 100%;
        }
    }

    @media (max-width: 768px) {
        .profile-header {
            padding: 20px;
        }

        .profile-avatar {
            width: 60px;
            height: 60px;
            font-size: 1.5rem;
        }

        .profile-header h4 {
            font-size: 1.1rem;
        }

        .profile-header p {
            font-size: 0.9rem;
        }

        .info-card {
            padding: 12px;
        }
    }

    @media (max-width: 576px) {
        .d-flex.gap-3 {
            flex-direction: column;
            text-align: center;
        }

        .profile-avatar {
            margin: 0 auto;
        }

        h5 {
            font-size: 1rem;
        }

        .btn {
            width: 100%;
            margin-bottom: 8px;
        }

        .form-control, .form-select, textarea {
            font-size: 14px;
        }

        .alert {
            font-size: 13px;
        }
    }
</style>

<div class="container-fluid">
    <div class="row g-4">
        <div class="col-lg-8">
            {{-- Profile Information --}}
            <div class="profile-card mb-4">
                <div class="profile-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="profile-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                        <div>
                            <h4 class="mb-1">{{ auth()->user()->name }}</h4>
                            <p class="mb-0 opacity-90">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                </div>
                <div class="p-4">
                    <h5 class="fw-bold mb-3"><i class="fa-solid fa-user-pen me-2"></i>Informasi Akun</h5>
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            {{-- Update Password --}}
            <div class="profile-card mb-4">
                <div class="p-4">
                    <h5 class="fw-bold mb-3"><i class="fa-solid fa-lock me-2"></i>Ubah Password</h5>
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            {{-- Delete Account --}}
            <div class="profile-card" style="border: 2px solid #fee2e2;">
                <div class="p-4">
                    <h5 class="fw-bold mb-3 text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i>Zona Berbahaya</h5>
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            {{-- User Info Card --}}
            <div class="profile-card mb-4">
                <div class="p-4">
                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-circle-user me-2"></i>Info Pengguna</h6>
                    <div class="info-card mb-2">
                        <small class="text-muted d-block mb-1">Role</small>
                        @php($role = auth()->user()->role ?? 'customer')
                        @if($role === 'admin')
                            <span class="badge bg-danger">Admin</span>
                        @elseif($role === 'agent')
                            <span class="badge bg-warning text-dark">Agent</span>
                        @else
                            <span class="badge bg-success">Customer</span>
                        @endif
                    </div>
                    <div class="info-card">
                        <small class="text-muted d-block mb-1">Bergabung sejak</small>
                        <div class="fw-semibold">{{ auth()->user()->created_at?->format('d M Y') ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>

            {{-- Tips Card --}}
            <div class="profile-card">
                <div class="p-4">
                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-shield-halved me-2"></i>Petunjuk Keamanan</h6>
                    <ul class="small text-muted mb-0" style="line-height: 1.8;">
                        <li>Gunakan password minimal 8 karakter</li>
                        <li>Kombinasikan huruf, angka & simbol</li>
                        <li>Jangan bagikan password Anda</li>
                        <li>Perbarui password secara berkala</li>
                        <li>Menghapus akun bersifat permanen</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
