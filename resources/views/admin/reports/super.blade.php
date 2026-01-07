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

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Laporan Tiket (Super Admin)</h1>
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-warning text-dark" href="{{ route('admin.ticketReports.exportPdf', request()->only(['from','to','status'])) }}">
                <i class="fa-solid fa-download me-1"></i>Download
            </a>
            <a class="btn btn-sm btn-success" href="{{ route('admin.ticketReports.exportCsv', request()->only(['from','to','status'])) }}">
                <i class="fa-solid fa-file-csv me-1"></i>CSV
            </a>
            <button class="btn btn-sm btn-danger" type="button" id="btn-report-print" data-url="{{ route('admin.ticketReports.print', request()->only(['from','to','status'])) }}">
                <i class="fa-solid fa-print me-1"></i>PDF/Cetak
            </button>
        </div>
    </div>

    <form method="GET" class="row g-2 align-items-end mb-3">
        <div class="col-md-3">
            <label class="form-label">Dari Tanggal</label>
            <input type="date" class="form-control" name="from" value="{{ $from }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Sampai Tanggal</label>
            <input type="date" class="form-control" name="to" value="{{ $to }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
                <option value="">Semua</option>
                @foreach(['open','assigned','in_progress','resolved','closed'] as $st)
                    <option value="{{ $st }}" @selected(($status ?? '') === $st)>{{ $st }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter me-1"></i>Filter</button>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Prioritas</th>
                        <th>Lokasi</th>
                        <th>Status</th>
                        <th>Judul</th>
                        <th>Customer</th>
                        <th>Teknisi</th>
                        <th>Tanggal & Jam</th>
                        <th>Kategori</th>
                        <th>Lampiran</th>
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
                            <td><small>{{ optional($t->customer)->name ?? '-' }}</small></td>
                            <td><small>{{ optional($t->agent)->name ?? '-' }}</small></td>
                            <td><small class="text-muted">{{ optional($t->created_at)->timezone('Asia/Jakarta')->format('Y-m-d H:i') }}</small></td>
                            <td><small>{{ optional($t->category)->name ?? '-' }}</small></td>
                            <td>
                                @if($hasAtt)
                                    <span class="badge text-bg-success">Ada</span>
                                @else
                                    <span class="badge text-bg-secondary">Tidak</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5">
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

            const launchPrint = (targetUrl) => {
                const iframe = document.createElement('iframe');
                iframe.style.position = 'fixed';
                iframe.style.right = '0';
                iframe.style.bottom = '0';
                iframe.style.width = '0';
                iframe.style.height = '0';
                iframe.style.border = '0';
                iframe.src = targetUrl;
                iframe.onload = function(){
                    try {
                        iframe.contentWindow.focus();
                        iframe.contentWindow.print();
                    } catch (e) {}
                    setTimeout(() => { iframe.remove(); }, 1500);
                };
                document.body.appendChild(iframe);
            };

            const start = () => {
                let target = url;
                try {
                    const u = new URL(url, window.location.origin);
                    target = u.toString();
                } catch (e) {}
                launchPrint(target);
            };

            if (typeof Swal === 'undefined') {
                start();
                return;
            }

            Swal.fire({
                icon: 'question',
                title: 'Cetak Laporan?',
                html: 'Dialog cetak PDF akan muncul.',
                showCancelButton: true,
                confirmButtonText: 'Ya, Cetak',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            }).then((res) => {
                if (res.isConfirmed) {
                    start();
                }
            });
        });
    })();
</script>
@endsection
