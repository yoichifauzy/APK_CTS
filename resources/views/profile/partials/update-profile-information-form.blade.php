<section>
    <p class="text-muted small mb-4">Perbarui informasi profil dan alamat email akun Anda.</p>

    @if(session('status') === 'profile-updated')
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 10px; border-left: 4px solid #16a34a;">
            <i class="fa-solid fa-circle-check me-2"></i>Profil berhasil diperbarui.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">@csrf</form>

    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')

        <div class="mb-3">
            <label for="name" class="form-label fw-semibold"><span class="text-primary"><i class="fa-solid fa-user"></i></span> Nama Lengkap</label>
            <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" style="border-radius: 10px; padding: 12px;" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" placeholder="Masukkan nama lengkap">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="email" class="form-label fw-semibold"><span class="text-primary"><i class="fa-solid fa-envelope"></i></span> Email Address</label>
            <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" style="border-radius: 10px; padding: 12px;" value="{{ old('email', $user->email) }}" required autocomplete="username" placeholder="email@example.com">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2">
                    <p class="small text-muted">{{ __('Your email address is unverified.') }}
                        <button form="send-verification" class="btn btn-link p-0 align-baseline">{{ __('Click here to re-send the verification email.') }}</button>
                    </p>
                    @if (session('status') === 'verification-link-sent')
                        <div class="alert alert-info mt-2">{{ __('A new verification link has been sent to your email address.') }}</div>
                    @endif
                </div>
            @endif
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary" style="border-radius: 10px; padding: 10px 24px;"><i class="fa-solid fa-floppy-disk me-2"></i>Simpan Perubahan</button>
        </div>
    </form>
</section>
