@extends('layouts.sidebar')

@section('page-title')
    <i class="fa-solid fa-ticket me-2"></i>Tiket
@endsection

@section('title', 'Tiket')

@section('content')
<style>
    /* Responsive table untuk mobile */
    @media (max-width: 768px) {
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table {
            min-width: 700px;
        }

        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }

        h1.h3 {
            font-size: 1.25rem;
        }
    }

    @media (max-width: 576px) {
        .d-flex.justify-content-between {
            flex-direction: column;
            gap: 10px;
        }

        .d-flex.justify-content-between > div:first-child,
        .d-flex.justify-content-between > a {
            width: 100%;
            text-align: center;
        }

        .btn-primary {
            width: 100%;
        }

        table {
            font-size: 13px;
        }

        .text-end {
            white-space: nowrap;
        }
    }
</style>

<style>
    .row-status-open > td, .row-status-open > th { background-color: rgba(var(--bs-primary-rgb), 0.06); }
    .row-status-assigned > td, .row-status-assigned > th { background-color: rgba(var(--bs-info-rgb), 0.08); }
    .row-status-in_progress > td, .row-status-in_progress > th { background-color: rgba(var(--bs-warning-rgb), 0.10); }
    .row-status-resolved > td, .row-status-resolved > th { background-color: rgba(var(--bs-success-rgb), 0.06); }
    .row-status-closed > td, .row-status-closed > th { background-color: rgba(var(--bs-danger-rgb), 0.06); }

    .ticket-panel-header { background: var(--bs-primary-bg-subtle); }
</style>
<?php use Carbon\Carbon; ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">
            @if(auth()->user()->role === 'agent')
                <i class="fa-solid fa-list-check me-2"></i>Tiket yang Harus Dikerjakan
            @else
                <i class="fa-solid fa-ticket me-2"></i>Daftar Tiket
            @endif
        </h1>
        <div class="text-muted small"><span id="ticket-count">{{ count($tickets) }}</span> tiket</div>
    </div>
    @if(auth()->user()->role === 'customer')
        <a class="btn btn-primary" href="{{ route('tickets.create') }}"><i class="fa-solid fa-circle-plus me-2"></i>Buat Tiket</a>
    @endif
</div>

<form class="d-flex gap-2 mb-3 align-items-center flex-wrap" method="GET" action="{{ route('tickets.index') }}">
    <input type="text" id="search-ticket" name="q" class="form-control" placeholder="Cari tiket..." value="{{ $search ?? request('q') }}" style="flex: 1; min-width: 200px;">
    <select id="filter-status" name="status" class="form-select" style="flex: 0 0 auto; min-width: 180px;">
        <option value="">Semua Status</option>
        @if(in_array(auth()->user()->role, ['admin','customer','super_admin'], true))
            <option value="open" @selected(($statusFilter ?? request('status'))==='open')>Open</option>
            <option value="assigned" @selected(($statusFilter ?? request('status'))==='assigned')>Assigned</option>
            <option value="in_progress" @selected(($statusFilter ?? request('status'))==='in_progress')>In Progress</option>
            <option value="resolved" @selected(($statusFilter ?? request('status'))==='resolved')>Resolved</option>
            <option value="closed" @selected(($statusFilter ?? request('status'))==='closed')>Closed</option>
        @elseif(auth()->user()->role === 'agent')
            <option value="assigned" @selected(($statusFilter ?? request('status'))==='assigned')>Assigned</option>
            <option value="in_progress" @selected(($statusFilter ?? request('status'))==='in_progress')>In Progress</option>
            <option value="resolved" @selected(($statusFilter ?? request('status'))==='resolved')>Resolved</option>
            <option value="closed" @selected(($statusFilter ?? request('status'))==='closed')>Closed</option>
        @endif
    </select>
    <button type="submit" class="btn btn-outline-secondary">
        <i class="fa-solid fa-magnifying-glass"></i>
    </button>
    <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary">Reset</a>
</form>

<div class="card">
    @if(in_array(auth()->user()->role, ['admin','agent'], true))
        <div class="card-header ticket-panel-header">
            <div class="fw-semibold">Panel Tiket</div>
            <div class="small text-muted">Status warna mengikuti aturan terbaru</div>
        </div>
    @endif
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 60px;">No</th>
                    <th>Judul</th>
                    <th>Kategori</th>
                    @if(in_array(auth()->user()->role, ['admin','agent'], true))
                        <th>Lokasi</th>
                    @endif
                    <th>Prioritas</th>
                    <th>Status</th>
                    @if(auth()->user()->role === 'admin')
                        <th>Customer</th>
                    @endif
                    <th>Update</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $statusColors = [
                        // Requested mapping:
                        // Open = Biru, Assigned = Cyan, In Progress = Kuning, Resolved = Hijau, Close = Merah
                        'open' => 'primary',
                        'assigned' => 'info',
                        'in_progress' => 'warning',
                        'resolved' => 'success',
                        'closed' => 'danger',
                    ];
                    $priorityColors = [
                        'low' => 'success',
                        'medium' => 'warning',
                        'high' => 'danger',
                    ];
                @endphp
                @forelse($tickets as $t)
                    @php
                        $stat = $t['status'] ?? 'open';
                        $rowStatusClass = 'row-status-' . $stat;
                    @endphp
                    <tr class="ticket-row {{ $rowStatusClass }}" data-status="{{ $stat }}" data-title="{{ strtolower($t['title'] ?? '') }}" data-category="{{ strtolower($t['category'] ?? '') }}" data-customer="{{ strtolower($t['customer_name'] ?? '') }}">
                        <td>
                            @if(is_object($tickets) && method_exists($tickets, 'currentPage'))
                                {{ ($tickets->currentPage() - 1) * $tickets->perPage() + $loop->iteration }}
                            @else
                                {{ $loop->iteration }}
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold">{{ Str::limit($t['title'] ?? '-', 40) }}</div>
                            @if(!empty($t['attachments']))
                                <small class="text-muted">{{ count($t['attachments']) }} file</small>
                            @endif
                        </td>
                        <td><small>{{ $t['category'] ?? '-' }}</small></td>
                        @if(in_array(auth()->user()->role, ['admin','agent'], true))
                            <td><small>{{ $t['location'] ?? '-' }}</small></td>
                        @endif
                        <td>
                            @php
                                $prio = $t['priority'] ?? '-';
                            @endphp
                            <span class="badge text-bg-{{ $priorityColors[$prio] ?? 'secondary' }}">{{ strtoupper($prio[0] ?? '-') }}</span>
                        </td>
                        <td>
                            @php
                                $stat = $t['status'] ?? 'open';
                            @endphp
                            <span class="badge text-bg-{{ $statusColors[$stat] ?? 'secondary' }}">{{ ucfirst(str_replace('_', ' ', $stat)) }}</span>
                        </td>
                        @if(auth()->user()->role === 'admin')
                            <td><small>{{ $t['customer_name'] ?? '-' }}</small></td>
                        @endif
                        @php
                            $ts = $t['updated_at_iso'] ?? $t['created_at_iso'] ?? null;
                            try {
                                if ($ts) {
                                    $dt = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $ts, 'Asia/Jakarta');
                                    if (! $dt) {
                                        $dt = \Carbon\Carbon::parse($ts)->setTimezone('Asia/Jakarta');
                                    }
                                } else {
                                    $dt = \Carbon\Carbon::now('Asia/Jakarta');
                                }
                            } catch (Exception $e) {
                                $dt = \Carbon\Carbon::now('Asia/Jakarta');
                            }
                        @endphp
                        <td><small class="text-muted">{{ $dt->diffForHumans(\Carbon\Carbon::now('Asia/Jakarta')) }}</small></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('tickets.show', $t['id']) }}">Buka</a>
                            @if(auth()->user()->role === 'customer')
                                @if(($stat ?? 'open') !== 'closed')
                                    <button type="button" class="btn btn-sm btn-dark btn-close-ticket" data-id="{{ $t['id'] }}" data-status="{{ $stat }}">
                                        Close
                                    </button>
                                    <form id="close-form-{{ $t['id'] }}" action="{{ route('tickets.customerClose', $t['id']) }}" method="POST" style="display:none;">
                                        @csrf
                                        <input type="hidden" name="note" id="close-note-{{ $t['id'] }}" value="">
                                    </form>
                                @endif
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-edit-ticket" data-id="{{ $t['id'] }}" data-status="{{ $stat }}">Edit</button>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-ticket" data-id="{{ $t['id'] }}" data-status="{{ $stat }}">Hapus</button>
                                <form id="delete-form-{{ $t['id'] }}" action="{{ route('tickets.destroy', $t['id']) }}" method="POST" style="display:none;">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            @elseif(auth()->user()->role === 'admin')
                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-ticket" data-id="{{ $t['id'] }}" data-status="{{ $stat }}">Hapus</button>
                                <form id="delete-form-{{ $t['id'] }}" action="{{ route('tickets.destroy', $t['id']) }}" method="POST" style="display:none;">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->role === 'admin' ? 9 : (in_array(auth()->user()->role, ['admin','agent'], true) ? 8 : 7) }}" class="text-center py-5">
                            <div class="text-muted">Tidak ada tiket</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('click', function (e) {
        const el = e.target;
        if (!el.classList) return;

        if (el.classList.contains('btn-close-ticket')) {
            const id = el.getAttribute('data-id');
            const status = (el.getAttribute('data-status') || 'open').toString();
            const form = document.getElementById('close-form-' + id);
            const noteEl = document.getElementById('close-note-' + id);
            if (!form || !noteEl) return;

            if (status === 'resolved') {
                noteEl.value = '';
                form.submit();
                return;
            }

            if (['open', 'assigned', 'in_progress'].includes(status)) {
                Swal.fire({
                    title: 'Tutup Ticket?',
                    html: 'Wajib isi catatan alasan menutup ticket.',
                    icon: 'warning',
                    input: 'textarea',
                    inputPlaceholder: 'Tuliskan alasan menutup ticket...',
                    inputAttributes: { maxlength: 2000 },
                    inputValidator: (value) => {
                        if (!value || !value.trim()) return 'Catatan wajib diisi.';
                        return null;
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Close',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((res) => {
                    if (res.isConfirmed){
                        noteEl.value = (res.value || '').toString().trim();
                        form.submit();
                    }
                });
                return;
            }

            // fallback: no note required
            noteEl.value = '';
            form.submit();
            return;
        }

        if (el.classList.contains('btn-edit-ticket')) {
            const id = el.getAttribute('data-id');
            const status = el.getAttribute('data-status');
            if (status === 'open') {
                // navigate to edit page
                window.location.href = '/tickets/' + encodeURIComponent(id) + '/edit';
            } else {
                // show alert using SweetAlert2
                Swal.fire({
                    icon: 'info',
                    title: 'Tidak dapat diedit',
                    html: 'Ticket tidak dapat diedit karena status saat ini: <strong>' + status.replace(/_/g, ' ') + '</strong>',
                    confirmButtonText: 'OK'
                });
            }
        }

        if (el.classList.contains('btn-delete-ticket')) {
            const id = el.getAttribute('data-id');
            const status = el.getAttribute('data-status');
            const role = '{{ auth()->user()->role }}';

            // Admin bisa hapus tiket dengan status apapun
            if (role === 'admin') {
                Swal.fire({
                    title: 'Hapus Tiket',
                    html: '<div class="mb-2 text-danger"><i class="fa-solid fa-trash-can"></i></div>Apakah Anda yakin ingin menghapus tiket ini?<br><small class="text-muted">Status: <strong>' + status.replace(/_/g, ' ') + '</strong></small><br><small class="text-danger">Tindakan ini tidak dapat dibatalkan.</small>',
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
                        const form = document.getElementById('delete-form-' + id);
                        if (form) form.submit();
                    }
                });
            } else if (status === 'open') {
                // Customer hanya bisa hapus tiket dengan status open
                Swal.fire({
                    title: 'Yakin ingin menghapus tiket ini?',
                    text: 'Tindakan ini tidak dapat dibatalkan.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Hapus',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('delete-form-' + id);
                        if (form) form.submit();
                    }
                });
            } else {
                Swal.fire({
                    icon: 'info',
                    title: 'Tidak dapat dihapus',
                    html: 'Ticket tidak dapat dihapus karena status saat ini: <strong>' + status.replace(/_/g, ' ') + '</strong>',
                    confirmButtonText: 'OK'
                });
            }
        }
    });

    // Filter dan Search (client-side) untuk semua role
    const filterStatus = document.getElementById('filter-status');
    const searchInput = document.getElementById('search-ticket');
    const ticketRows = document.querySelectorAll('.ticket-row');
    const ticketCount = document.getElementById('ticket-count');

    function filterTickets() {
        const statusFilter = filterStatus ? filterStatus.value.toLowerCase() : '';
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        let visibleCount = 0;

        ticketRows.forEach(row => {
            const status = row.getAttribute('data-status') || '';
            const title = row.getAttribute('data-title') || '';
            const category = row.getAttribute('data-category') || '';
            const customer = row.getAttribute('data-customer') || '';

            const matchesStatus = !statusFilter || status === statusFilter;
            const matchesSearch = !searchTerm ||
                title.includes(searchTerm) ||
                category.includes(searchTerm) ||
                customer.includes(searchTerm);

            if (matchesStatus && matchesSearch) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (ticketCount) {
            ticketCount.textContent = visibleCount;
        }
    }

    if (filterStatus) {
        filterStatus.addEventListener('change', filterTickets);
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterTickets);
    }
</script>
@endsection
