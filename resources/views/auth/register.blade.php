@extends('layouts.bootstrap')

@section('no-navbar', '1')
@section('no-footer', '1')

@section('content')
<style>
    .auth-wrapper { padding: 48px 0; }
    .auth-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; box-shadow: 0 8px 24px rgba(15,23,42,.06); }
    .auth-title { font-weight: 800; letter-spacing: .2px; }
    .auth-muted { color: #6b7280; }
</style>

<div class="auth-wrapper">
    <div class="row justify-content-center">
        <div class="col-sm-10 col-md-8 col-lg-5">
            <div class="text-center mb-3">
                <small class="text-muted">CTM</small>
                <h2 class="auth-title mt-1 mb-1">Buat Akun Baru</h2>
                <div class="auth-muted">Akses dashboard dan mulai kelola tiket.</div>
            </div>

            @if(session('registered'))
            <div class="alert alert-success d-flex justify-content-between align-items-center" role="alert">
                <div class="mb-0">Registrasi berhasil. Silakan login.</div>
                <div>
                    <a href="{{ route('login') }}" class="btn btn-sm btn-success">OK</a>
                </div>
            </div>
            @endif

            <div class="auth-card p-4">
                <form method="POST" action="{{ route('register') }}" class="d-grid gap-3">
                    @csrf

                    <div>
                        <label for="name" class="form-label">Nama</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" class="form-control @error('name') is-invalid @enderror">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label for="email" class="form-label">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" class="form-control @error('email') is-invalid @enderror">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label for="password" class="form-label">Password</label>
                        <input id="password" type="password" name="password" required autocomplete="new-password" class="form-control @error('password') is-invalid @enderror">
                        <div class="form-text">Password minimal 8 karakter.</div>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="form-control @error('password_confirmation') is-invalid @enderror">
                        @error('password_confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <button class="btn btn-primary w-100" type="submit">Daftar</button>

                    <div class="text-center text-muted">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
