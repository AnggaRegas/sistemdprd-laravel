@extends('layouts.admin', [
    'role' => 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route('dashboard')],
        ['label' => 'Daftar Akun Pengguna'],
    ],
])

@section('title', 'Daftar Akun Pengguna')

@section('content')
    @php
        $resetErrorAccountId = old('user_id');
        $resetErrorAccountName = $resetErrorAccountId
            ? (\App\Models\User::query()->find((int) $resetErrorAccountId)?->name ?? 'Akun terpilih')
            : null;
    @endphp

    <x-admin.page-header
        eyebrow="Manajemen akses"
        title="Daftar Akun Pengguna"
        description="Daftar seluruh akun internal dan wewenang pengarsipan."
    >
        <span class="status-badge status-neutral">
            <i class="ri-group-line" aria-hidden="true"></i>
            {{ number_format($summary['total'], 0, ',', '.') }} akun
        </span>
        <a href="{{ route('akun.create') }}" class="btn btn-emerald">
            <i class="ri-user-add-line" aria-hidden="true"></i>
            Tambah Akun Staf
        </a>
    </x-admin.page-header>

    <div class="app-card mb-3">
        <div class="app-card-body-tight">
            <div class="row g-3">
                <div class="col-6 col-xl-3">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #eef6f1; color: #155043;">
                            <i class="ri-group-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ $summary['total'] }}</strong>
                            <span>Total akun</span>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #fbf3e2; color: #a9853f;">
                            <i class="ri-shield-user-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ $summary['administrator'] }}</strong>
                            <span>Administrator</span>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #eaf2fb; color: #2c6aa0;">
                            <i class="ri-user-star-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ $summary['staff'] }}</strong>
                            <span>Staf pendamping</span>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="mini-stat">
                        <span class="mini-stat-icon" style="background: #fdecea; color: #b23a34;">
                            <i class="ri-user-forbid-line" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong>{{ $summary['inactive'] }}</strong>
                            <span>Akun nonaktif</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-card filter-card">
        <form method="GET" action="{{ route('akun.index') }}" class="filter-bar" role="search">
            <div class="filter-search">
                <i class="ri-search-line" aria-hidden="true"></i>
                <input type="search" name="q" value="{{ $filters['q'] }}"
                       placeholder="Cari nama, username, email, atau anggota dewan..."
                       aria-label="Cari akun">
            </div>

            <select name="role" class="filter-select" data-auto-submit aria-label="Filter peran akun">
                <option value="">Semua peran</option>
                <option value="administrator" @selected($filters['role'] === 'administrator')>Administrator</option>
                <option value="staf_pendamping" @selected($filters['role'] === 'staf_pendamping')>Staf Pendamping</option>
            </select>

            <select name="status" class="filter-select" data-auto-submit aria-label="Filter status akun">
                <option value="">Semua status</option>
                <option value="active" @selected($filters['status'] === 'active')>Aktif</option>
                <option value="inactive" @selected($filters['status'] === 'inactive')>Nonaktif</option>
            </select>

            <div class="filter-actions">
                <button type="submit" class="btn btn-emerald btn-sm">
                    <i class="ri-filter-3-line" aria-hidden="true"></i>
                    Terapkan
                </button>
                <a href="{{ route('akun.index') }}" class="filter-reset">
                    <i class="ri-refresh-line" aria-hidden="true"></i>
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="app-card">
        <div class="app-card-header">
            <div>
                <h2 class="app-card-title">Akun Internal</h2>
                <p class="app-card-subtitle">
                    Menampilkan {{ $accounts->firstItem() ?? 0 }}–{{ $accounts->lastItem() ?? 0 }}
                    dari {{ number_format($filteredTotal, 0, ',', '.') }} akun
                </p>
            </div>
            <span class="status-badge status-neutral">{{ number_format($filteredTotal, 0, ',', '.') }} hasil</span>
        </div>

        @if ($accounts->isEmpty())
            @if ($summary['total'] === 0)
                <x-admin.empty-state
                    icon="ri-user-settings-line"
                    title="Belum ada akun pengguna"
                    description="Belum ada akun internal yang tercatat. Tambahkan akun staf pendamping untuk mulai menugaskan wewenang pengarsipan."
                >
                    <a href="{{ route('akun.create') }}" class="btn btn-emerald btn-sm">
                        <i class="ri-user-add-line" aria-hidden="true"></i>
                        Tambah Akun Staf
                    </a>
                </x-admin.empty-state>
            @else
                <x-admin.empty-state
                    icon="ri-user-search-line"
                    title="Akun tidak ditemukan"
                    description="Tidak ada akun yang cocok dengan pencarian atau filter Anda. Coba kata kunci lain atau hapus filter."
                >
                    <a href="{{ route('akun.index') }}" class="btn btn-outline-soft btn-sm">
                        <i class="ri-refresh-line" aria-hidden="true"></i>
                        Hapus filter
                    </a>
                </x-admin.empty-state>
            @endif
        @else
            <div class="table-responsive">
                <table class="table-modern account-table">
                    <thead>
                        <tr>
                            <th scope="col" class="text-center">No.</th>
                            <th scope="col">Akun Staf</th>
                            <th scope="col">Peran</th>
                            <th scope="col">Status</th>
                            <th scope="col">Penugasan Dewan</th>
                            <th scope="col">Terdaftar</th>
                            <th scope="col" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($accounts as $account)
                            @php
                                $isSelf = auth()->id() === $account->id;
                                $isLastActiveAdministrator = $account->isAdministrator()
                                    && $account->is_active
                                    && $activeAdminCount <= 1;
                                $isProtected = $isSelf || $isLastActiveAdministrator;
                                $initials = \App\Support\PortalData::initials($account->name);
                                $member = $account->member;
                            @endphp

                            <tr>
                                <td class="text-center">
                                    <span class="cell-muted">{{ ($accounts->firstItem() ?? 1) + $loop->index }}</span>
                                </td>
                                <td>
                                    <div class="table-person">
                                        <span class="avatar avatar-sm {{ $account->isAdministrator() ? 'avatar-gold' : 'avatar-emerald' }}" aria-hidden="true">
                                            {{ $initials }}
                                        </span>
                                        <div class="table-person-meta">
                                            <strong>{{ $account->name }}</strong>
                                            <span>{{ '@'.$account->username }} &middot; {{ $account->email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge {{ $account->isAdministrator() ? 'status-gold' : 'status-info' }}">
                                        {{ $account->role_label }}
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge {{ $account->is_active ? 'status-success' : 'status-danger' }}">
                                        {{ $account->status_label }}
                                    </span>
                                </td>
                                <td>
                                    @if ($member)
                                        <span class="cell-title">{{ $member->nama_lengkap }}</span>
                                        <span class="cell-sub">{{ $member->jabatan ?: 'Anggota DPRD' }}</span>
                                    @else
                                        <span class="cell-muted">Tidak ada penugasan</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="cell-muted">
                                        {{ $account->created_at?->locale('id')->translatedFormat('d M Y') ?? '—' }}
                                    </span>
                                    <span class="cell-sub">{{ $account->created_at?->format('H:i') }} WITA</span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <div class="dropdown action-dropdown">
                                            <button type="button" class="action-menu-btn" data-bs-toggle="dropdown"
                                                    data-bs-boundary="viewport"
                                                    data-bs-popper-config='{"strategy":"fixed"}'
                                                    aria-expanded="false" aria-label="Tindakan untuk akun {{ $account->name }}">
                                                <i class="ri-more-2-fill" aria-hidden="true"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('akun.edit', $account) }}">
                                                        <i class="ri-edit-line" aria-hidden="true"></i>
                                                        Edit Akun
                                                    </a>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item"
                                                            data-bs-toggle="modal" data-bs-target="#resetPasswordModal"
                                                            data-account-id="{{ $account->id }}"
                                                            data-account-name="{{ $account->name }}">
                                                        <i class="ri-lock-password-line" aria-hidden="true"></i>
                                                        Reset Kata Sandi
                                                    </button>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>

                                                @if ($isProtected && $account->is_active)
                                                    <li>
                                                        <span class="dropdown-item-text text-muted-soft fs-10 d-flex align-items-center gap-2">
                                                            <i class="ri-lock-2-line" aria-hidden="true"></i>
                                                            {{ $isSelf ? 'Akun yang sedang digunakan' : 'Administrator aktif terakhir' }}
                                                        </span>
                                                    </li>
                                                @else
                                                    <li>
                                                        <button type="button" class="dropdown-item {{ $account->is_active ? 'text-danger' : 'text-success' }}"
                                                                data-confirm-action
                                                                data-confirm-title="{{ $account->is_active ? 'Nonaktifkan akun?' : 'Aktifkan akun?' }}"
                                                                data-confirm-message="{{ $account->is_active
                                                                    ? 'Akun '.$account->name.' akan dinonaktifkan. Pengguna tidak dapat masuk dan sesi aktifnya akan ditolak pada permintaan berikutnya.'
                                                                    : 'Akun '.$account->name.' akan diaktifkan kembali dan dapat digunakan untuk masuk ke sistem.' }}"
                                                                data-confirm-label="{{ $account->is_active ? 'Nonaktifkan akun' : 'Aktifkan akun' }}"
                                                                data-confirm-form="#toggleAccount{{ $account->id }}">
                                                            <i class="{{ $account->is_active ? 'ri-user-forbid-line' : 'ri-user-follow-line' }}" aria-hidden="true"></i>
                                                            {{ $account->is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}
                                                        </button>
                                                    </li>
                                                @endif

                                                <li>
                                                    @if ($isProtected)
                                                        <span class="dropdown-item-text text-muted-soft fs-10 d-flex align-items-center gap-2">
                                                            <i class="ri-shield-check-line" aria-hidden="true"></i>
                                                            Akun ini dilindungi dari penghapusan
                                                        </span>
                                                    @else
                                                        <button type="button" class="dropdown-item text-danger"
                                                                data-confirm-action
                                                                data-confirm-title="Hapus akun ini?"
                                                                data-confirm-message="Akun {{ $account->name }} akan dihapus (soft delete). Arsip, laporan, dan riwayat aktivitas yang terkait tetap tersimpan."
                                                                data-confirm-label="Hapus akun"
                                                                data-confirm-form="#deleteAccount{{ $account->id }}">
                                                            <i class="ri-delete-bin-6-line" aria-hidden="true"></i>
                                                            Hapus Akun
                                                        </button>
                                                    @endif
                                                </li>
                                            </ul>
                                        </div>
                                    </div>

                                    <form id="toggleAccount{{ $account->id }}" method="POST"
                                          action="{{ route('akun.toggle-status', $account) }}" class="d-none">
                                        @csrf
                                        @method('PATCH')
                                    </form>

                                    <form id="deleteAccount{{ $account->id }}" method="POST"
                                          action="{{ route('akun.destroy', $account) }}" class="d-none">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <p class="table-footer-note">
                    Menampilkan <strong>{{ $accounts->firstItem() }}–{{ $accounts->lastItem() }}</strong>
                    dari <strong>{{ number_format($filteredTotal, 0, ',', '.') }}</strong> akun
                </p>
                <x-admin.pagination :paginator="$accounts" />
            </div>
        @endif
    </div>

    {{-- Modal reset kata sandi --}}
    <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordTitle" aria-hidden="true"
         data-reset-open="{{ $errors->resetPassword->any() ? '1' : '0' }}"
         data-reset-account-id="{{ $resetErrorAccountId }}"
         data-reset-account-name="{{ $resetErrorAccountName }}">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('akun.reset-password') }}">
                    @csrf
                    <input type="hidden" name="user_id" id="resetPasswordUserId" value="{{ $resetErrorAccountId }}">

                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="resetPasswordTitle">Reset Kata Sandi</h5>
                            <p class="app-card-subtitle mb-0">
                                Kata sandi baru untuk <strong data-reset-account-name>{{ $resetErrorAccountName ?: 'akun terpilih' }}</strong>
                            </p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body">
                        @if ($errors->resetPassword->any())
                            <div class="alert alert-danger app-alert" role="alert">
                                <i class="ri-error-warning-line" aria-hidden="true"></i>
                                <div>
                                    <strong>Reset kata sandi belum dapat diproses.</strong>
                                    <ul class="mb-0 mt-1 ps-3">
                                        @foreach ($errors->resetPassword->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        <div class="demo-note">
                            <i class="ri-shield-keyhole-line" aria-hidden="true"></i>
                            <div>
                                Kata sandi baru tidak akan ditampilkan kembali. Pengguna wajib mengganti kata sandi ini
                                saat login berikutnya, dan sesi lama akan dicabut.
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="resetPassword">Kata Sandi Baru <span class="req">*</span></label>
                                <input type="password" id="resetPassword" name="password"
                                       class="form-control @if ($errors->resetPassword->has('password')) is-invalid @endif"
                                       required minlength="8" autocomplete="new-password">
                                @if ($errors->resetPassword->has('password'))
                                    <div class="invalid-feedback">{{ $errors->resetPassword->first('password') }}</div>
                                @endif
                                <div class="form-text">Minimal 8 karakter. Gunakan kombinasi huruf, angka, dan simbol.</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="resetPasswordConfirmation">Ulangi Kata Sandi Baru <span class="req">*</span></label>
                                <input type="password" id="resetPasswordConfirmation" name="password_confirmation"
                                       class="form-control" required minlength="8" autocomplete="new-password">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-soft" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-emerald">
                            <i class="ri-lock-password-line" aria-hidden="true"></i>
                            Simpan Kata Sandi Baru
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
