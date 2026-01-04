@extends('layouts.sidebar')

@section('page-title')
    <i class="fa-solid fa-comments me-2"></i>Diskusi
@endsection

@section('title', 'Diskusi Ticket')

@section('content')
<style>
    .panel { border: 1px solid #e5e7eb; border-radius: 14px; box-shadow: 0 10px 24px rgba(15,23,42,0.06); }
    .panel-header { padding: 12px 16px; border-bottom: 1px solid #e5e7eb; font-weight: 700; letter-spacing: .01em; }

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
    .badge-role { font-size: .75rem; }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h1 class="h5 mb-1">{{ $ticket['title'] ?? 'Ticket' }}</h1>
            <div class="text-muted">ID: {{ $ticket['id'] ?? '-' }}</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('discussions.index') }}">Kembali</a>
            <a class="btn btn-outline-primary btn-sm" href="{{ route('tickets.show', $ticket['id']) }}">Detail Tiket</a>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header d-flex align-items-center gap-2">
            <span><i class="fa-solid fa-comments me-2"></i>Komentar & Diskusi</span>
            <span class="badge bg-secondary ms-auto">{{ isset($comments) && is_array($comments) ? count($comments) : 0 }}</span>
        </div>
        <div class="p-3">
            @if(isset($comments) && is_array($comments) && count($comments) > 0)
                <div class="mb-4">
                    <div class="chat-list">
                        @foreach($comments as $c)
                            @php
                                $authUser = auth()->user();
                                $commentUserId = $c['user_id'] ?? null;
                                $isMine = false;
                                if (!is_null($commentUserId)) {
                                    $isMine = (string)$commentUserId === (string)($authUser->id ?? '');
                                } else {
                                    $isMine = ($c['user_name'] ?? '') === ($authUser->name ?? '') && ($c['user_role'] ?? '') === ($authUser->role ?? '');
                                }
                            @endphp

                            <div class="chat-row {{ $isMine ? 'me' : 'other' }}">
                                @if(!$isMine)
                                    <div class="chat-avatar">{{ strtoupper(substr($c['user_name'] ?? 'U',0,1)) }}</div>
                                @endif

                                <div class="chat-bubble">
                                    <div class="chat-meta">
                                        <div>
                                            <span class="chat-name">{{ $c['user_name'] ?? 'User' }}</span>
                                            @if(isset($c['user_role']))
                                                @if($c['user_role'] === 'admin')
                                                    <span class="badge bg-danger badge-role ms-1">Admin</span>
                                                @elseif($c['user_role'] === 'agent')
                                                    <span class="badge bg-warning text-dark badge-role ms-1">Agent</span>
                                                @else
                                                    <span class="badge bg-success badge-role ms-1">Customer</span>
                                                @endif
                                            @endif
                                        </div>
                                        <div class="chat-time">{{ $c['created_at_iso'] ?? '' }}</div>
                                    </div>

                                    <div class="chat-text">{{ $c['comment'] ?? $c['message'] ?? '(tidak ada komentar)' }}</div>

                                    @if(isset($c['attachments']) && is_array($c['attachments']) && count($c['attachments']) > 0)
                                        <div class="mt-2">
                                            <div class="small fw-semibold text-muted">Bukti / Lampiran</div>
                                            <div class="list-group list-group-flush mt-1">
                                                @foreach($c['attachments'] as $attc)
                                                    <div class="list-group-item d-flex justify-content-between align-items-center p-2">
                                                        <div class="text-truncate" style="max-width:70%">{{ $attc['name'] ?? basename($attc['path'] ?? 'file') }}</div>
                                                        <div>
                                                            @if(!empty($attc['temp_url']))
                                                                <a class="btn btn-sm btn-outline-primary" href="{{ $attc['temp_url'] }}" target="_blank" rel="noopener">Download</a>
                                                            @else
                                                                <span class="text-muted small">(URL tidak tersedia)</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                @if($isMine)
                                    <div class="chat-avatar">{{ strtoupper(substr($c['user_name'] ?? 'U',0,1)) }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="alert alert-info mb-3">
                    <strong><i class="fa-solid fa-circle-info me-2"></i>Belum ada komentar.</strong><br>
                    Jadilah yang pertama memberikan update atau pertanyaan!
                </div>
            @endif

            <div class="border-top pt-3">
                <form method="POST" action="{{ route('tickets.comments.store', $ticket['id']) }}">
                    @csrf
                    <input type="hidden" name="redirect_to" value="{{ route('discussions.show', $ticket['id'], false) }}">
                    <div class="mb-2">
                        <label class="form-label fw-semibold">Tambah Komentar</label>
                        <textarea name="message" rows="3" class="form-control @error('message') is-invalid @enderror" placeholder="Contoh: Mohon update progres, atau saya lampirkan detail tambahan." required>{{ old('message') }}</textarea>
                        @error('message')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            <i class="fa-solid fa-lightbulb me-2"></i>Tips: Jelaskan progress, kendala, atau pertanyaan
                        </div>
                    </div>
                    <button class="btn btn-primary" type="submit">
                        <i class="fa-solid fa-paper-plane me-2"></i>Kirim Komentar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
