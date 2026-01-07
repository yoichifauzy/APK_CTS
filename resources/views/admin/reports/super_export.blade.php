<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Tiket - Super Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        @media print { .no-print { display: none; } }
        table { font-size: 12px; }
    </style>
</head>
<body>
    <div class="no-print mb-3">
        <button class="btn btn-sm btn-secondary" onclick="window.close()">Tutup</button>
        <button class="btn btn-sm btn-danger" onclick="window.print()">Cetak</button>
    </div>

    <h4 class="mb-3">Laporan Tiket (Super Admin)</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-sm">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Prioritas</th>
                    <th>Lokasi</th>
                    <th>Status</th>
                    <th>Judul</th>
                    <th>Customer</th>
                    <th>Teknisi</th>
                    <th>Tanggal & Jam</th>
                    <th>Kategori</th>
                    <th>Lampiran</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tickets as $i => $t)
                    <tr>
                        <td>{{ $i+1 }}</td>
                        <td>{{ $t->priority ?? '-' }}</td>
                        <td>{{ $t->location ?? '-' }}</td>
                        <td>{{ $t->status }}</td>
                        <td>{{ $t->title }}</td>
                        <td>{{ optional($t->customer)->name }}</td>
                        <td>{{ optional($t->agent)->name }}</td>
                        <td>{{ optional($t->created_at)->timezone('Asia/Jakarta')->format('Y-m-d H:i') }}</td>
                        <td>{{ optional($t->category)->name ?? '-' }}</td>
                        <td>
                            @php $hasAtt = is_array($t->attachments) && count($t->attachments) > 0; @endphp
                            {{ $hasAtt ? 'Ada' : 'Tidak' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
