@extends('layouts.bootstrap')

@section('no-navbar', '1')
@section('no-footer', '1')

@section('content')
<style>
    .auth-wrapper { padding: 48px 0; }
    .auth-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; box-shadow: 0 8px 24px rgba(15,23,42,.06); }
    .auth-title { font-weight: 800; letter-spacing: .2px; }
    .auth-muted { color: #6b7280; }
    
    /* Alert Modal Styles - Similar to delete confirmation */
    .alert-modal .modal-content {
        border: none;
        border-radius: 20px;
        padding: 40px 30px 30px;
        text-align: center;
    }
    .alert-modal .modal-body {
        padding: 0;
    }
    .alert-icon-circle {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        border: 4px solid;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 2.5rem;
    }
    .alert-modal.error .alert-icon-circle {
        border-color: #f97316;
        color: #f97316;
    }
    .alert-modal.info .alert-icon-circle {
        border-color: #0ea5e9;
        color: #0ea5e9;
    }
    .alert-modal h5 {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 8px;
        color: #1f2937;
    }
    .alert-modal .alert-subicon {
        font-size: 1.8rem;
        margin: 10px 0;
    }
    .alert-modal.error .alert-subicon {
        color: #dc3545;
    }
    .alert-modal p {
        color: #6b7280;
        margin-bottom: 30px;
        font-size: 0.95rem;
    }
    .alert-modal .modal-footer {
        border: none;
        padding: 0;
        justify-content: center;
        gap: 12px;
    }
    .alert-modal .btn {
        padding: 12px 32px;
        font-weight: 500;
        border-radius: 8px;
        min-width: 120px;
    }
</style>

<div class="auth-wrapper">
    <div class="row justify-content-center">
        <div class="col-sm-10 col-md-8 col-lg-5">
            <div class="text-center mb-3">
                <small class="text-muted">Cloud Ticketing</small>
                <h2 class="auth-title mt-1 mb-1">Masuk ke Dashboard</h2>
                <div class="auth-muted">Kelola tiket dan pantau progres dengan cepat.</div>
            </div>

            <div class="auth-card p-4">
                <form method="POST" action="{{ route('login') }}" class="d-grid gap-3">
                    @csrf

                    <div>
                        <label for="email" class="form-label">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="form-control @error('email') is-invalid @enderror">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label for="password" class="form-label">Password</label>
                        <input id="password" type="password" name="password" required autocomplete="current-password" class="form-control @error('password') is-invalid @enderror">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <button class="btn btn-primary w-100" type="submit">Masuk</button>

                    <div class="text-center text-muted">Belum punya akun? <a href="{{ route('register') }}">Daftar</a></div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Modal untuk Error/Success Alert --}}
@if(session('status') || $errors->has('auth') || $errors->has('email') || $errors->has('password'))
<div class="modal fade alert-modal {{ $errors->any() ? 'error' : 'info' }}" id="alertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                @if($errors->has('auth'))
                    <div class="alert-icon-circle">
                        <i class="fa-solid fa-exclamation"></i>
                    </div>
                    <h5>Login Gagal</h5>
                    <div class="alert-subicon">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </div>
                    <p>{{ $errors->first('auth') }}</p>
                @elseif($errors->has('email'))
                    <div class="alert-icon-circle">
                        <i class="fa-solid fa-exclamation"></i>
                    </div>
                    <h5>Email Tidak Valid</h5>
                    <div class="alert-subicon">
                        <i class="fa-solid fa-envelope-circle-check"></i>
                    </div>
                    <p>{{ $errors->first('email') }}</p>
                @elseif($errors->has('password'))
                    <div class="alert-icon-circle">
                        <i class="fa-solid fa-exclamation"></i>
                    </div>
                    <h5>Password Error</h5>
                    <div class="alert-subicon">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                    <p>{{ $errors->first('password') }}</p>
                @elseif(session('status'))
                    <div class="alert-icon-circle">
                        <i class="fa-solid fa-info"></i>
                    </div>
                    <h5>Informasi</h5>
                    <p>{{ session('status') }}</p>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">OK, Mengerti!</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Auto show modal when page loads with errors
    document.addEventListener('DOMContentLoaded', function() {
        var alertModal = document.getElementById('alertModal');
        if (alertModal) {
            var modal = new bootstrap.Modal(alertModal);
            modal.show();
        }
    });
</script>
@endif

@endsection
