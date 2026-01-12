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
        $stat = (string) ($ticket['status'] ?? '');
        $prio = (string) ($ticket['priority'] ?? '');
    @endphp
    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h1 class="h5 mb-1">{{ $ticket['title'] ?? 'Ticket' }}</h1>
            <div class="text-muted">ID: {{ $ticket['id'] ?? '-' }}</div>
            <div class="mt-2 d-flex flex-wrap gap-2">
                <span class="badge text-bg-{{ $statusColors[$stat] ?? 'secondary' }}">
                    {{ $stat !== '' ? ucfirst(str_replace('_',' ', $stat)) : '-' }}
                </span>
                <span class="badge text-bg-{{ $priorityColors[$prio] ?? 'secondary' }}">
                    {{ $prio !== '' ? strtoupper(substr($prio, 0, 1)) : '-' }}
                </span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('discussions.index') }}">Kembali</a>
            <a class="btn btn-outline-primary btn-sm" href="{{ route('tickets.show', $ticket['id']) }}">Detail Tiket</a>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header d-flex align-items-center gap-2">
            <span><i class="fa-solid fa-comments me-2"></i>Komentar & Diskusi</span>
            <span class="badge bg-secondary ms-auto" id="discussion-count">{{ isset($comments) && is_array($comments) ? count($comments) : 0 }}</span>
        </div>
        <div class="p-3">
            <div class="mb-4" id="discussion-chat">
                @include('discussions._chat', ['comments' => $comments])
            </div>

            <div class="border-top pt-3">
                <form method="POST" enctype="multipart/form-data" action="{{ route('tickets.comments.store', $ticket['id']) }}">
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
                    <input id="comment-attachments" type="file" name="attachments[]" multiple style="display:none;">
                    <div class="d-flex gap-2 align-items-center">
                        <button class="btn btn-outline-secondary" type="button" id="btn-pick-attachments" title="Lampirkan file">
                            <i class="fa-solid fa-paperclip"></i>
                        </button>
                        <button class="btn btn-primary" type="submit">
                            <i class="fa-solid fa-paper-plane me-2"></i>Kirim Komentar
                        </button>
                        <span class="text-muted small" id="attachment-hint" style="display:none;"></span>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    (function(){
        const btn = document.getElementById('btn-pick-attachments');
        const input = document.getElementById('comment-attachments');
        const hint = document.getElementById('attachment-hint');
        if (!btn || !input) return;

        btn.addEventListener('click', function(){
            input.click();
        });

        input.addEventListener('change', function(){
            if (!hint) return;
            const files = Array.from(input.files || []);
            if (files.length === 0) {
                hint.style.display = 'none';
                hint.textContent = '';
                return;
            }
            hint.style.display = '';
            hint.textContent = files.length + ' file dipilih';
        });
    })();

    (function(){
        const ticketId = @json((string)($ticket['id'] ?? ''));
        const container = document.getElementById('discussion-chat');
        const countEl = document.getElementById('discussion-count');
        if (!ticketId || !container) return;

        const partialUrl = @json(route('discussions.partial', ['ticket' => (string)($ticket['id'] ?? '')]));

        function scrollToBottom(){
            try {
                const panelBody = container.closest('.panel')?.querySelector('.p-3');
                if (panelBody) {
                    panelBody.scrollTop = panelBody.scrollHeight;
                }
            } catch (e) {
                // ignore
            }
        }

        async function refreshChat(){
            try {
                const res = await fetch(partialUrl, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    cache: 'no-store'
                });
                if (!res.ok) return;
                const data = await res.json();
                if (data && typeof data.chatHtml === 'string') {
                    container.innerHTML = data.chatHtml;
                }
                if (countEl && data && typeof data.count !== 'undefined') {
                    countEl.textContent = String(data.count);
                }
                scrollToBottom();
            } catch (e) {
                // silent
            }
        }

        function subscribe(){
            if (!window.Echo || !window.Echo.private) return false;
            window.Echo.private('ticket.' + ticketId)
                .listen('.ticket.comments.changed', function(){
                    refreshChat();
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
