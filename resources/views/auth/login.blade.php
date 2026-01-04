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
                <small class="text-muted">Cloud Ticketing</small>
                <h2 class="auth-title mt-1 mb-1">Masuk ke Dashboard</h2>
                <div class="auth-muted">Kelola tiket dan pantau progres dengan cepat.</div>
            </div>

            @if(session('status'))
                <div class="alert alert-info">{{ session('status') }}</div>
            @endif

            @if($errors->has('auth'))
                <div class="alert alert-danger">{{ $errors->first('auth') }}</div>
            @endif

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
@endsection
