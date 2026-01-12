@extends('layouts.sidebar')

@section('page-title')
    <i class="fa-solid fa-user-pen me-2"></i>Edit Teknisi
@endsection

@section('scripts')
<script>
(function(){
    const form = document.getElementById('editTechnicianForm');
    if (!form) return;

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
            if (result.isConfirmed) {
                dirty = false;
                window.location.href = a.href;
            }
        });
    });
})();
</script>
@endsection

@section('title', 'Edit Teknisi')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Ubah Teknisi</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('admin.technicians.update', $user) }}" id="editTechnicianForm">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Nama</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Kata Sandi (kosongkan jika tidak ingin mengganti)</label>
                            <input type="password" name="password" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Kata Sandi</label>
                            <input type="password" name="password_confirmation" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Level</label>
                            <select name="availability_status" class="form-select">
                                <option value="junior" @selected(old('availability_status', $user->availability_status)==='junior')>Junior</option>
                                <option value="intermediate" @selected(old('availability_status', $user->availability_status)==='intermediate')>Intermediate</option>
                                <option value="expert" @selected(old('availability_status', $user->availability_status)==='expert')>Expert</option>
                            </select>
                        </div>

                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" type="submit">Simpan</button>
                            <a href="{{ route('admin.technicians.index') }}" class="btn btn-outline-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
