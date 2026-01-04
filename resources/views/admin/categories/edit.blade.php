@extends('layouts.sidebar')

@section('page-title')
    <i class="fa-solid fa-pen-to-square me-2"></i>Edit Kategori
@endsection

@section('title', 'Edit Kategori')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Edit Kategori</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.categories.update', $category) }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">Nama</label>
                            <input name="name" class="form-control" value="{{ old('name', $category->name) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" class="form-control">{{ old('description', $category->description) }}</textarea>
                        </div>
                        <div class="d-flex gap-2">
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
