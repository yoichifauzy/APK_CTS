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
        @if(($role ?? null) === 'admin')
            <td>{{ $u->category->name ?? '-' }}</td>
        @elseif(($role ?? null) === 'agent')
            <td>{{ $u->category->name ?? '-' }}</td>
            <td>{{ $u->availability_status ?? '-' }}</td>
        @endif
        <td><span class="badge text-bg-secondary">{{ $u->role }}</span></td>
        @if(!($readOnly ?? false))
            <td class="text-end">
                <div class="d-flex justify-content-end gap-2">
                    @if(($isSuperAdmin ?? false))
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary btn-user-view"
                            data-bs-toggle="modal"
                            data-bs-target="#userViewModal"
                            data-role="{{ $role ?? ($u->role ?? '') }}"
                            data-name="{{ $u->name }}"
                            data-email="{{ $u->email }}"
                            data-jobdesk="{{ $u->category->name ?? '' }}"
                            data-level="{{ $u->availability_status ?? '' }}"
                        >
                            Lihat
                        </button>
                    @else
                        <a href="{{ route('admin.users.show', $u) }}" class="btn btn-sm btn-outline-secondary">Lihat</a>
                    @endif
                    <a href="{{ route('admin.users.edit', $u) }}" class="btn btn-sm btn-outline-primary">Ubah</a>
                    <button class="btn btn-sm btn-danger" onclick="confirmDelete('{{ route('admin.users.destroy', $u) }}?role={{ request('role') }}', '{{ $u->name }}')">Hapus</button>
                </div>
            </td>
        @endif
    </tr>
@endforeach
