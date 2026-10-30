@php
    $isEdit = isset($account) && $account->exists;
    $isAdministrator = $isEdit && $account->isAdministrator();
    $requiresMember = ! $isAdministrator;
    $selectedMember = old('dprd_member_id', $account->dprd_member_id ?? '');
    $selectedStatus = (string) old('is_active', isset($account) ? ($account->is_active ? '1' : '0') : '1');
    $canDeactivate = $canDeactivate ?? true;
@endphp

<form method="POST" action="{{ $isEdit ? route('akun.update', $account) : route('akun.store') }}">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="app-card mb-3">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Informasi Akun</h2>
                        <p class="app-card-subtitle">Identitas masuk dan alamat email yang digunakan staf.</p>
                    </div>
                    <span class="status-badge {{ $isAdministrator ? 'status-gold' : 'status-info' }}">
                        {{ $isAdministrator ? 'Administrator' : 'Staf Pendamping' }}
                    </span>
                </div>

                <div class="app-card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Nama Lengkap <span class="req">*</span></label>
                            <input type="text" id="name" name="name"
                                   value="{{ old('name', $account->name ?? '') }}"
                                   class="form-control @error('name') is-invalid @enderror"
                                   required maxlength="255" autocomplete="name" autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="username">Username <span class="req">*</span></label>
                            <input type="text" id="username" name="username"
                                   value="{{ old('username', $account->username ?? '') }}"
                                   class="form-control @error('username') is-invalid @enderror"
                                   required maxlength="50" autocomplete="username"
                                   pattern="[A-Za-z0-9._-]+">
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Hanya huruf, angka, titik, garis bawah, dan tanda hubung.</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="email">Alamat Email <span class="req">*</span></label>
                            <input type="email" id="email" name="email"
                                   value="{{ old('email', $account->email ?? '') }}"
                                   class="form-control @error('email') is-invalid @enderror"
                                   required maxlength="255" autocomplete="email">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @unless ($isEdit)
                            <div class="col-md-6">
                                <label class="form-label" for="password">Kata Sandi Awal <span class="req">*</span></label>
                                <input type="password" id="password" name="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       required minlength="8" autocomplete="new-password">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Minimal 8 karakter. Pengguna wajib menggantinya saat login pertama.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="password_confirmation">Ulangi Kata Sandi Awal <span class="req">*</span></label>
                                <input type="password" id="password_confirmation" name="password_confirmation"
                                       class="form-control" required minlength="8" autocomplete="new-password">
                            </div>
                        @endunless
                    </div>
                </div>
            </div>

            <div class="app-card">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Penugasan &amp; Status</h2>
                        <p class="app-card-subtitle">
                            {{ $isAdministrator
                                ? 'Akun administrator tidak ditugaskan ke anggota DPRD.'
                                : 'Satu anggota DPRD hanya boleh memiliki satu staf pendamping aktif.' }}
                        </p>
                    </div>
                </div>

                <div class="app-card-body">
                    @if ($requiresMember && $members->isEmpty())
                        <div class="alert alert-warning app-alert" role="alert">
                            <i class="ri-alert-line" aria-hidden="true"></i>
                            <div>
                                <strong>Tidak ada anggota DPRD yang tersedia.</strong>
                                Semua anggota aktif sudah memiliki staf pendamping aktif. Nonaktifkan staf lama
                                terlebih dahulu melalui daftar akun sebelum membuat akun baru.
                            </div>
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-7">
                            @if ($requiresMember)
                                <label class="form-label" for="dprd_member_id">Anggota DPRD <span class="req">*</span></label>
                                <select id="dprd_member_id" name="dprd_member_id"
                                        class="form-select @error('dprd_member_id') is-invalid @enderror"
                                        required @if ($members->isEmpty()) disabled @endif>
                                    <option value="">Pilih anggota DPRD</option>
                                    @foreach ($members as $member)
                                        <option value="{{ $member->id }}" @selected((string) $selectedMember === (string) $member->id)>
                                            {{ $member->nama_lengkap }}
                                            @if ($member->jabatan)
                                                — {{ $member->jabatan }}
                                            @endif
                                            @if ($member->komisi)
                                                ({{ $member->komisi }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('dprd_member_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Hanya anggota aktif yang belum memiliki staf aktif yang ditampilkan.</div>
                            @else
                                <label class="form-label">Penugasan Dewan</label>
                                <div class="form-control bg-light d-flex align-items-center gap-2" aria-readonly="true">
                                    <i class="ri-shield-user-line text-gold" aria-hidden="true"></i>
                                    <span>Tidak ada — akun administrator tidak terikat anggota DPRD.</span>
                                </div>
                            @endif
                        </div>

                        <div class="col-md-5">
                            <label class="form-label" for="is_active">Status Akun <span class="req">*</span></label>
                            <select id="is_active" name="is_active"
                                    class="form-select @error('is_active') is-invalid @enderror" required>
                                <option value="1" @selected($selectedStatus === '1')>Aktif — dapat masuk ke sistem</option>
                                <option value="0" @selected($selectedStatus === '0') @disabled($isEdit && ! $canDeactivate)>
                                    Nonaktif — tidak dapat masuk
                                </option>
                            </select>
                            @error('is_active')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            @if ($isEdit && ! $canDeactivate)
                                <div class="form-text text-danger">
                                    Akun ini tidak dapat dinonaktifkan karena sedang digunakan atau merupakan administrator aktif terakhir.
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="demo-note mt-3 mb-0">
                        <i class="ri-shield-check-line" aria-hidden="true"></i>
                        <div>
                            @if ($isAdministrator)
                                Role akun tetap <strong>Administrator</strong> dan tidak dapat diubah dari formulir ini.
                            @else
                                Role akun selalu <strong>Staf Pendamping</strong> dan tidak dapat diubah dari formulir.
                                Perubahan role hanya dilakukan melalui mekanisme administrator yang tervalidasi.
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="app-card mb-3">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Ringkasan</h2>
                        <p class="app-card-subtitle">Ketentuan akun staf internal.</p>
                    </div>
                </div>

                <div class="app-card-body">
                    <dl class="detail-list">
                        <div class="detail-row">
                            <dt>Peran</dt>
                            <dd>{{ $isAdministrator ? 'Administrator' : 'Staf Pendamping' }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Status</dt>
                            <dd>{{ $selectedStatus === '1' ? 'Aktif' : 'Nonaktif' }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Penugasan</dt>
                            <dd>
                                @if ($isAdministrator)
                                    Tidak ada (administrator)
                                @else
                                    {{ $members->firstWhere('id', (int) $selectedMember)?->nama_lengkap ?? 'Belum dipilih' }}
                                @endif
                            </dd>
                        </div>
                        @if ($isEdit)
                            <div class="detail-row">
                                <dt>Terdaftar</dt>
                                <dd>{{ $account->created_at?->locale('id')->translatedFormat('d M Y') ?? '—' }}</dd>
                            </div>
                        @endif
                    </dl>

                    <div class="demo-note mt-3 mb-0">
                        <i class="ri-information-line" aria-hidden="true"></i>
                        <div>
                            @if ($isEdit)
                                Kata sandi tidak diubah melalui formulir ini. Gunakan tombol
                                <strong>Reset Kata Sandi</strong> pada daftar akun.
                            @else
                                Kata sandi awal wajib diganti pengguna saat login pertama. Sistem tidak menampilkan
                                atau menyimpan kata sandi dalam bentuk teks biasa.
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-card">
                <div class="app-card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-emerald" @if ($requiresMember && $members->isEmpty()) disabled @endif>
                            <i class="ri-save-3-line" aria-hidden="true"></i>
                            {{ $isEdit ? 'Simpan Perubahan' : 'Buat Akun Staf' }}
                        </button>
                        <a href="{{ route('akun.index') }}" class="btn btn-outline-soft">
                            <i class="ri-arrow-left-line" aria-hidden="true"></i>
                            Kembali ke Daftar Akun
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
