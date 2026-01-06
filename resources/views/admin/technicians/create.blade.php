@extends('layouts.sidebar')

@section('page-title')
    <i class="fa-solid fa-circle-plus me-2"></i>Tambah Teknisi
@endsection

@section('title', 'Tambah Teknisi')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Tambah Teknisi</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('admin.technicians.store') }}">
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
                            <label class="form-label">Level</label>
                            <select name="availability_status" class="form-select">
                                <option value="junior" @selected(old('availability_status')==='junior')>Junior</option>
                                <option value="intermediate" @selected(old('availability_status')==='intermediate')>Intermediate</option>
                                <option value="expert" @selected(old('availability_status')==='expert')>Expert</option>
                            </select>
                        </div>

                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" type="submit">Buat Teknisi</button>
                            <a href="{{ route('admin.technicians.index') }}" class="btn btn-outline-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
