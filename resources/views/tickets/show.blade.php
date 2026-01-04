@extends('layouts.sidebar')

@section('page-title')
    <i class="fa-solid fa-ticket me-2"></i>{{ $ticket['title'] ?? 'Tiket' }}
@endsection

@section('title', 'Detail Tiket')

@section('content')
<style>
    .pill { border-radius: 999px; padding: 6px 12px; font-weight: 600; font-size: .85rem; }
    .panel { border: 1px solid #e5e7eb; border-radius: 14px; box-shadow: 0 10px 24px rgba(15,23,42,0.06); }
    .panel-header { padding: 12px 16px; border-bottom: 1px solid #e5e7eb; font-weight: 700; letter-spacing: .01em; }
    .timeline { position: relative; padding-left: 24px; }
    .timeline::before { content: ''; position: absolute; left: 8px; top: 6px; bottom: 6px; width: 2px; background: #e5e7eb; }
    .timeline-item { position: relative; padding: 10px 0 10px 12px; --tl-rgb: var(--bs-primary-rgb); }
    .timeline-item::before { content: ''; position: absolute; left: -16px; top: 12px; width: 10px; height: 10px; border-radius: 50%; background: rgb(var(--tl-rgb)); box-shadow: 0 0 0 4px rgba(var(--tl-rgb), 0.18); }
    .attachment-card { border: 1px solid #e5e7eb; border-radius: 12px; padding: 10px 12px; }
    /* Chat bubbles (WhatsApp-like left/right) */
    .chat-list { display: flex; flex-direction: column; gap: 10px; }
    .chat-row { display: flex; align-items: flex-end; gap: 10px; }
    .chat-row.me { justify-content: flex-end; }
    .chat-row.other { justify-content: flex-start; }
    .chat-avatar { width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg,#2563eb,#7c3aed); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; flex: 0 0 auto; }
    .chat-bubble { max-width: min(720px, 78%); border: 1px solid #e5e7eb; border-radius: 16px; padding: 10px 12px; box-shadow: 0 6px 16px rgba(15,23,42,0.05); }
    .chat-row.other .chat-bubble { background: #fff; border-top-left-radius: 10px; }
    .chat-row.me .chat-bubble { background: var(--bs-primary-bg-subtle); border-color: rgba(var(--bs-primary-rgb), 0.18); border-top-right-radius: 10px; }
    .chat-meta { display: flex; justify-content: space-between; align-items: baseline; gap: 10px; margin-bottom: 6px; }
    .chat-name { font-weight: 700; }
    .chat-time { color: #6b7280; font-size: 12px; white-space: nowrap; }
    .chat-text { white-space: pre-wrap; }
    .avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg,#2563eb,#7c3aed); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; }
    .badge-role { font-size: .75rem; }
    .comment-body { background: #f8fafc; border-radius: 10px; padding: 10px 12px; }

    /* Responsive styles */
    @media (max-width: 992px) {
        .col-lg-8, .col-lg-4 {
            width: 100%;
            margin-bottom: 20px;
        }

        .d-flex.gap-2 {
            flex-wrap: wrap;
        }
    }

    @media (max-width: 768px) {
        .container-fluid {
            padding: 0;
        }

        .d-flex.justify-content-between {
            flex-direction: column;
            gap: 10px;
        }

        .d-flex.gap-2, .d-flex.gap-3 {
            flex-direction: column;
            width: 100%;
            gap: 8px !important;
        }

        .btn {
            width: 100%;
        }

        .panel {
            border-radius: 10px;
        }

        .row.g-3 {
            gap: 10px 0;
        }

        .col-md-6 {
            width: 100%;
        }

        h1.h3 {
            font-size: 1.25rem;
        }

        .attachment-card {
            flex-direction: column;
            text-align: center;
        }

        .attachment-card .text-truncate {
            max-width: 100% !important;
        }
    }

    @media (max-width: 576px) {
        .pill {
            font-size: 0.75rem;
            padding: 4px 10px;
        }

        .badge {
            font-size: 0.7rem;
        }

        .comment-meta {
            flex-direction: column;
            align-items: flex-start;
        }

        .avatar {
            width: 32px;
            height: 32px;
            font-size: 14px;
        }

        .panel-header {
            font-size: 14px;
            padding: 10px 12px;
        }

        .btn-sm {
            font-size: 11px;
            padding: 4px 8px;
        }

        textarea {
            font-size: 14px;
        }

        .container-fluid {
            padding-left: 0;
            padding-right: 0;
        }

        .d-flex.align-items-center.gap-3 {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 10px !important;
        }

        .card {
            margin-bottom: 15px;
        }

        .card-body {
            padding: 12px;
        }

        h1.h3 {
            font-size: 1.1rem;
        }
    }
</style>
<div class="container-fluid">
<div class="d-flex justify-content-between align-items-start gap-3 mb-3">
    <div>
        <h1 class="h3 mb-1">{{ $ticket['title'] ?? 'Ticket' }}</h1>
        <div class="text-muted">ID: {{ $ticket['id'] }}</div>
        @php
            $statusColors = [
                'open' => 'secondary',
                'assigned' => 'info',
                'in_progress' => 'warning',
                'resolved' => 'success',
                'closed' => 'dark',
            ];
            $priorityColors = [
                'low' => 'success',
                'medium' => 'warning',
                'high' => 'danger',
            ];
        @endphp
        <div class="mt-2 d-flex flex-wrap align-items-center gap-2">
            @php
                $stat = $ticket['status'] ?? 'open';
                $prio = $ticket['priority'] ?? '-';
            @endphp
            <span class="badge text-bg-{{ $statusColors[$stat] ?? 'secondary' }}">{{ $stat }}</span>
            <span class="badge text-bg-{{ $priorityColors[$prio] ?? 'secondary' }}">{{ $prio }}</span>
            @if(isset($ticket['attachments']) && (is_array($ticket['attachments']) || (function_exists('is_countable') && is_countable($ticket['attachments']))) && count($ticket['attachments']) > 0)
                <span class="badge text-bg-light text-dark">{{ count($ticket['attachments']) }} lampiran</span>
            @endif
        </div>
    </div>
    <div class="d-flex gap-2 align-items-start">
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('tickets.index') }}">Kembali</a>

        @if(in_array(($ticket['status'] ?? 'open'), ['resolved','closed']))
            <button
                class="btn btn-outline-primary btn-sm"
                type="button"
                id="btn-open-barcode"
                onclick='(function(){
                    const payload = @json('cloudticket:' . ($ticket['id'] ?? ''));
                    const qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=520x520&ecc=M&margin=1&data=" + encodeURIComponent(payload);
                    Swal.fire({
                        icon: "info",
                        title: "Barcode Ticket",
                        html: "<div style=\"display:flex;justify-content:center;\">" +
                              "<img src=\"" + qrUrl + "\" alt=\"Barcode\" style=\"max-width:100%;width:380px;height:auto;border-radius:12px;border:1px solid #e5e7eb;background:#fff;padding:10px;\" />" +
                              "</div>",
                        confirmButtonText: "Tutup"
                    });
                })();'
            >
                <i class="fa-solid fa-qrcode me-2"></i>Buka Barcode
            </button>
            <a class="btn btn-primary btn-sm" href="{{ route('tickets.barcode.download', $ticket['id']) }}">
                <i class="fa-solid fa-download me-2"></i>Download Barcode
            </a>
        @endif

        {{-- HANYA customer yang buat ticket DAN status masih 'open' boleh edit --}}
        @if(($ticket['customer_id'] ?? null) === auth()->id() && ($ticket['status'] ?? 'open') === 'open')
            <a class="btn btn-outline-primary" href="{{ route('tickets.edit', $ticket['id']) }}">
                <i class="fa-solid fa-pen-to-square me-2"></i>Ubah Ticket
            </a>
        @elseif(($ticket['customer_id'] ?? null) === auth()->id() && ($ticket['status'] ?? 'open') !== 'open')
            <button class="btn btn-outline-secondary" disabled title="Ticket yang sudah diproses tidak bisa diedit">
                <i class="fa-solid fa-lock me-2"></i>Terkunci (sudah diproses)
            </button>
        @endif
        {{-- Admin atau customer bisa hapus --}}
        @if(auth()->user()->role === 'admin' || ($ticket['customer_id'] ?? null) === auth()->id())
            <button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteTicket('{{ route('tickets.destroy', $ticket['id']) }}', '{{ $ticket['status'] ?? 'open' }}')"><i class="fa-solid fa-trash-can me-2"></i>Hapus</button>
        @endif
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="panel mb-3">
            <div class="panel-header">Rincian Tiket</div>
            <div class="p-3">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="small text-muted">Kategori</div>
                        <div class="fw-semibold">{{ $ticket['category'] ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Prioritas</div>
                        @php $prio = $ticket['priority'] ?? '-'; @endphp
                        <span class="pill bg-{{ $priorityColors[$prio] ?? 'secondary' }} text-white">{{ ucfirst($prio) }}</span>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Status</div>
                        @php $stat = $ticket['status'] ?? 'open'; @endphp
                        <span class="pill bg-{{ $statusColors[$stat] ?? 'secondary' }} text-white">{{ ucfirst(str_replace('_',' ',$stat)) }}</span>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Agent Ditugaskan</div>
                        <div class="fw-semibold">
                            @if(!empty($ticket['agent_name']))
                                {{ $ticket['agent_name'] }}
                            @elseif(!empty($ticket['agent_id']))
                                Agent #{{ $ticket['agent_id'] }}
                            @else
                                @if(($ticket['status'] ?? 'open') === 'open')
                                    Admin Sedang Mencari Agent
                                @else
                                    -
                                @endif
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Pelanggan</div>
                        <div class="fw-semibold">{{ $ticket['customer_name'] ?? '-' }}</div>
                    </div>
                </div>
                <div class="small text-muted mb-1">Deskripsi</div>
                <div class="border rounded p-3 bg-light" style="white-space: pre-wrap;">{{ $ticket['description'] ?? '-' }}</div>
            </div>
        </div>

        <div class="panel mb-3">
            <div class="panel-header">Tracking Status</div>
            <div class="p-3">
                @php
                    $history = $ticket['status_history'] ?? [];
                    if (!is_array($history)) { $history = []; }
                    $displayHistory = $history;
                    $hasOpen = collect($displayHistory)->contains(fn($h) => ($h['status'] ?? '') === 'open');
                    if (!$hasOpen) array_unshift($displayHistory, ['status' => 'open', 'changed_at_iso' => $ticket['created_at_iso'] ?? '-']);

                    $timelineRgb = [
                        'open' => 'var(--bs-secondary-rgb)',
                        'assigned' => 'var(--bs-info-rgb)',
                        'in_progress' => 'var(--bs-warning-rgb)',
                        'resolved' => 'var(--bs-success-rgb)',
                        'closed' => 'var(--bs-dark-rgb)',
                    ];
                @endphp
                <div class="timeline">
                    @foreach($displayHistory as $h)
                        @php
                            $st = $h['status'] ?? '';
                            $tlRgb = $timelineRgb[$st] ?? 'var(--bs-secondary-rgb)';
                        @endphp
                        <div class="timeline-item d-flex justify-content-between align-items-start gap-2" style="--tl-rgb: {{ $tlRgb }};">
                            <div class="fw-semibold">{{ ucfirst(str_replace('_', ' ', $h['status'] ?? '-')) }}</div>
                            <div class="text-muted small">{{ $h['changed_at_iso'] ?? ($ticket['created_at_iso'] ?? '') }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="panel mb-3">
            <div class="panel-header">Lampiran</div>
            <div class="p-3">
                @if(isset($ticket['attachments']) && is_array($ticket['attachments']) && count($ticket['attachments']) > 0)
                    <div class="row g-2">
                        @foreach($ticket['attachments'] as $att)
                            <div class="col-md-6">
                                <div class="attachment-card d-flex justify-content-between align-items-center">
                                    <div class="text-truncate" style="max-width:65%">{{ $att['name'] ?? ($att['path'] ?? 'file') }}</div>
                                    @if(!empty($att['temp_url']))
                                        <a class="btn btn-sm btn-outline-primary" href="{{ $att['temp_url'] }}" target="_blank" rel="noopener">Download</a>
                                    @else
                                        <span class="text-muted small">N/A</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-muted">Tidak ada lampiran.</div>
                @endif
            </div>
        </div>

        <div class="panel">
            <div class="panel-header d-flex align-items-center gap-2">
                <span><i class="fa-solid fa-comments me-2"></i>Komentar & Diskusi</span>
                <span class="badge bg-secondary ms-auto">{{ isset($comments) && is_array($comments) ? count($comments) : 0 }}</span>
            </div>
            <div class="p-3">
                <div class="alert alert-info mb-0">
                    Komentar dan diskusi dipindahkan ke halaman khusus agar halaman tiket tidak menumpuk.
                </div>
                <div class="mt-3">
                    <a class="btn btn-primary" href="{{ route('discussions.show', $ticket['id']) }}">
                        <i class="fa-solid fa-message me-2"></i>Buka Halaman Diskusi
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="d-grid gap-3">
            {{-- INFO: Ticket metadata --}}
            <div class="card">
                <div class="card-header"><i class="fa-solid fa-circle-info me-2"></i>Informasi Ticket</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-6 text-muted">Dibuat</dt>
                        <dd class="col-6 text-end">{{ $ticket['created_at_iso'] ?? '-' }}</dd>

                        <dt class="col-6 text-muted">Terakhir Update</dt>
                        <dd class="col-6 text-end">{{ $ticket['updated_at_iso'] ?? ($ticket['created_at_iso'] ?? '-') }}</dd>

                        <dt class="col-6 text-muted">Pelanggan</dt>
                        <dd class="col-6 text-end">{{ $ticket['customer_name'] ?? '-' }}</dd>

                        <dt class="col-6 text-muted">Agent</dt>
                        <dd class="col-6 text-end">
                            @if(!empty($ticket['agent_name']))
                                {{ $ticket['agent_name'] }}
                            @elseif(!empty($ticket['agent_id']))
                                Agent #{{ $ticket['agent_id'] }}
                            @else
                                @if(($ticket['status'] ?? 'open') === 'open')
                                    Admin Sedang Mencari Agent
                                @else
                                    -
                                @endif
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            {{-- Tugaskan Agent (Admin Only) --}}
            @if(auth()->user()->role === 'admin')
                <div class="card">
                    <div class="card-header bg-warning text-dark"><i class="fa-solid fa-user-gear me-2"></i>Tugaskan Agent</div>
                    <div class="card-body">
                        @php($ticketStatus = $ticket['status'] ?? 'open')
                        @if(in_array($ticketStatus, ['resolved','closed']))
                            <div class="alert alert-info mb-3">
                                Ticket sudah <strong>{{ strtoupper($ticketStatus) }}</strong> sehingga tidak bisa ditugaskan kembali.
                            </div>
                        @endif

                        @if(!empty($ticket['agent_id']))
                            <div class="alert alert-success mb-3">
                                <strong><i class="fa-solid fa-circle-check me-2"></i>Sudah ditugaskan ke:</strong><br>
                                <span class="fs-6">{{ $ticket['agent_name'] ?? 'Agent #'.$ticket['agent_id'] }}</span>
                            </div>
                        @else
                            <div class="alert alert-warning mb-3">
                                <strong><i class="fa-solid fa-triangle-exclamation me-2"></i>Belum ditugaskan!</strong>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('tickets.assign', $ticket['id']) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Pilih Agent</label>
                                <select name="agent_id" class="form-select form-select-sm" required {{ in_array($ticketStatus, ['resolved','closed']) ? 'disabled' : '' }}>
                                    <option value="">-- Pilih Agent --</option>
                                    @if(isset($agents) && (is_array($agents) || (function_exists('is_countable') && is_countable($agents))))
                                        @foreach($agents as $agent)
                                            <option value="{{ $agent->id }}" @selected(isset($ticket['agent_id']) && $ticket['agent_id'] == $agent->id)>{{ $agent->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <button class="btn btn-warning w-100" type="submit" {{ (in_array($ticketStatus, ['resolved','closed']) || !(isset($agents) && (is_array($agents) || (function_exists('is_countable') && is_countable($agents))) && count($agents) > 0)) ? 'disabled' : '' }}>
                                <strong><i class="fa-solid fa-paper-plane me-2"></i>Tugaskan Agent</strong>
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- Ubah Status (Admin & Agent) --}}
            <div class="card">
                <div class="card-header"><i class="fa-solid fa-chart-line me-2"></i>Ubah Status</div>
                <div class="card-body">
                    @if(auth()->user()->role === 'admin' || auth()->user()->role === 'agent')
                        <form method="POST" action="{{ route('tickets.updateStatus', $ticket['id']) }}" enctype="multipart/form-data" id="status-form">
                            @csrf
                            @php($cur = $ticket['status'] ?? 'open')
                            <div class="mb-2">
                                <label class="form-label">Status Saat Ini</label>
                                <select name="status" class="form-select form-select-sm">
                                    @if((auth()->user()->role ?? 'customer') === 'admin')
                                        <option value="assigned" @selected($cur==='assigned') {{ in_array($cur, ['resolved','closed']) ? 'disabled' : '' }}>Assigned (Ditugaskan)</option>
                                        <option value="in_progress" @selected($cur==='in_progress')>In Progress (Dikerjakan)</option>
                                        <option value="resolved" @selected($cur==='resolved')>Resolved (Selesai)</option>
                                        <option value="closed" @selected($cur==='closed')>Closed (Ditutup)</option>
                                    @elseif((auth()->user()->role ?? 'customer') === 'agent')
                                        <option value="in_progress" @selected($cur==='in_progress')>In Progress (Dikerjakan)</option>
                                        <option value="resolved" @selected($cur==='resolved')>Resolved (Selesai)</option>
                                    @endif
                                </select>
                            </div>
                                <div id="evidence-section" style="display:none;">
                                    <div class="mb-2">
                                        <label class="form-label">Catatan Bukti Kerja (wajib jika resolved)</label>
                                        <textarea name="evidence_note" class="form-control" rows="3" placeholder="Contoh: Mesin menyala normal, level oli OK..."></textarea>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Lampiran Bukti (foto, log) — minimal 1 file</label>
                                        <input type="file" name="evidence[]" class="form-control" multiple accept="image/*,video/*,application/pdf" />
                                    </div>
                                </div>
                                <button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i>Perbarui Status</button>
                            </form>

                            @section('scripts')
                            <script>
                                (function(){
                                    const form = document.getElementById('status-form');
                                    if (!form) return;
                                    const select = form.querySelector('select[name="status"]');
                                    const evidence = document.getElementById('evidence-section');
                                    const initialStatus = '{{ $ticket['status'] ?? '' }}';
                                    const role = '{{ auth()->user()->role ?? '' }}';
                                    function toggleEvidence(){
                                        if (!select) return;
                                        const val = select.value;
                                        // show evidence inputs only when agent chooses resolved
                                        if (val === 'resolved' && role === 'agent'){
                                            evidence.style.display = 'block';
                                        } else {
                                            evidence.style.display = 'none';
                                        }
                                    }
                                    if (select){
                                        // If ticket is already closed, agents must not change it.
                                        // Disable the select and submit button for clarity.
                                        if (initialStatus === 'closed' && role === 'agent'){
                                            select.disabled = true;
                                            const submitBtn = form.querySelector('button[type="submit"]');
                                            if (submitBtn) submitBtn.disabled = true;
                                            // Inform agent once on load
                                            setTimeout(function(){
                                                Swal.fire({icon:'info', title:'Ticket sudah ditutup', text:'Ticket yang telah ditutup tidak dapat diubah statusnya oleh agent.'});
                                            }, 150);
                                            // ensure evidence hidden
                                            toggleEvidence();
                                        }

                                        select.addEventListener('change', function(ev){
                                            // Normally we toggle evidence when agent selects 'resolved'
                                            // If the ticket was closed on load we already disabled controls above.
                                            toggleEvidence();
                                        });

                                        // init
                                        toggleEvidence();
                                    }
                                    // client-side pre-submit check for agents resolving
                                    form.addEventListener('submit', function(e){
                                        try{
                                            const val = select.value;
                                            if (val === 'resolved' && role === 'agent'){
                                                const note = form.querySelector('textarea[name="evidence_note"]').value.trim();
                                                const files = form.querySelector('input[name="evidence[]"]').files;
                                                if (!note){
                                                    e.preventDefault();
                                                    Swal.fire({icon:'warning', title:'Butuh catatan', text:'Tolong isi catatan bukti kerja sebelum menandai Resolved.'});
                                                    return false;
                                                }
                                                if (!files || files.length === 0){
                                                    e.preventDefault();
                                                    Swal.fire({icon:'warning', title:'Butuh bukti', text:'Lampirkan minimal 1 file bukti (foto atau log) sebelum menandai Resolved.'});
                                                    return false;
                                                }
                                            }
                                        }catch(err){/* ignore */}
                                    });
                                })();
                            </script>
                            @endsection
                        </form>
                    @else
                        <div class="text-muted">Hanya agent/admin yang bisa ubah status.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<form id="deleteTicketForm" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@section('scripts')
<script>
function confirmDeleteTicket(url, status) {
    const role = '{{ auth()->user()->role }}';

    // Admin bisa hapus tiket dengan status apapun
    if (role === 'admin') {
        Swal.fire({
            title: 'Hapus Tiket',
            html: '<div class="mb-2 text-danger"><i class="fa-solid fa-trash-can"></i></div>Apakah Anda yakin ingin menghapus tiket ini?<br><small class="text-muted">Status: <strong>' + (status ? status.replace(/_/g, ' ') : 'open') + '</strong></small><br><small class="text-danger">Tindakan ini tidak dapat dibatalkan.</small>',
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
                const form = document.getElementById('deleteTicketForm');
                form.action = url;
                form.submit();
            }
        });
    } else {
        // Customer hanya bisa hapus tiket dengan status open
        if (status === 'open') {
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
                    const form = document.getElementById('deleteTicketForm');
                    form.action = url;
                    form.submit();
                }
            });
        } else {
            Swal.fire({
                icon: 'info',
                title: 'Tidak dapat dihapus',
                html: 'Ticket tidak dapat dihapus karena status saat ini: <strong>' + (status ? status.replace(/_/g, ' ') : 'open') + '</strong>',
                confirmButtonText: 'OK'
            });
        }
    }
}
</script>
@endsection
