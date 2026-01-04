@extends('layouts.sidebar')

@section('page-title')
    <i class="fa-solid fa-folder-open me-2"></i>Manajemen Kategori
@endsection

@section('title', 'Manajemen Kategori')

@section('content')
<style>
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
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Manajemen Kategori</h1>
    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">Tambah Kategori</a>
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
</script>
@endsection
