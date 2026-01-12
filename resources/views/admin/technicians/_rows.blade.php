@foreach($users as $u)
    <tr>
        <td>
            @if(is_object($users) && method_exists($users, 'currentPage'))
                {{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}
            @else
                {{ $loop->iteration }}
            @endif
        </td>
        <td>{{ $u->name }}</td>
        <td>{{ $u->email }}</td>
        <td>
            @php($lv = $u->availability_status ?? 'junior')
            @if($lv === 'expert')
                <span class="badge text-bg-success">Expert</span>
            @elseif($lv === 'intermediate')
                <span class="badge text-bg-warning text-dark">Intermediate</span>
            @else
                <span class="badge text-bg-secondary">Junior</span>
            @endif
        </td>
        <td class="text-end">
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.technicians.edit', $u) }}" class="btn btn-sm btn-outline-primary">Ubah</a>
                <button class="btn btn-sm btn-danger" onclick="confirmDelete('{{ route('admin.technicians.destroy', $u) }}', '{{ $u->name }}')">Hapus</button>
            </div>
        </td>
    </tr>
@endforeach
