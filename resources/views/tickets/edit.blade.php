@extends('layouts.sidebar')

@section('page-title')
    <i class="fa-solid fa-pen-to-square me-2"></i>Edit Tiket
@endsection

@section('title', 'Edit Tiket')

@section('content')
<div class="container-fluid">
    @if($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Validasi gagal:</div>
            <ul class="mb-0">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('tickets.update', $ticket['id']) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="mb-3">
        <label for="title" class="form-label">Judul</label>
         <input type="text" name="title" id="title"
             class="form-control @error('title') is-invalid @enderror"
             value="{{ old('title', $ticket['title'] ?? '') }}" placeholder="Contoh: Meja rusak saat pemasangan">
        @error('title')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="category" class="form-label">Kategori</label>
        <select name="category_id" id="category"
                class="form-select @error('category_id') is-invalid @enderror">
            @php($cat = old('category_id', $ticket['category_id'] ?? ''))
            <option value="">Pilih Kategori</option>
            @foreach($categories as $c)
                <option value="{{ $c->id }}" @selected((string)$cat === (string)$c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
        @error('category_id')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">Kategori membantu tim mengarahkan ticket ke agent yang tepat.</div>
    </div>

    <div class="mb-3">
        <label for="priority" class="form-label">Prioritas</label>
        <select name="priority" id="priority"
            class="form-select @error('priority') is-invalid @enderror">
            @php($prio = old('priority', $ticket['priority'] ?? ''))
            <option value="">Pilih Prioritas</option>
            <option value="low" @selected($prio==='low')>Low</option>
            <option value="medium" @selected($prio==='medium')>Medium</option>
            <option value="high" @selected($prio==='high')>High</option>
        </select>
        @error('priority')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">Gunakan prioritas tinggi untuk isu yang kritikal.</div>
    </div>

    <div class="mb-3">
        <label for="description" class="form-label">Deskripsi</label>
        <textarea name="description" id="description" rows="6"
              class="form-control @error('description') is-invalid @enderror" placeholder="Jelaskan masalah, langkah yang sudah dicoba, dan harapan perbaikan.">{{ old('description', $ticket['description'] ?? '') }}</textarea>
        @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a class="btn btn-outline-secondary" href="{{ route('tickets.show', $ticket['id']) }}">Batal</a>
    </div>
    </form>
</div>
@endsection
