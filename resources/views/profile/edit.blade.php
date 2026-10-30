@extends('layouts.admin', [
    'role' => auth()->user()->isStaff() ? 'staff' : 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route(auth()->user()->dashboardRouteName())],
        ['label' => 'Profil Saya'],
    ],
])

@section('title', 'Profil Saya')

@section('content')
    <x-admin.page-header
        eyebrow="Akun internal"
        title="Profil Saya"
        description="Perbarui informasi akun dan kata sandi Anda. Perubahan role dan penugasan anggota hanya dapat dilakukan oleh administrator melalui mekanisme backend."
    />

    @if (session('status') === 'profile-updated')
        <div class="alert alert-success mb-3" role="status">Informasi profil berhasil diperbarui.</div>
    @endif

    @if (session('status') === 'password-updated')
        <div class="alert alert-success mb-3" role="status">Kata sandi berhasil diperbarui.</div>
    @endif

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="app-card mb-3">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Informasi Profil</h2>
                        <p class="app-card-subtitle">Nama, username, dan alamat email yang digunakan untuk masuk.</p>
                    </div>
                </div>

                <div class="app-card-body">
                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('patch')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="name">Nama Lengkap <span class="req">*</span></label>
                                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}"
                                       class="form-control @error('name') is-invalid @enderror"
                                       required autofocus autocomplete="name">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="username">Username <span class="req">*</span></label>
                                <input type="text" id="username" name="username" value="{{ old('username', $user->username) }}"
                                       class="form-control @error('username') is-invalid @enderror"
                                       required autocomplete="username">
                                @error('username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="email">Alamat Email <span class="req">*</span></label>
                                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}"
                                       class="form-control @error('email') is-invalid @enderror"
                                       required autocomplete="email">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-emerald">
                                <i class="ri-save-3-line" aria-hidden="true"></i>
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="app-card">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Keamanan Akun</h2>
                        <p class="app-card-subtitle">Gunakan kata sandi yang kuat dan tidak dipakai di layanan lain.</p>
                    </div>
                </div>

                <div class="app-card-body">
                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf
                        @method('put')

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="current_password">Kata Sandi Saat Ini <span class="req">*</span></label>
                                <input type="password" id="current_password" name="current_password"
                                       class="form-control @if ($errors->updatePassword->has('current_password')) is-invalid @endif"
                                       required autocomplete="current-password">
                                @if ($errors->updatePassword->has('current_password'))
                                    <div class="invalid-feedback">{{ $errors->updatePassword->first('current_password') }}</div>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="password">Kata Sandi Baru <span class="req">*</span></label>
                                <input type="password" id="password" name="password"
                                       class="form-control @if ($errors->updatePassword->has('password')) is-invalid @endif"
                                       required autocomplete="new-password">
                                @if ($errors->updatePassword->has('password'))
                                    <div class="invalid-feedback">{{ $errors->updatePassword->first('password') }}</div>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="password_confirmation">Ulangi Kata Sandi Baru <span class="req">*</span></label>
                                <input type="password" id="password_confirmation" name="password_confirmation"
                                       class="form-control" required autocomplete="new-password">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-emerald">
                                <i class="ri-lock-password-line" aria-hidden="true"></i>
                                Perbarui Kata Sandi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="app-card">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Ringkasan Akun</h2>
                        <p class="app-card-subtitle">Hak akses yang sedang aktif pada sesi ini.</p>
                    </div>
                </div>

                <div class="app-card-body">
                    <dl class="detail-list">
                        <div class="detail-row">
                            <dt>Nama</dt>
                            <dd>{{ $user->name }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Username</dt>
                            <dd>{{ $user->username }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Email</dt>
                            <dd>{{ $user->email }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Role</dt>
                            <dd>{{ $user->role_label }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Anggota binaan</dt>
                            <dd>{{ $user->member?->nama_lengkap ?? 'Tidak ada' }}</dd>
                        </div>
                    </dl>

                    <div class="demo-note mb-0">
                        <i class="ri-information-line" aria-hidden="true"></i>
                        <div>Penghapusan akun mandiri dinonaktifkan. Hubungi administrator sekretariat untuk perubahan role atau penonaktifan akun.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
