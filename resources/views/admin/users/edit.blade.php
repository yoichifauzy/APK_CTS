@extends('layouts.sidebar')

@section('page-title')
    <i class="fa-solid fa-user-pen me-2"></i>Edit Pengguna
@endsection

@section('title', 'Edit Pengguna')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Ubah Pengguna</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('admin.users.update', $user) }}" id="editUserForm">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Nama</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Kata Sandi (kosongkan jika tidak ingin mengganti)</label>
                            <input type="password" name="password" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Kata Sandi</label>
                            <input type="password" name="password_confirmation" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Peran</label>
                            <select name="role" class="form-select" required>
                                <option value="customer" @selected(old('role', $user->role)==='customer')>customer</option>
                                <option value="agent" @selected(old('role', $user->role)==='agent')>agent</option>
                                <option value="admin" @selected(old('role', $user->role)==='admin')>admin</option>
                            </select>
                        </div>

                        @php($effectiveRole = old('role', $user->role))
                        @if(in_array($effectiveRole, ['admin','agent'], true))
                            <div class="mb-3">
                                <label class="form-label">Kategori / Jobdesk</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    @foreach(($categories ?? []) as $c)
                                        <option value="{{ $c->id }}" @selected((string)old('category_id', $user->category_id) === (string)$c->id)>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        {{-- Status agent ditentukan otomatis berdasarkan tiket aktif (assigned/in_progress/resolved). --}}

                                                <div class="d-flex gap-2">
                                                        <button class="btn btn-primary" type="button" id="saveBtn">Simpan</button>
                                                        <a href="{{ route('admin.users.index', ['role' => $user->role]) }}" class="btn btn-outline-secondary">Batal</a>
                                                </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@section('scripts')
        <script>
                document.addEventListener('DOMContentLoaded', function(){
                        var saveBtn = document.getElementById('saveBtn');
                        var editForm = document.getElementById('editUserForm');
                        var confirmSaveModal = new bootstrap.Modal(document.getElementById('confirmSaveModal'));
                        var confirmSaveBtn = document.getElementById('confirmSaveBtn');

                        if (saveBtn && confirmSaveModal){
                                saveBtn.addEventListener('click', function(){
                                        confirmSaveModal.show();
                                });
                        }

                        if (confirmSaveBtn && editForm){
                                confirmSaveBtn.addEventListener('click', function(){
                                        // submit the original edit form
                                        editForm.submit();
                                });
                        }
                });
        </script>

        <!-- Confirm Save Modal -->
        <div class="modal fade" id="confirmSaveModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Konfirmasi Simpan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda ingin menyimpan perubahan ini dan kembali ke daftar pengguna?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" id="confirmSaveBtn" class="btn btn-primary">OK, Simpan</button>
                    </div>
                </div>
            </div>
        </div>
@endsection
