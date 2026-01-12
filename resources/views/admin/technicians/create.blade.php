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
                    <form method="POST" action="{{ route('admin.technicians.store') }}" id="createTechnicianForm">
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

@section('scripts')
<script>
(function(){
    const form = document.getElementById('createTechnicianForm');
    if (!form) return;

    const listUrl = @json(route('admin.technicians.index'));

    // Show errors as alert
    @if(session('error'))
    Swal.fire({
        title: 'Gagal',
        text: '{{ addslashes(session('error')) }}',
        icon: 'error',
        confirmButtonText: 'OK'
    });
    @elseif($errors->any())
    Swal.fire({
        title: 'Gagal',
        text: '{{ addslashes($errors->first()) }}',
        icon: 'error',
        confirmButtonText: 'OK'
    });
    @endif

    // Unsaved changes guard
    let dirty = false;
    const markDirty = () => { dirty = true; };
    form.querySelectorAll('input,select,textarea').forEach(el => {
        el.addEventListener('input', markDirty);
        el.addEventListener('change', markDirty);
    });
    form.addEventListener('submit', () => { dirty = false; });

    window.addEventListener('beforeunload', function (e) {
        if (!dirty) return;
        e.preventDefault();
        e.returnValue = '';
    });

    document.addEventListener('click', function(e){
        const a = e.target.closest('a[href]');
        if (!a || !dirty) return;
        // allow opening in new tab etc
        if (a.target === '_blank' || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        e.preventDefault();
        Swal.fire({
            title: 'Apakah ingin merubah atau menambah data?',
            text: 'Anda belum menyimpan perubahan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya',
            cancelButtonText: 'Tidak',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) return; // stay
            // Tidak: discard/reset then go back to list
            form.reset();
            form.querySelectorAll('input[type="text"],input[type="email"],input[type="password"],textarea').forEach(el => { el.value = ''; });
            dirty = false;
            window.location.href = listUrl;
        });
    });
})();
</script>
@endsection
