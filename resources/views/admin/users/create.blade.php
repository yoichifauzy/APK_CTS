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

@section('scripts')
<script>
(function(){
    const form = document.getElementById('createUserForm');
    if (!form) return;

    const listUrl = @json(route('admin.users.index', isset($role) ? ['role' => $role] : []));

    let dirty = false;
    const markDirty = () => { dirty = true; };
    form.querySelectorAll('input,select,textarea').forEach(el => {
        el.addEventListener('input', markDirty);
        el.addEventListener('change', markDirty);
    });
    form.addEventListener('submit', () => { dirty = false; });

    function getRoleLabel(){
        const role = '{{ $role ?? '' }}'.toLowerCase();
        if (role === 'admin') return 'Admin';
        if (role === 'agent') return 'Teknisi';
        return 'User';
    }

    function resetForm(){
        form.reset();
        // ensure all text-like inputs cleared (reset() may keep old() values)
        form.querySelectorAll('input[type="text"],input[type="email"],input[type="password"],textarea').forEach(el => {
            el.value = '';
        });
        // keep hidden role if present
        const hiddenRole = form.querySelector('input[type="hidden"][name="role"]');
        if (hiddenRole && hiddenRole.value) {
            // do nothing
        }
        dirty = false;
    }

    document.addEventListener('click', function(e){
        const a = e.target.closest('a[href]');
        if (!a || !dirty) return;
        if (a.dataset && a.dataset.bypassUnsaved === '1') return;
        if (a.target === '_blank' || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        e.preventDefault();
        Swal.fire({
            title: `Apakah Akan Melanjutkan Menambahkan ${getRoleLabel()}?`,
            text: 'Pilih Ya untuk melanjutkan mengisi. Pilih Tidak untuk membatalkan dan mereset form.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya',
            cancelButtonText: 'Tidak',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) return; // stay on page
            // Tidak: discard/reset and go back to list (Kelola role yang sedang dibuat)
            resetForm();
            window.location.href = listUrl;
        });
    });
})();
</script>
@endsection

@section('title', $pageTitleText)

@section('content')
<style>
    @media (max-width: 576px) {
        .user-form-actions {
            flex-direction: column;
        }

        .user-form-actions .btn {
            width: 100%;
        }
    }
</style>
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ $cardTitle }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('admin.users.store') }}" id="createUserForm">
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

                        @php($effectiveRole = $role ?? old('role'))
                        @if(in_array($effectiveRole, ['admin','agent'], true))
                            <div class="mb-3">
                                <label class="form-label">Kategori / Jobdesk</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    @foreach(($categories ?? []) as $c)
                                        <option value="{{ $c->id }}" @selected(old('category_id') == $c->id)>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        {{-- Status agent ditentukan otomatis berdasarkan tiket aktif (assigned/in_progress/resolved). --}}

                        <div class="d-flex gap-2 user-form-actions">
                            <button class="btn btn-primary" type="submit">Buat Pengguna</button>
                            <a href="{{ route('admin.users.index', isset($role) ? ['role' => $role] : []) }}" class="btn btn-outline-secondary" data-bypass-unsaved="1">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
