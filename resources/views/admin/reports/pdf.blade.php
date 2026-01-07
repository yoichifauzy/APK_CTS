<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Tiket</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h2 { margin: 0 0 10px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 6px 6px; vertical-align: top; }
        th { background: #eee; }
    </style>
</head>
<body>
    <h2>Laporan Tiket (Jobdesk)</h2>
    <table>
        <thead>
            <tr>
                <th style="width:40px;">No</th>
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
            @foreach($tickets as $i => $t)
                @php
                    $hasAtt = is_array($t->attachments) && count($t->attachments) > 0;
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $t->priority ?? '-' }}</td>
                    <td>{{ $t->location ?? '-' }}</td>
                    <td>{{ ucfirst(str_replace('_',' ', (string) ($t->status ?? '-'))) }}</td>
                    <td>{{ $t->title ?? '-' }}</td>
                    <td>{{ $t->customer->name ?? '-' }}</td>
                    <td>{{ $hasAtt ? 'Ada' : 'Tidak' }}</td>
                    <td>{{ optional($t->created_at)->format('Y-m-d H:i') }}</td>
                    <td>{{ $t->agent->name ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
