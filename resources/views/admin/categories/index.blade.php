@extends('layouts.sidebar')

@section('page-title')
    <i class="fa-solid fa-folder-open me-2"></i>Manajemen Kategori
@endsection

@section('title', 'Manajemen Kategori')

@section('content')
<style>
    .categories-toolbar {
        gap: 0.75rem;
    }

    .categories-toolbar-right {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
        justify-content: flex-end;
    }

    .categories-search,
    .categories-export-group,
    .categories-create-btn {
        flex: 0 1 auto;
    }

    /* Responsive categories table */
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

        .categories-toolbar {
            flex-direction: column;
            align-items: stretch !important;
            gap: 10px;
        }

        .categories-toolbar-right {
            width: 100%;
            justify-content: stretch;
        }

        .categories-search,
        .categories-export-group,
        .categories-create-btn {
            width: 100%;
            max-width: 100% !important;
        }

        .categories-export-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .categories-export-group .btn {
            flex: 1 1 100%;
        }

        .btn-primary {
            width: 100%;
        }

        h1.h3 {
            font-size: 1.25rem;
        }

        .d-flex.gap-2 {
            gap: 5px !important;
        }

        .btn-sm {
            padding: 4px 8px;
            font-size: 11px;
        }
    }

    @media (max-width: 576px) {
        table th, table td {
            padding: 8px 6px;
        }

        .d-flex.justify-content-end {
            flex-direction: column;
            width: 100%;
        }

        .btn {
            width: 100%;
            margin-bottom: 5px;
        }
    }
</style>
<div class="container-fluid">
<div class="d-flex justify-content-between align-items-center mb-3 categories-toolbar">
    <h1 class="h3 mb-0">Manajemen Kategori</h1>
    <div class="categories-toolbar-right">
        <div class="input-group categories-search" style="max-width: 360px;">
            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input id="category-search" type="text" class="form-control" placeholder="Cari nama / slug..." autocomplete="off">
            <button class="btn btn-outline-secondary" type="button" id="category-search-clear" title="Clear">Clear</button>
        </div>
        <div class="btn-group categories-export-group" role="group">
            <a href="{{ route('admin.categories.exportPdf') }}" class="btn btn-sm btn-warning text-dark" title="Download" download>
                <i class="fa-solid fa-download me-1"></i>Download
            </a>
            <a href="{{ route('admin.categories.exportCsv') }}" class="btn btn-sm btn-success" title="Export CSV">
                <i class="fa-solid fa-file-csv me-1"></i>CSV
            </a>
            <button type="button" class="btn btn-sm btn-danger" id="btn-categories-print" title="Cetak/PDF">
                <i class="fa-solid fa-print me-1"></i>PDF/Cetak
            </button>
        </div>
        <a href="{{ route('admin.categories.create') }}" class="btn btn-primary categories-create-btn">Tambah Kategori</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Slug</th>
                    <th>Deskripsi</th>
                    <th>Jumlah Admin</th>
                    <th>Jumlah Teknisi</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $c)
                    <tr>
                        <td>
                            @if(method_exists($categories, 'currentPage'))
                                {{ ($categories->currentPage() - 1) * $categories->perPage() + $loop->iteration }}
                            @else
                                {{ $loop->iteration }}
                            @endif
                        </td>
                        <td>{{ $c->name }}</td>
                        <td>{{ $c->slug }}</td>
                        <td>{{ $c->description }}</td>
                        <td>{{ (int)($c->admins_count ?? 0) }}</td>
                        <td>{{ (int)($c->agents_count ?? 0) }}</td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('admin.categories.edit', $c) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <button class="btn btn-sm btn-danger" onclick="confirmDelete('{{ route('admin.categories.destroy', $c) }}', 'kategori ini')">Hapus</button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $categories->links() }}
</div>
</div>

<form id="deleteForm" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@section('scripts')
<script>
(function(){
    @if(session('deleted_success'))
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: 'Berhasil',
            text: '{{ addslashes(session('deleted_success')) }}',
            icon: 'success',
            confirmButtonColor: '#16a34a',
            confirmButtonText: 'OK'
        });
    });
    @endif
})();

(function(){
    const input = document.getElementById('category-search');
    const clearBtn = document.getElementById('category-search-clear');
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

function confirmDelete(url, itemName) {
    Swal.fire({
        title: 'Konfirmasi Hapus',
        html: `<div class="mb-2 text-danger"><i class="fa-solid fa-trash-can"></i></div>Apakah Anda yakin ingin menghapus <strong>${itemName}</strong>?<br><small class="text-muted">Tindakan ini tidak dapat dibatalkan.</small>`,
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
        if (result.isConfirmed) {
            const form = document.getElementById('deleteForm');
            form.action = url;
            form.submit();
        }
    });
}

document.getElementById('btn-categories-print')?.addEventListener('click', function() {
    const printUrl = '{{ route('admin.categories.exportPdf') }}';
    Swal.fire({
        title: 'Cetak Laporan Kategori?',
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
