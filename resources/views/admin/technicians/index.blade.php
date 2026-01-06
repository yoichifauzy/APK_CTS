@extends('layouts.sidebar')

@section('page-title')
    <i class="fa-solid fa-user-gear me-2"></i>Kelola Teknisi
@endsection

@section('title', 'Kelola Teknisi')

@section('content')
<style>
    @media (max-width: 992px) {
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { min-width: 760px; }
    }

    @media (max-width: 768px) {
        table { font-size: 13px; }
        .d-flex.justify-content-between { flex-direction: column; gap: 10px; }
        .btn-primary { width: 100%; }
        h1.h3 { font-size: 1.25rem; }
    }

    @media (max-width: 576px) {
        .btn-sm { padding: 4px 8px; font-size: 11px; }
        table th, table td { padding: 8px 6px; }
        .btn { width: 100%; margin-bottom: 5px; }
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="fa-solid fa-user-gear me-2"></i>Kelola Teknisi</h1>
        <div class="d-flex gap-2 align-items-center" style="min-width: 320px;">
            <form method="GET" action="{{ route('admin.technicians.index') }}" class="d-flex gap-2 align-items-center">
                <select name="level" class="form-select" style="max-width: 200px" onchange="this.form.submit()">
                    <option value="">Semua Level</option>
                    <option value="junior" @selected(($level ?? request('level'))==='junior')>Junior</option>
                    <option value="intermediate" @selected(($level ?? request('level'))==='intermediate')>Intermediate</option>
                    <option value="expert" @selected(($level ?? request('level'))==='expert')>Expert</option>
                </select>
                @if(!empty($level ?? request('level')))
                    <a class="btn btn-outline-secondary" href="{{ route('admin.technicians.index') }}">Reset</a>
                @endif
            </form>
            <div class="input-group" style="max-width: 360px;">
                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input id="user-search" type="text" class="form-control" placeholder="Cari nama / email..." autocomplete="off">
                <button class="btn btn-outline-secondary" type="button" id="user-search-clear" title="Clear">Clear</button>
            </div>
            <a href="{{ route('admin.technicians.create') }}" class="btn btn-primary">Tambah Teknisi</a>
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
                        <th>Level</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $u)
                        <tr>
                            <td>
                                @if(is_object($users) && method_exists($users, 'currentPage'))
                                    {{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}
                                @else
                                    {{ $loop->iteration }}
                                @endif
                            </td>
                            <td>{{ $u->name }}</td>
                            <td>{{ $u->email }}</td>
                            <td>
                                @php($lv = $u->availability_status ?? 'junior')
                                @if($lv === 'expert')
                                    <span class="badge text-bg-success">Expert</span>
                                @elseif($lv === 'intermediate')
                                    <span class="badge text-bg-warning text-dark">Intermediate</span>
                                @else
                                    <span class="badge text-bg-secondary">Junior</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.technicians.edit', $u) }}" class="btn btn-sm btn-outline-primary">Ubah</a>
                                    <button class="btn btn-sm btn-danger" onclick="confirmDelete('{{ route('admin.technicians.destroy', $u) }}', '{{ $u->name }}')">Hapus</button>
                                </div>
                            </td>
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
        html: `<div class="mb-2 text-danger"><i class="fa-solid fa-trash-can"></i></div>Apakah Anda yakin ingin menghapus teknisi <strong>${userName}</strong>?<br><small class="text-muted">Tindakan ini tidak dapat dibatalkan.</small>`,
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
