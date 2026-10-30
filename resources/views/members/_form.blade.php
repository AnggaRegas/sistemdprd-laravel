@php
    $isEdit = $member->exists;
    $selectedStatus = (string) old('is_active', $isEdit ? ($member->is_active ? '1' : '0') : '1');
    $selectedKecamatan = old('kecamatan', $member->kecamatan ?? '');
    $selectedDapil = old('dapil', $member->dapil ?? '');
    $selectedKomisi = old('komisi', $member->komisi ?? '');
    $selectedRegion = (string) old('dprd_region_id', $member->dprd_region_id ?? '');
    $selectedRegionName = optional($regions->firstWhere('id', (int) $selectedRegion))->name;
@endphp

<form method="POST" action="{{ $isEdit ? route('anggota.update', $member) : route('anggota.store') }}">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="app-card mb-3">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Identitas Anggota Dewan</h2>
                        <p class="app-card-subtitle">Data resmi yang tampil pada daftar anggota dan halaman arsip.</p>
                    </div>
                    <span class="status-badge {{ $selectedStatus === '1' ? 'status-success' : 'status-danger' }}">
                        {{ $selectedStatus === '1' ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>

                <div class="app-card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="kode">Kode Anggota <span class="req">*</span></label>
                            <input type="text" id="kode" name="kode"
                                   value="{{ old('kode', $member->kode ?? '') }}"
                                   class="form-control @error('kode') is-invalid @enderror"
                                   required maxlength="20" placeholder="DWN-26"
                                   autocomplete="off" autocapitalize="characters" autofocus>
                            @error('kode')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Kode unik, contoh DWN-01 sampai DWN-25.</div>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label" for="nama_lengkap">Nama Lengkap &amp; Gelar <span class="req">*</span></label>
                            <input type="text" id="nama_lengkap" name="nama_lengkap"
                                   value="{{ old('nama_lengkap', $member->nama_lengkap ?? '') }}"
                                   class="form-control @error('nama_lengkap') is-invalid @enderror"
                                   required maxlength="150" placeholder="Contoh: H. Andi Mappatoba, S.E."
                                   autocomplete="name">
                            @error('nama_lengkap')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="jabatan">Jabatan <span class="req">*</span></label>
                            <input type="text" id="jabatan" name="jabatan"
                                   value="{{ old('jabatan', $member->jabatan ?? '') }}"
                                   class="form-control @error('jabatan') is-invalid @enderror"
                                   required maxlength="100" placeholder="Contoh: Ketua Komisi I">
                            @error('jabatan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="partai">Partai Politik / Fraksi <span class="req">*</span></label>
                            <input type="text" id="partai" name="partai"
                                   value="{{ old('partai', $member->partai ?? '') }}"
                                   class="form-control @error('partai') is-invalid @enderror"
                                   required maxlength="100" placeholder="Contoh: Partai Golongan Karya"
                                   list="partaiOptions" autocomplete="off">
                            <datalist id="partaiOptions">
                                @foreach ($parties as $party)
                                    <option value="{{ $party }}"></option>
                                @endforeach
                            </datalist>
                            @error('partai')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="dapil">Dapil</label>
                            <input type="text" id="dapil" name="dapil"
                                   value="{{ $selectedDapil }}"
                                   class="form-control @error('dapil') is-invalid @enderror"
                                   maxlength="100" placeholder="Contoh: Dapil Parepare 1">
                            @error('dapil')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="komisi">Komisi</label>
                            <input type="text" id="komisi" name="komisi"
                                   value="{{ $selectedKomisi }}"
                                   class="form-control @error('komisi') is-invalid @enderror"
                                   maxlength="100" placeholder="Contoh: Komisi 1">
                            @error('komisi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="is_active">Status Anggota <span class="req">*</span></label>
                            <select id="is_active" name="is_active"
                                    class="form-select @error('is_active') is-invalid @enderror" required>
                                <option value="1" @selected($selectedStatus === '1')>Aktif</option>
                                <option value="0" @selected($selectedStatus === '0')>Nonaktif</option>
                            </select>
                            @error('is_active')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="kecamatan">Kecamatan Asal</label>
                            <input type="text" id="kecamatan" name="kecamatan"
                                   value="{{ $selectedKecamatan }}"
                                   class="form-control @error('kecamatan') is-invalid @enderror"
                                   maxlength="100" placeholder="Contoh: Kecamatan Bacukiki"
                                   list="kecamatanOptions" autocomplete="off">
                            <datalist id="kecamatanOptions">
                                @foreach ($kecamatanOptions as $kecamatan)
                                    <option value="{{ $kecamatan }}"></option>
                                @endforeach
                            </datalist>
                            @error('kecamatan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Kecamatan asal anggota, terpisah dari wilayah aspirasi.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="dprd_region_id">Wilayah Aspirasi</label>
                            <select id="dprd_region_id" name="dprd_region_id"
                                    class="form-select @error('dprd_region_id') is-invalid @enderror">
                                <option value="">Belum dipetakan</option>
                                @foreach ($regions as $region)
                                    <option value="{{ $region->id }}" @selected($selectedRegion === (string) $region->id)>
                                        {{ $region->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('dprd_region_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Wilayah inilah yang menghubungkan anggota dengan pilihan masyarakat pada form aspirasi.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-card">
                <div class="app-card-body">
                    <div class="demo-note mb-0">
                        <i class="ri-shield-user-line" aria-hidden="true"></i>
                        <div>
                            <strong>Staf pendamping tidak diisi dari form ini.</strong>
                            Penugasan staf dilakukan melalui fitur
                            <a href="{{ route('akun.index') }}">Kelola Akun Staf</a>
                            agar satu anggota hanya memiliki satu akun staf aktif dan nama staf selalu berasal dari relasi database.
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
                        <p class="app-card-subtitle">Pratinjau data sebelum disimpan.</p>
                    </div>
                </div>

                <div class="app-card-body">
                    <dl class="detail-list">
                        <div class="detail-row">
                            <dt>Kode</dt>
                            <dd>{{ old('kode', $member->kode ?? 'Belum diisi') }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Nama</dt>
                            <dd>{{ old('nama_lengkap', $member->nama_lengkap ?? 'Belum diisi') }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Jabatan</dt>
                            <dd>{{ old('jabatan', $member->jabatan ?? 'Belum diisi') }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Partai / Fraksi</dt>
                            <dd>{{ old('partai', $member->partai ?? 'Belum diisi') }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Dapil</dt>
                            <dd>{{ $selectedDapil ?: 'Belum diatur' }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Komisi</dt>
                            <dd>{{ $selectedKomisi ?: 'Belum diatur' }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Kecamatan asal</dt>
                            <dd>{{ $selectedKecamatan ?: 'Belum diatur' }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Wilayah aspirasi</dt>
                            <dd>{{ $selectedRegionName ?: 'Belum dipetakan' }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Staf pendamping</dt>
                            <dd>{{ $isEdit ? ($member->staff?->name ?? 'Belum ditugaskan') : 'Belum ditugaskan' }}</dd>
                        </div>
                        <div class="detail-row">
                            <dt>Status</dt>
                            <dd>{{ $selectedStatus === '1' ? 'Aktif' : 'Nonaktif' }}</dd>
                        </div>
                    </dl>

                    <div class="demo-note mt-3 mb-0">
                        <i class="ri-information-line" aria-hidden="true"></i>
                        <div>
                            Setelah anggota disimpan, Anda dapat membuka halaman arsip anggota untuk menambahkan dokumen terkait.
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-card">
                <div class="app-card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-gold">
                            <i class="ri-save-3-line" aria-hidden="true"></i>
                            {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Anggota' }}
                        </button>
                        <a href="{{ $isEdit ? route('anggota.arsip', $member) : route('anggota.index') }}"
                           class="btn btn-outline-soft">
                            <i class="ri-archive-2-line" aria-hidden="true"></i>
                            {{ $isEdit ? 'Buka Arsip Anggota' : 'Kembali ke Daftar' }}
                        </a>
                        <a href="{{ route('anggota.index') }}" class="btn btn-outline-soft">
                            <i class="ri-arrow-left-line" aria-hidden="true"></i>
                            Kembali ke Daftar Anggota
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
