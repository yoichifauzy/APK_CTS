@extends('layouts.sidebar')

@php
    $role = request('role');
    $isSuperAdmin = (auth()->user()->role ?? null) === 'super_admin';
    $readOnly = false; // Super Admin can manage users
    $allowCreate = true;
    $pageTitleText = 'Manajemen Pengguna';
    $pageTitleHtml = '<i class="fa-solid fa-users me-2"></i>Manajemen Pengguna';
    $btnText = 'Tambah Pengguna';

    if ($role === 'admin') {
        $pageTitleText = 'Manajemen Admin';
        $pageTitleHtml = '<i class="fa-solid fa-user-shield me-2"></i>Manajemen Admin';
        $btnText = 'Tambah Admin';
    } elseif ($role === 'agent') {
        $pageTitleText = 'Daftar Agent';
        $pageTitleHtml = '<i class="fa-solid fa-screwdriver-wrench me-2"></i>Daftar Agent';
        $btnText = 'Tambah Agent';
    } elseif ($role === 'customer') {
        $pageTitleText = 'Daftar Customer';
        $pageTitleHtml = '<i class="fa-solid fa-users me-2"></i>Daftar Customer';
        $btnText = 'Tambah Customer';
    }
@endphp

@section('page-title')
    {!! $pageTitleHtml !!}
@endsection

@section('title', $pageTitleText)

@section('content')
<style>
    /* Responsive admin tables */
    @media (max-width: 992px) {
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table {
            min-width: 600px;
        }
    }

    @media (max-width: 768px) {
        table {
            font-size: 13px;
        }

        .d-flex.justify-content-between {
            flex-direction: column;
            gap: 10px;
        }

        .btn-primary {
            width: 100%;
        }

        h1.h3 {
            font-size: 1.25rem;
        }
    }

    @media (max-width: 576px) {
        .btn-sm {
            padding: 4px 8px;
            font-size: 11px;
        }

        table th, table td {
            padding: 8px 6px;
        }

        .btn {
            width: 100%;
            margin-bottom: 5px;
        }
    }
</style>
<div class="container-fluid">
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">{!! $pageTitleHtml !!}</h1>
    <div class="d-flex gap-2 align-items-center" style="min-width: 320px;">
        <form method="GET" action="{{ route('admin.users.index') }}" class="d-flex gap-2 align-items-center">
            <input type="hidden" name="role" value="{{ request('role') }}" />
            <select name="category_id" class="form-select form-select-sm" style="min-width: 200px;" onchange="this.form.submit()">
                <option value="">Semua Jobdesk</option>
                @foreach(($categories ?? collect()) as $cat)
                    <option value="{{ $cat->id }}" @selected((string)request('category_id') === (string)$cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>
        </form>
        <div class="input-group" style="max-width: 360px;">
            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input id="user-search" type="text" class="form-control" placeholder="Cari nama / email..." autocomplete="off">
            <button class="btn btn-outline-secondary" type="button" id="user-search-clear" title="Clear">Clear</button>
        </div>
        <div class="btn-group" role="group">
            <a href="{{ route('admin.users.exportPdf', ['role' => request('role'), 'category_id' => request('category_id')]) }}" class="btn btn-sm btn-warning text-dark" title="Download" download>
                <i class="fa-solid fa-download me-1"></i>Download
            </a>
            <a href="{{ route('admin.users.exportCsv', ['role' => request('role'), 'category_id' => request('category_id')]) }}" class="btn btn-sm btn-success" title="Export CSV">
                <i class="fa-solid fa-file-csv me-1"></i>CSV
            </a>
            <button type="button" class="btn btn-sm btn-danger" id="btn-users-print" title="Cetak/PDF">
                <i class="fa-solid fa-print me-1"></i>PDF/Cetak
            </button>
        </div>
        @if($allowCreate)
            <a href="{{ route('admin.users.create', ['role' => $role]) }}" class="btn btn-primary">{{ $btnText }}</a>
        @endif
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Email</th>
                    @if($role === 'admin')
                        <th>Jobdesk</th>
                    @elseif($role === 'agent')
                        <th>Jobdesk</th>
                        <th>Level</th>
                    @endif
                    <th>Peran</th>
                    @if(!$readOnly)
                        <th class="text-end">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($users as $u)
                    <tr>
                            <td>
                                @php
                                    $no = 0;
                                @endphp
                                {{-- Pagination-aware numbering --}}
                                @if(is_object($users) && method_exists($users, 'currentPage'))
                                    {{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}
                                @else
                                    {{ $loop->iteration }}
                                @endif
                            </td>
                            <td>{{ $u->name }}</td>
                            <td>{{ $u->email }}</td>
                            @if($role === 'admin')
                                <td>{{ $u->category->name ?? '-' }}</td>
                            @elseif($role === 'agent')
                                <td>{{ $u->category->name ?? '-' }}</td>
                                <td>{{ $u->availability_status ?? '-' }}</td>
                            @endif
                            <td><span class="badge text-bg-secondary">{{ $u->role }}</span></td>
                            @if(!$readOnly)
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('admin.users.show', $u) }}" class="btn btn-sm btn-outline-secondary">Lihat</a>
                                        <a href="{{ route('admin.users.edit', $u) }}" class="btn btn-sm btn-outline-primary">Ubah</a>
                                        <button class="btn btn-sm btn-danger" onclick="confirmDelete('{{ route('admin.users.destroy', $u) }}?role={{ request('role') }}', '{{ $u->name }}')">Hapus</button>
                                    </div>
                                </td>
                            @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $users->links() }}
</div>
    <form id="deleteForm" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
    </form>
</div>
@endsection

@section('scripts')
<script>
// Client-side search (filters current page rows only)
(function(){
    const input = document.getElementById('user-search');
    const clearBtn = document.getElementById('user-search-clear');
    const table = document.querySelector('table');
    if (!input || !table) return;
    const rows = Array.from(table.querySelectorAll('tbody tr'));

    function normalize(s){
        return (s || '').toString().toLowerCase().trim();
    }

    function applyFilter(){
        const q = normalize(input.value);
        rows.forEach(row => {
            const text = normalize(row.innerText);
            row.style.display = q === '' || text.includes(q) ? '' : 'none';
        });
    }

    input.addEventListener('input', applyFilter);
    if (clearBtn){
        clearBtn.addEventListener('click', function(){
            input.value = '';
            applyFilter();
            input.focus();
        });
    }
})();

function confirmDelete(url, userName) {
    Swal.fire({
        title: 'Konfirmasi Hapus',
        html: `<div class="mb-2 text-danger"><i class="fa-solid fa-trash-can"></i></div>Apakah Anda yakin ingin menghapus pengguna <strong>${userName}</strong>?<br><small class="text-muted">Tindakan ini tidak dapat dibatalkan.</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        customClass: {
            popup: 'rounded-4',
            confirmButton: 'btn btn-danger px-4',
            cancelButton: 'btn btn-secondary px-4'
        },
        buttonsStyling: false
    }).then((result) => {

@if(session('success'))
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        title: 'Berhasil',
        text: '{{ addslashes(session('success')) }}',
        icon: 'success',
        confirmButtonColor: '#16a34a',
        confirmButtonText: 'OK'
    });
});
@endif
        if (result.isConfirmed) {
            const form = document.getElementById('deleteForm');
            form.action = url;
            form.submit();
        }
    });
}

document.getElementById('btn-users-print')?.addEventListener('click', function() {
    const printUrl = '{{ route('admin.users.exportPdf', ['role' => request('role'), 'category_id' => request('category_id')]) }}';
    Swal.fire({
        title: 'Cetak Laporan Pengguna?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Cetak',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = printUrl;
            document.body.appendChild(iframe);
            iframe.onload = function() {
                iframe.focus();
                iframe.contentWindow.print();
            };
        }
    });
});
</script>
@endsection
