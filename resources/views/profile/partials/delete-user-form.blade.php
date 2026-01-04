<section>
    <div class="alert alert-danger" style="border-radius: 10px; border-left: 4px solid #dc2626;">
        <strong><i class="fa-solid fa-triangle-exclamation me-2"></i>Peringatan:</strong> Setelah akun dihapus, semua data akan hilang secara permanen. Backup data penting Anda sebelum melanjutkan.
    </div>

    <button type="button" class="btn btn-danger" style="border-radius: 10px; padding: 10px 24px;" onclick="confirmDeleteAccount()">
        <i class="fa-solid fa-trash-can me-2"></i>Hapus Akun Permanen
    </button>

    <form id="deleteAccountForm" method="post" action="{{ route('profile.destroy') }}" style="display:none;">
        @csrf
        @method('delete')
    </form>
</section>

<style>
/* SweetAlert popup width responsive */
.swal2-popup.swal-delete-account { width: min(92vw, 520px) !important; }
</style>

<script>
function confirmDeleteAccount() {
    Swal.fire({
        title: 'Hapus Akun Permanen',
        html: '<div class="mb-2 text-danger"><i class="fa-solid fa-trash-can"></i></div><p class="mb-3">Setelah akun dihapus, semua data akan hilang secara permanen.</p><p class="text-muted small">Anda yakin ingin menghapus akun ini?</p>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus Akun!',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        customClass: {
            popup: 'rounded-4 swal-delete-account',
            confirmButton: 'btn btn-danger px-4',
            cancelButton: 'btn btn-secondary px-4'
        },
        buttonsStyling: false,
        width: 'auto',
        preConfirm: () => true
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById('deleteAccountForm');
            form.submit();
        }
    });
}
</script>
