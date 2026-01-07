<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Export Kategori</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print mb-3">
        <button class="btn btn-sm btn-secondary" onclick="window.close()">Tutup</button>
        <button class="btn btn-sm btn-danger" onclick="window.print()">Cetak</button>
    </div>

    <h4 class="mb-3">Daftar Kategori</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-sm">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Slug</th>
                    <th>Deskripsi</th>
                    <th>Jumlah Admin</th>
                    <th>Jumlah Teknisi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $i => $c)
                    <tr>
                        <td>{{ $i+1 }}</td>
                        <td>{{ $c->name }}</td>
                        <td>{{ $c->slug }}</td>
                        <td>{{ $c->description }}</td>
                        <td>{{ (int)($c->admins_count ?? 0) }}</td>
                        <td>{{ (int)($c->agents_count ?? 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
