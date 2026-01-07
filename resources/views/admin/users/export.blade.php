<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Export Pengguna</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print mb-3">
        <button class="btn btn-sm btn-secondary" onclick="window.close()">Tutup</button>
        <button class="btn btn-sm btn-danger" onclick="window.print()">Cetak</button>
    </div>

    <h4 class="mb-3">Daftar Pengguna ({{ ucfirst($role) }})</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-sm">
            <thead>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Email</th>
                @if($role === 'admin')
                    <th>Jobdesk</th>
                @elseif($role === 'agent')
                    <th>Jobdesk</th>
                    <th>Level</th>
                @elseif($role === 'customer')
                    <th>Kategori</th>
                    <th>Agent</th>
                @endif
                <th>Peran</th>
            </tr>
            </thead>
            <tbody>
            @foreach($users as $i => $u)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>{{ $u->name }}</td>
                    <td>{{ $u->email }}</td>
                    @if($role === 'admin')
                        <td>{{ $u->category->name ?? '-' }}</td>
                    @elseif($role === 'agent')
                        <td>{{ $u->category->name ?? '-' }}</td>
                        <td>{{ $u->availability_status ?? '-' }}</td>
                    @elseif($role === 'customer')
                        <td>{{ $u->category->name ?? '-' }}</td>
                        <td>-</td>
                    @endif
                    <td>{{ $u->role }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
