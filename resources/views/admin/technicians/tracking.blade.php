@extends('layouts.sidebar')

@section('page-title')
    <i class="fa-solid fa-chart-line me-2"></i>Tracking Teknisi
@endsection

@section('title', 'Tracking Teknisi')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="fa-solid fa-chart-line me-2"></i>Tracking Teknisi/Agent</h1>
        <a href="{{ route('admin.technicians.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    @php
        $flow = ['open', 'assigned', 'in_progress', 'resolved'];
        $labels = [
            'open' => 'Open',
            'assigned' => 'Assigned',
            'in_progress' => 'In Progress',
            'resolved' => 'Resolved',
        ];
        $colors = [
            'open' => 'primary',
            'assigned' => 'info',
            'in_progress' => 'warning',
            'resolved' => 'success',
        ];
    @endphp

    <form class="row g-2 mb-3" method="GET" action="{{ route('admin.technicians.tracking') }}">
        <div class="col-md-5">
            <input class="form-control" type="text" name="q" placeholder="Search nama/email teknisi..." value="{{ $q ?? '' }}">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="level">
                <option value="">Semua Level</option>
                <option value="junior" @selected(($level ?? '')==='junior')>Junior</option>
                <option value="intermediate" @selected(($level ?? '')==='intermediate')>Intermediate</option>
                <option value="expert" @selected(($level ?? '')==='expert')>Expert</option>
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select" name="status">
                <option value="">Semua Status</option>
                <option value="open" @selected(($status ?? '')==='open')>Open</option>
                <option value="assigned" @selected(($status ?? '')==='assigned')>Assigned</option>
                <option value="in_progress" @selected(($status ?? '')==='in_progress')>In Progress</option>
                <option value="resolved" @selected(($status ?? '')==='resolved')>Resolved</option>
            </select>
        </div>
        <div class="col-md-1 d-flex gap-2">
            <button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
        </div>
        <div class="col-12">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.technicians.tracking') }}">Reset</a>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 70px">No</th>
                        <th>Nama Teknisi</th>
                        <th>Tracking Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($agents as $agent)
                        @php
                            $cur = $workStatus[$agent->id] ?? 'open';
                            // If agent has no active ticket, we treat it as Open (belum ada kerjaan)
                            $curIndex = array_search($cur, $flow, true);
                            if ($curIndex === false) { $curIndex = 0; }
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <div class="fw-semibold">{{ $agent->name }}</div>
                                <div class="text-muted small">{{ $agent->email }}</div>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($flow as $idx => $st)
                                        @php
                                            $isReached = $idx <= $curIndex;
                                            $cls = $isReached ? ('text-bg-' . ($colors[$st] ?? 'secondary')) : 'text-bg-light text-dark';
                                        @endphp
                                        <span class="badge {{ $cls }}">
                                            {{ $labels[$st] ?? strtoupper($st) }}
                                            @if($st === 'open' && $cur === 'open')
                                                <span class="ms-1">(belum ada kerjaan)</span>
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    @if($agents->count() === 0)
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">Belum ada teknisi pada jobdesk ini.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
