@extends('layouts.sidebar')

@section('page-title')
    <i class="fa-solid fa-comments me-2"></i>Diskusi
@endsection

@section('title', 'Diskusi')

@section('content')
<style>
    .panel { border: 1px solid #e5e7eb; border-radius: 14px; box-shadow: 0 10px 24px rgba(15,23,42,0.06); }
    .panel-header { padding: 12px 16px; border-bottom: 1px solid #e5e7eb; font-weight: 700; letter-spacing: .01em; }
</style>

<div class="container-fluid">
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
    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h1 class="h4 mb-1">Diskusi Tiket</h1>
            <div class="text-muted">Pilih tiket untuk membuka halaman komentar dan diskusi.</div>
        </div>
        <div>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('tickets.index') }}">Kembali ke Tiket</a>
        </div>
    </div>

    <form class="row g-2 mb-3" method="GET" action="{{ route('discussions.index') }}">
        <div class="col-md-6">
            <input class="form-control" type="text" name="q" placeholder="Search judul/kategori/customer/agent/lokasi..." value="{{ $q ?? '' }}">
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
        <div class="col-md-2">
            <select class="form-select" name="priority">
                <option value="">Semua Prioritas</option>
                <option value="low" @selected(($priority ?? '')==='low')>Low</option>
                <option value="medium" @selected(($priority ?? '')==='medium')>Medium</option>
                <option value="high" @selected(($priority ?? '')==='high')>High</option>
            </select>
        </div>
        <div class="col-md-1 d-flex gap-2">
            <button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-filter"></i></button>
        </div>
        <div class="col-12">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('discussions.index') }}">Reset</a>
        </div>
    </form>

    <div class="panel">
        <div class="panel-header d-flex align-items-center">
            <span>Daftar Tiket</span>
            <span class="badge bg-secondary ms-auto">{{ is_array($tickets ?? null) ? count($tickets) : 0 }}</span>
        </div>
        <div class="p-3">
            @if(isset($tickets) && is_array($tickets) && count($tickets) > 0)
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th style="width: 45%">Judul</th>
                                <th>Status</th>
                                <th>Prioritas</th>
                                <th>Terakhir Update</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tickets as $t)
                                @php
                                    $stat = (string) ($t['status'] ?? '');
                                    $prio = (string) ($t['priority'] ?? '');
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $t['title'] ?? 'Ticket' }}</div>
                                        <div class="text-muted small">ID: {{ $t['id'] ?? '-' }}</div>
                                    </td>
                                    <td>
                                        <span class="badge text-bg-{{ $statusColors[$stat] ?? 'secondary' }}">
                                            {{ $stat !== '' ? ucfirst(str_replace('_',' ', $stat)) : '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge text-bg-{{ $priorityColors[$prio] ?? 'secondary' }}">
                                            {{ $prio !== '' ? strtoupper(substr($prio, 0, 1)) : '-' }}
                                        </span>
                                    </td>
                                    <td class="text-muted">{{ $t['updated_at_iso'] ?? ($t['created_at_iso'] ?? '-') }}</td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-primary" href="{{ route('discussions.show', $t['id']) }}">
                                            <i class="fa-solid fa-message me-2"></i>Buka Diskusi
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-info mb-0">
                    <strong><i class="fa-solid fa-circle-info me-2"></i>Belum ada tiket.</strong>
                    Buat tiket terlebih dahulu untuk mulai berdiskusi.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
