@extends('layouts.sidebar')

@section('page-title')
    <i class="fa-solid fa-circle-plus me-2"></i>Tambah Kategori
@endsection

@section('title', 'Tambah Kategori')

@section('content')
<style>
    @media (max-width: 576px) {
        .category-form-actions {
            flex-direction: column;
        }

        .category-form-actions .btn {
            width: 100%;
        }
    }
</style>
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Tambah Kategori</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.categories.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Nama</label>
                            <input name="name" class="form-control" value="{{ old('name') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" class="form-control">{{ old('description') }}</textarea>
                        </div>
                        <div class="d-flex gap-2 category-form-actions">
                            <button class="btn btn-primary">Simpan</button>
                            <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
