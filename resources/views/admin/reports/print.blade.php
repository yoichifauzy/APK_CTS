<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Laporan Tiket</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; margin: 16px; color: #111; }
        .toolbar { display: flex; gap: 8px; align-items: center; margin-bottom: 12px; }
        .btn { display: inline-block; padding: 8px 12px; border: 1px solid #999; background: #f5f5f5; color: #111; text-decoration: none; border-radius: 6px; cursor: pointer; }
        .btn-primary { border-color: #1d4ed8; background: #1d4ed8; color: #fff; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
        .muted { color: #6b7280; font-size: 12px; }
        @media print {
            .toolbar { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn btn-primary" onclick="window.print()">Cetak</button>
        <a class="btn" href="{{ route('admin.reports.index', request()->query()) }}">Kembali</a>
        <span class="muted">Tip: gunakan dialog browser untuk simpan sebagai PDF.</span>
    </div>

    <h2 style="margin: 0 0 6px;">Laporan Tiket</h2>
    <div class="muted" style="margin-bottom: 12px;">Dicetak pada: {{ now()->format('Y-m-d H:i:s') }}</div>

    <table>
        <thead>
            <tr>
                <th style="width:60px;">No</th>
                <th>Prioritas</th>
                <th>Lokasi</th>
                <th>Status</th>
                <th>Judul</th>
                <th>Nama Customer</th>
                <th>Lampiran</th>
                <th>Tanggal Masuk</th>
                <th>Agent</th>
            </tr>
        </thead>
        <tbody>
            @php $i = 1; @endphp
            @foreach($tickets as $t)
                @php $hasAtt = is_array($t->attachments) && count($t->attachments) > 0; @endphp
                <tr>
                    <td>{{ $i++ }}</td>
                    <td>{{ $t->priority ?? '-' }}</td>
                    <td>{{ $t->location ?? '-' }}</td>
                    <td>{{ ucfirst(str_replace('_',' ', $t->status ?? '-')) }}</td>
                    <td>{{ $t->title ?? '-' }}</td>
                    <td>{{ $t->customer->name ?? '-' }}</td>
                    <td>{{ $hasAtt ? 'Ada' : 'Tidak' }}</td>
                    <td>{{ optional($t->created_at)->format('Y-m-d H:i') }}</td>
                    <td>{{ $t->agent->name ?? '-' }}</td>
                </tr>
            @endforeach
            @if($tickets->count() === 0)
                <tr>
                    <td colspan="9" style="text-align:center; padding: 16px;" class="muted">Tidak ada data</td>
                </tr>
            @endif
        </tbody>
    </table>

    @if((string) request()->query('autoprint', '') === '1')
        <script>
            window.addEventListener('load', function(){
                // Small delay helps ensure layout is ready before printing
                setTimeout(function(){
                    window.print();
                }, 150);
            });
        </script>
    @endif
</body>
</html>
