@extends('layouts.sidebar')

@section('page-title')
    <i class="fa-solid fa-file-lines me-2"></i>Laporan Tiket
@endsection

@section('title', 'Laporan Tiket')

@section('content')
@php
    $statusColors = [
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

<style>
    .row-status-open > td, .row-status-open > th { background-color: rgba(var(--bs-primary-rgb), 0.06); }
    .row-status-assigned > td, .row-status-assigned > th { background-color: rgba(var(--bs-info-rgb), 0.08); }
    .row-status-in_progress > td, .row-status-in_progress > th { background-color: rgba(var(--bs-warning-rgb), 0.10); }
    .row-status-resolved > td, .row-status-resolved > th { background-color: rgba(var(--bs-success-rgb), 0.06); }
    .row-status-closed > td, .row-status-closed > th { background-color: rgba(var(--bs-danger-rgb), 0.06); }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Laporan Tiket (Jobdesk)</h1>
        <div class="text-muted small">Menampilkan tiket sesuai kategori/jobdesk Admin</div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-secondary" href="{{ route('admin.reports.exportPdf', request()->query()) }}">
            <i class="fa-solid fa-download me-2"></i>Download
        </a>
        <a class="btn btn-outline-secondary" href="{{ route('admin.reports.exportCsv', request()->query()) }}">
            <i class="fa-solid fa-file-excel me-2"></i>Excel/CSV
        </a>
        <button class="btn btn-outline-secondary" type="button" id="btn-report-print" data-url="{{ route('admin.reports.print', request()->query()) }}">
            <i class="fa-solid fa-print me-2"></i>PDF/Cetak
        </button>
    </div>
</div>

<form class="row g-2 mb-3" method="GET" action="{{ route('admin.reports.index') }}">
    <div class="col-md-4">
        <input class="form-control" type="text" name="q" placeholder="Search judul/customer/agent/lokasi..." value="{{ $q ?? '' }}">
    </div>
    <div class="col-md-3">
        <select class="form-select" name="status">
            <option value="">Semua Status</option>
            <option value="open" @selected(($status ?? '')==='open')>Open</option>
            <option value="assigned" @selected(($status ?? '')==='assigned')>Assigned</option>
            <option value="in_progress" @selected(($status ?? '')==='in_progress')>In Progress</option>
            <option value="resolved" @selected(($status ?? '')==='resolved')>Resolved</option>
            <option value="closed" @selected(($status ?? '')==='closed')>Closed</option>
        </select>
    </div>
    <div class="col-md-3">
        <select class="form-select" name="priority">
            <option value="">Semua Prioritas</option>
            <option value="low" @selected(($priority ?? '')==='low')>Low</option>
            <option value="medium" @selected(($priority ?? '')==='medium')>Medium</option>
            <option value="high" @selected(($priority ?? '')==='high')>High</option>
        </select>
    </div>
    <div class="col-md-2 d-flex gap-2">
        <button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-filter me-2"></i>Filter</button>
        <a class="btn btn-outline-secondary" href="{{ route('admin.reports.index') }}">Reset</a>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:60px;">No</th>
                    <th>Prioritas</th>
                    <th>Lokasi</th>
                    <th>Status</th>
                    <th>Judul</th>
                    <th>Nama Customer</th>
                    <th>Lampiran</th>
                    <th>Tanggal Masuk</th>
                    <th>Agent</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tickets as $i => $t)
                    @php
                        $stat = $t->status ?? 'open';
                        $prio = $t->priority ?? '-';
                        $rowStatusClass = 'row-status-' . $stat;
                        $hasAtt = is_array($t->attachments) && count($t->attachments) > 0;
                    @endphp
                    <tr class="{{ $rowStatusClass }}">
                        <td>{{ $tickets->firstItem() + $i }}</td>
                        <td><span class="badge text-bg-{{ $priorityColors[$prio] ?? 'secondary' }}">{{ strtoupper(substr((string)$prio,0,1)) }}</span></td>
                        <td><small>{{ $t->location ?? '-' }}</small></td>
                        <td><span class="badge text-bg-{{ $statusColors[$stat] ?? 'secondary' }}">{{ ucfirst(str_replace('_',' ',$stat)) }}</span></td>
                        <td class="fw-semibold">{{ $t->title }}</td>
                        <td><small>{{ $t->customer->name ?? '-' }}</small></td>
                        <td>
                            @if($hasAtt)
                                <span class="badge text-bg-success">Ada</span>
                            @else
                                <span class="badge text-bg-secondary">Tidak</span>
                            @endif
                        </td>
                        <td><small class="text-muted">{{ optional($t->created_at)->format('Y-m-d H:i') }}</small></td>
                        <td><small>{{ $t->agent->name ?? '-' }}</small></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div class="text-muted">Tidak ada data</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $tickets->links() }}
</div>
@endsection

@section('scripts')
<script>
    (function(){
        const btn = document.getElementById('btn-report-print');
        if (!btn) return;

        btn.addEventListener('click', function(){
            const url = btn.getAttribute('data-url');
            if (!url) return;

            if (typeof Swal === 'undefined') {
                window.location.href = url;
                return;
            }

            Swal.fire({
                icon: 'question',
                title: 'Cetak Laporan?',
                html: 'Akan membuka halaman cetak. Lanjutkan?',
                showCancelButton: true,
                confirmButtonText: 'Ya, Cetak',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            }).then((res) => {
                if (res.isConfirmed) {
                    try {
                        const u = new URL(url, window.location.origin);
                        if (!u.searchParams.has('autoprint')) {
                            u.searchParams.set('autoprint', '1');
                        }
                        window.location.href = u.toString();
                    } catch (e) {
                        const sep = url.includes('?') ? '&' : '?';
                        window.location.href = url + sep + 'autoprint=1';
                    }
                }
            });
        });
    })();
</script>
@endsection
