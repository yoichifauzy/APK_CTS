@extends('layouts.sidebar')

@php
    $pageTitleText = 'Tambah Pengguna';
    $pageTitleHtml = '<i class="fa-solid fa-circle-plus me-2"></i>Tambah Pengguna';
    $cardTitle = 'Tambah Pengguna Baru';

    if (isset($role)) {
        if ($role === 'admin') {
            $pageTitleText = 'Tambah Admin';
            $pageTitleHtml = '<i class="fa-solid fa-circle-plus me-2"></i>Tambah Admin';
            $cardTitle = 'Tambah Admin Baru';
        } elseif ($role === 'agent') {
            $pageTitleText = 'Tambah Operator';
            $pageTitleHtml = '<i class="fa-solid fa-circle-plus me-2"></i>Tambah Operator';
            $cardTitle = 'Tambah Operator Baru';
        } elseif ($role === 'customer') {
            $pageTitleText = 'Tambah User';
            $pageTitleHtml = '<i class="fa-solid fa-circle-plus me-2"></i>Tambah User';
            $cardTitle = 'Tambah User Baru';
        }
    }
@endphp

@section('page-title')
    {!! $pageTitleHtml !!}
@endsection

@section('title', $pageTitleText)

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ $cardTitle }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('admin.users.store') }}">
                        @if(isset($role))
                            <input type="hidden" name="role" value="{{ $role }}">
                        @endif
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Nama</label>
                            <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Kata Sandi</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Kata Sandi</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Peran</label>
                            <select name="role" class="form-select" {{ isset($role) ? 'readonly disabled' : 'required' }}>
                                <option value="customer" {{ (isset($role) && $role === 'customer') || old('role') === 'customer' ? 'selected' : '' }}>Customer</option>
                                <option value="agent" {{ (isset($role) && $role === 'agent') || old('role') === 'agent' ? 'selected' : '' }}>Agent (Operator)</option>
                                <option value="admin" {{ (isset($role) && $role === 'admin') || old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                            </select>
                            @if(isset($role))
                                <input type="hidden" name="role" value="{{ $role }}">
                            @endif
                        </div>

                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" type="submit">Buat Pengguna</button>
                            <a href="{{ route('admin.users.index', isset($role) ? ['role' => $role] : []) }}" class="btn btn-outline-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
