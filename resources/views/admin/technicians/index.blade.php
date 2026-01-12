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
                <tbody id="technicians-tbody">
                    @include('admin.technicians._rows', ['users' => $users])
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3" id="technicians-pagination">
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

    function normalize(s){
        return (s || '').toString().toLowerCase().trim();
    }

    function applyFilter(){
        const q = normalize(input.value);
        const rows = Array.from(table.querySelectorAll('tbody tr'));
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

@if(session('success'))
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        title: 'Berhasil',
        text: '{{ addslashes(session('success')) }}',
        icon: 'success',
        confirmButtonText: 'OK'
    });
});
@endif

@if(session('error'))
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        title: 'Gagal',
        text: '{{ addslashes(session('error')) }}',
        icon: 'error',
        confirmButtonText: 'OK'
    });
});
@endif

// Real-time refresh (Admin subscribe ke category.{categoryId} channel)
(function(){
    const tbody = document.getElementById('technicians-tbody');
    const pager = document.getElementById('technicians-pagination');
    const partialUrl = @json(route('admin.technicians.partial'));
    const categoryId = window.__ctm?.categoryId;

    if (!tbody || !pager || !partialUrl || !categoryId) return;

    async function refreshTechniciansIndex(){
        try {
            const url = new URL(partialUrl, window.location.origin);
            const current = new URL(window.location.href);
            ['level', 'page'].forEach(k => {
                const v = current.searchParams.get(k);
                if (v !== null) url.searchParams.set(k, v);
            });

            const res = await fetch(url.toString(), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                cache: 'no-store'
            });
            if (!res.ok) return;
            const data = await res.json();
            if (data && typeof data.rowsHtml === 'string') tbody.innerHTML = data.rowsHtml;
            if (data && typeof data.paginationHtml === 'string') pager.innerHTML = data.paginationHtml;

            // re-apply search filter to new rows
            const input = document.getElementById('user-search');
            if (input) input.dispatchEvent(new Event('input'));
        } catch (e) {
            // silent
        }
    }

    function subscribe(){
        if (!window.Echo || !window.Echo.private) return false;
        window.Echo.private('category.' + categoryId)
            .listen('.users.changed', function(){
                refreshTechniciansIndex();
                // keep sidebar badges in sync too if available
                if (window.CTMLive && typeof window.CTMLive.refreshLiveSummary === 'function') {
                    window.CTMLive.refreshLiveSummary();
                }
            });
        return true;
    }

    window.addEventListener('ctm:echo-ready', subscribe);
    document.addEventListener('DOMContentLoaded', function(){
        if (window.Echo) subscribe();
    });
})();
</script>
@endsection
