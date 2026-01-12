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
                <tbody id="tracking-tbody">
                    @include('admin.technicians._tracking_rows', ['agents' => $agents, 'workStatus' => $workStatus])
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function(){
    const tbody = document.getElementById('tracking-tbody');
    const partialUrl = @json(route('admin.technicians.trackingPartial'));
    const categoryId = window.__ctm?.categoryId;
    if (!tbody || !partialUrl || !categoryId) return;

    async function refreshTracking(){
        try {
            const url = new URL(partialUrl, window.location.origin);
            const current = new URL(window.location.href);
            ['q', 'level', 'status'].forEach(k => {
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
            if (data && typeof data.rowsHtml === 'string') {
                tbody.innerHTML = data.rowsHtml;
            }
        } catch (e) {
            // silent
        }
    }

    function subscribe(){
        if (!window.Echo || !window.Echo.private) return false;
        const ch = window.Echo.private('category.' + categoryId);
        ch.listen('.users.changed', refreshTracking);
        ch.listen('.tickets.changed', refreshTracking);
        return true;
    }

    window.addEventListener('ctm:echo-ready', subscribe);
    document.addEventListener('DOMContentLoaded', function(){
        if (window.Echo) subscribe();
    });
})();
</script>
@endsection
