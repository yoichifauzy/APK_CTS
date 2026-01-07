@extends('layouts.sidebar')

@section('page-title')
    <i class="fa-solid fa-chart-line me-2"></i>Dashboard
@endsection

@section('title', 'Dashboard')

@section('content')
{{-- DASHBOARD HEADER --}}
@php
    $role = auth()->user()->role ?? 'customer';
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1">Dashboard</h1>
        <div class="text-muted">Selamat datang, <strong>{{ auth()->user()->name }}</strong></div>
    </div>
    <div>
        @if($role === 'admin')
            <span class="badge text-bg-danger">Admin</span>
        @elseif($role === 'super_admin')
            <span class="badge text-bg-dark">Super Admin</span>
        @elseif($role === 'agent')
            <span class="badge text-bg-warning">Agent</span>
        @else
            <span class="badge text-bg-success">Customer</span>
        @endif
    </div>
</div>

{{-- STATISTIK CARDS --}}
<style>
    .stat-card { border: none; border-radius: 14px; box-shadow: 0 10px 24px rgba(15,23,42,0.08); height: 100%; }
    .stat-card .card-body { min-height: 120px; display: flex; justify-content: space-between; align-items: center; }
    .stat-icon { font-size: 1.8rem; }
    .stat-label { font-size: .9rem; letter-spacing: .02em; text-transform: uppercase; }

    /* Responsive dashboard */
    @media (max-width: 992px) {
        .col-lg-2 {
            width: 50%;
        }

        .stat-card .card-body {
            min-height: 100px;
        }

        .stat-icon {
            font-size: 1.5rem;
        }
    }

    @media (max-width: 768px) {
        .d-flex.justify-content-between {
            flex-direction: column;
            gap: 10px;
            text-align: center;
        }

        h1.h3 {
            font-size: 1.25rem;
        }

        .col-md-4 {
            width: 50%;
        }

        .stat-card .card-body {
            min-height: 90px;
            padding: 12px;
        }

        h3 {
            font-size: 1.5rem;
        }
    }

    @media (max-width: 576px) {
        .col-md-4, .col-lg-2 {
            width: 100%;
        }

        .stat-label {
            font-size: 0.8rem;
        }

        .stat-icon {
            font-size: 1.3rem;
        }
    }
</style>

<div class="row g-3 mb-4">
    <div class="col-md-4 col-lg-2 d-flex flex-column">
        <div class="card stat-card text-white" style="background: linear-gradient(135deg,#2563eb,#1d4ed8);">
            <div class="card-body">
                <div>
                    <div class="stat-label">Total</div>
                    <h3 class="mb-0">{{ $stats['total'] ?? 0 }}</h3>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-clipboard-list"></i></div>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-lg-2 d-flex flex-column">
        <div class="card stat-card text-white" style="background: linear-gradient(135deg,#6b7280,#4b5563);">
            <div class="card-body">
                <div>
                    <div class="stat-label">Open</div>
                    <h3 class="mb-0">{{ $stats['open'] ?? 0 }}</h3>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-circle-dot"></i></div>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-lg-2 d-flex flex-column">
        <div class="card stat-card text-white" style="background: linear-gradient(135deg,#f59e0b,#d97706);">
            <div class="card-body">
                <div>
                    <div class="stat-label">Assigned</div>
                    <h3 class="mb-0">{{ $stats['assigned'] ?? 0 }}</h3>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-thumbtack"></i></div>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-lg-2 d-flex flex-column">
        <div class="card stat-card text-white" style="background: linear-gradient(135deg,#f97316,#ea580c);">
            <div class="card-body">
                <div>
                    <div class="stat-label">In Progress</div>
                    <h3 class="mb-0">{{ $stats['in_progress'] ?? 0 }}</h3>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-gear"></i></div>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-lg-2 d-flex flex-column">
        <div class="card stat-card text-white" style="background: linear-gradient(135deg,#16a34a,#15803d);">
            <div class="card-body">
                <div>
                    <div class="stat-label">Resolved</div>
                    <h3 class="mb-0">{{ $stats['resolved'] ?? 0 }}</h3>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-lg-2 d-flex flex-column">
        <div class="card stat-card text-white" style="background: linear-gradient(135deg,#0f172a,#1f2937);">
            <div class="card-body">
                <div>
                    <div class="stat-label">Closed</div>
                    <h3 class="mb-0">{{ $stats['closed'] ?? 0 }}</h3>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-lock"></i></div>
            </div>
        </div>
    </div>
</div>

{{-- CONTENT BERDASARKAN ROLE --}}
<div class="row g-4">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                @if($role === 'admin')
                    <h5 class="card-title"><i class="fa-solid fa-bullseye me-2"></i>Admin Dashboard</h5>

                    @if(isset($stats['unassigned']) && $stats['unassigned'] > 0)
                        <div class="alert alert-warning mb-3">
                            <strong><i class="fa-solid fa-triangle-exclamation me-1"></i></strong> {{ $stats['unassigned'] }} tiket belum ditugaskan
                        </div>
                    @endif

                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-primary" href="{{ route('tickets.index') }}">
                            <i class="fa-solid fa-ticket me-2"></i>Tiket
                        </a>
                        <a class="btn btn-outline-secondary" href="{{ route('admin.technicians.index') }}">
                            <i class="fa-solid fa-user-gear me-2"></i>Teknisi
                        </a>
                    </div>

                    <hr class="my-4">

                    <div class="row g-3">
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="mb-3"><i class="fa-solid fa-chart-column me-2"></i>Jumlah Status Semua Ticket</h6>
                                    <canvas id="adminTicketStatusChart" height="140"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="mb-3"><i class="fa-solid fa-chart-line me-2"></i>Prioritas Ticket</h6>
                                    <canvas id="adminTicketPriorityChart" height="140"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="mb-3"><i class="fa-solid fa-chart-pie me-2"></i>Semua Status Teknisi</h6>
                                    <canvas id="adminTechnicianStatusChart" height="140"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="mb-3"><i class="fa-solid fa-chart-pie me-2"></i>Semua Level Teknisi</h6>
                                    <canvas id="adminTechnicianLevelChart" height="140"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                @elseif($role === 'super_admin')
                    <h5 class="card-title"><i class="fa-solid fa-crown me-2"></i>Super Admin Dashboard</h5>

                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-primary" href="{{ route('admin.users.index', ['role' => 'admin']) }}">
                            <i class="fa-solid fa-user-shield me-2"></i>Kelola Admin
                        </a>
                        <a class="btn btn-outline-secondary" href="{{ route('admin.categories.index') }}">
                            <i class="fa-solid fa-list me-2"></i>Kategori
                        </a>
                    </div>

                @elseif($role === 'agent')
                    <h5 class="card-title"><i class="fa-solid fa-headset me-2"></i>Agent Dashboard</h5>

                    @if($stats['total'] > 0)
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-primary" href="{{ route('tickets.index') }}">
                                <i class="fa-solid fa-list-check me-2"></i>Tiket yang Harus Dikerjakan ({{ $stats['total'] }})
                            </a>
                        </div>
                    @else
                        <div class="alert alert-success">
                            <strong><i class="fa-solid fa-circle-check me-2"></i>Semua tiket sudah selesai!</strong><br>
                            Tidak ada tiket yang perlu dikerjakan saat ini.
                        </div>
                    @endif

                @else
                    <h5 class="card-title"><i class="fa-solid fa-user me-2"></i>Customer Dashboard</h5>

                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-primary" href="{{ route('tickets.create') }}">
                            <i class="fa-solid fa-circle-plus me-2"></i>Buat Tiket
                        </a>
                        <a class="btn btn-outline-secondary" href="{{ route('tickets.index') }}">
                            <i class="fa-solid fa-ticket me-2"></i>Tiket Saya ({{ $stats['total'] }})
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- CHARTS - HANYA UNTUK SUPER ADMIN --}}
@if($role === 'super_admin')
<div class="row g-3 mt-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-users me-2"></i>Jumlah Admin vs Agent</div>
            <div class="card-body">
                <canvas id="chartStatusBar" height="280"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-chart-pie me-2"></i>Admin per Jobdesk</div>
            <div class="card-body">
                <canvas id="chartStatusDonut" height="280"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-chart-pie me-2"></i>Agent per Jobdesk</div>
            <div class="card-body">
                <canvas id="chartStatusLine" height="280"></canvas>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@section('scripts')
    @if(($role ?? 'customer') === 'admin')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            (function () {
                const statusLabels = @json(($adminTicketStatusLabels ?? collect())->values());
                const statusValues = @json(($adminTicketStatusValues ?? collect())->values());
                const prioLabels = @json(($adminTicketPriorityLabels ?? collect())->values());
                const prioValues = @json(($adminTicketPriorityValues ?? collect())->values());
                const techStatusLabels = @json(($adminTechnicianStatusLabels ?? collect())->values());
                const techStatusValues = @json(($adminTechnicianStatusValues ?? collect())->values());
                const techLevelLabels = @json(($adminTechnicianLevelLabels ?? collect())->values());
                const techLevelValues = @json(($adminTechnicianLevelValues ?? collect())->values());

                const el1 = document.getElementById('adminTicketStatusChart');
                if (el1) {
                    new Chart(el1, {
                        type: 'bar',
                        data: {
                            labels: statusLabels,
                            datasets: [{
                                label: 'Tickets',
                                data: statusValues,
                                backgroundColor: 'rgba(37, 99, 235, 0.25)',
                                borderColor: 'rgba(37, 99, 235, 0.9)',
                                borderWidth: 1,
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { display: false } },
                            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                        }
                    });
                }

                const el2 = document.getElementById('adminTicketPriorityChart');
                if (el2) {
                    new Chart(el2, {
                        type: 'line',
                        data: {
                            labels: prioLabels,
                            datasets: [{
                                label: 'Tickets',
                                data: prioValues,
                                borderColor: 'rgba(245, 158, 11, 0.95)',
                                backgroundColor: 'rgba(245, 158, 11, 0.15)',
                                tension: 0.25,
                                fill: true,
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { display: false } },
                            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                        }
                    });
                }

                const el3 = document.getElementById('adminTechnicianStatusChart');
                if (el3) {
                    new Chart(el3, {
                        type: 'doughnut',
                        data: {
                            labels: techStatusLabels,
                            datasets: [{
                                data: techStatusValues,
                                backgroundColor: [
                                    'rgba(107, 114, 128, 0.35)',
                                    'rgba(245, 158, 11, 0.35)',
                                    'rgba(249, 115, 22, 0.35)',
                                    'rgba(22, 163, 74, 0.35)'
                                ],
                                borderColor: [
                                    'rgba(107, 114, 128, 0.95)',
                                    'rgba(245, 158, 11, 0.95)',
                                    'rgba(249, 115, 22, 0.95)',
                                    'rgba(22, 163, 74, 0.95)'
                                ],
                                borderWidth: 1,
                            }]
                        },
                        options: { responsive: true }
                    });
                }

                const el4 = document.getElementById('adminTechnicianLevelChart');
                if (el4) {
                    new Chart(el4, {
                        type: 'pie',
                        data: {
                            labels: techLevelLabels,
                            datasets: [{
                                data: techLevelValues,
                                backgroundColor: [
                                    'rgba(59, 130, 246, 0.35)',
                                    'rgba(245, 158, 11, 0.35)',
                                    'rgba(22, 163, 74, 0.35)'
                                ],
                                borderColor: [
                                    'rgba(59, 130, 246, 0.95)',
                                    'rgba(245, 158, 11, 0.95)',
                                    'rgba(22, 163, 74, 0.95)'
                                ],
                                borderWidth: 1,
                            }]
                        },
                        options: { responsive: true }
                    });
                }
            })();
        </script>
    @endif
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function(){
            // Grafik hanya untuk super admin
            const isSuperAdmin = '{{ $role }}' === 'super_admin';
            if (!isSuperAdmin) return;

            function byId(id){ return document.getElementById(id); }

            const barEl = byId('chartStatusBar');
            if (barEl){
                const userLabels = ['Admin','Agent'];
                const userValues = [
                    {{ (int)($adminTotal ?? 0) }},
                    {{ (int)($agentTotal ?? 0) }},
                ];
                new Chart(barEl, {
                    type: 'bar',
                    data: {
                        labels: userLabels,
                        datasets: [{
                            label: 'Jumlah',
                            data: userValues,
                            backgroundColor: [
                                'rgba(220,53,69,0.85)',  // danger
                                'rgba(255,193,7,0.85)',  // warning
                            ],
                            borderRadius: 10,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 } }
                        }
                    }
                });
            }

            const donutEl = byId('chartStatusDonut');
            if (donutEl){
                const labels = @json($adminJobdeskLabels ?? []);
                const values = @json($adminJobdeskValues ?? []);

                new Chart(donutEl, {
                    type: 'doughnut',
                    data: {
                        labels: labels.length ? labels : ['-'],
                        datasets: [{
                            data: values.length ? values : [0],
                            backgroundColor: [
                                'rgba(220,53,69,0.85)',
                                'rgba(13,202,240,0.85)',
                                'rgba(255,193,7,0.85)',
                                'rgba(25,135,84,0.85)',
                                'rgba(108,117,125,0.85)',
                                'rgba(111,66,193,0.85)',
                            ],
                            borderWidth: 0,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        cutout: '62%',
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                });
            }

            const lineEl = byId('chartStatusLine');
            if (lineEl){
                const labels = @json($agentJobdeskLabels ?? []);
                const values = @json($agentJobdeskValues ?? []);
                new Chart(lineEl, {
                    type: 'doughnut',
                    data: {
                        labels: labels.length ? labels : ['-'],
                        datasets: [{
                            data: values.length ? values : [0],
                            backgroundColor: [
                                'rgba(255,193,7,0.85)',
                                'rgba(13,202,240,0.85)',
                                'rgba(25,135,84,0.85)',
                                'rgba(108,117,125,0.85)',
                                'rgba(220,53,69,0.85)',
                                'rgba(111,66,193,0.85)',
                            ],
                            borderWidth: 0,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        cutout: '62%',
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                });
            }
        })();
    </script>
@endsection
