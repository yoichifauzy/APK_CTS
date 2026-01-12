@if(isset($comments) && is_array($comments) && count($comments) > 0)
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
@else
    <div class="alert alert-info mb-3">
        <strong><i class="fa-solid fa-circle-info me-2"></i>Belum ada komentar.</strong><br>
        Jadilah yang pertama memberikan update atau pertanyaan!
    </div>
@endif
