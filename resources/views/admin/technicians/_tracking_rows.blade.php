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

@foreach($agents as $agent)
    @php
        $cur = $workStatus[$agent->id] ?? 'open';
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
