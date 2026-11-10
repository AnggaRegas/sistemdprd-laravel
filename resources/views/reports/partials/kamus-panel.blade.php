@php
    $autoHasil = $report->kamusHasil;
    $manualTerakhir = $report->kamusVerifikasi->first();
    $terdaftar = $report->kamus_status === \App\Models\LaporanKamusHasil::STATUS_SESUAI;
    $sudahDiperiksa = $autoHasil !== null || $report->kamus_status !== null;
@endphp

<div class="app-card mb-3" id="kamus-acuan">
    <div class="app-card-header">
        <div>
            <h2 class="app-card-title">Kamus Acuan</h2>
            <p class="app-card-subtitle">Status pendaftaran aspirasi pada Kamus Acuan yang berlaku.</p>
        </div>
    </div>

    <div class="app-card-body">
        @if ($errors->any())
            <div class="alert alert-danger app-alert mb-3" role="alert">
                <i class="ri-error-warning-line" aria-hidden="true"></i>
                <div>
                    <strong>Periksa kembali isian Kamus Acuan.</strong>
                    <ul class="mb-0 mt-1 ps-3">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @if (! $kamusVersion)
            <div class="alert alert-warning app-alert mb-3" role="alert">
                <i class="ri-alert-line" aria-hidden="true"></i>
                <div>Belum ada versi Kamus Acuan aktif. Pemeriksaan otomatis tidak dapat dijalankan sampai Super Admin menetapkan versi.</div>
            </div>
        @endif

        <div class="kamus-status-box {{ $terdaftar ? 'is-registered' : 'is-unregistered' }}">
            <span class="kamus-status-icon" aria-hidden="true">
                <i class="{{ $terdaftar ? 'ri-shield-check-line' : 'ri-shield-cross-line' }}"></i>
            </span>
            <div class="kamus-status-body">
                <span class="kamus-status-badge">
                    {{ $terdaftar ? 'Terdaftar di Kamus Acuan' : 'Tidak Terdaftar di Kamus Acuan' }}
                </span>
                <p class="kamus-status-meta">
                    @if (! $sudahDiperiksa)
                        Aspirasi ini belum diperiksa terhadap Kamus Acuan.
                    @elseif ($report->kamus_diperiksa_at || $report->kamus_diverifikasi_at)
                        Terakhir diperbarui
                        {{ \App\Support\PortalData::dateTime($report->kamus_diverifikasi_at ?? $report->kamus_diperiksa_at) }}
                        @if ($report->kamus_tahun)
                            · Kamus {{ $report->kamus_tahun }}
                        @endif
                        @if ($terdaftar && $report->kamus_entri_id)
                            · {{ $report->kamus_entri_label }}
                        @endif
                    @else
                        Hasil pemeriksaan Kamus Acuan sudah tersimpan.
                    @endif
                </p>
            </div>
        </div>

        @if ($isAdministrator)
            <div class="soft-divider"></div>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div>
                    <h3 class="app-card-title mb-1">Tindakan Super Admin</h3>
                    <p class="cell-muted mb-0" style="font-size: 11px;">Pemeriksaan ulang tidak menimpa keputusan manual yang sudah tersimpan.</p>
                </div>

                <form method="POST" action="{{ route('kamus.laporan.periksa', $report) }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-soft btn-sm" @disabled(! $kamusVersion)>
                        <i class="ri-refresh-line" aria-hidden="true"></i>
                        Periksa Ulang Otomatis
                    </button>
                </form>
            </div>

            <form method="POST" action="{{ route('kamus.laporan.verifikasi', $report) }}" class="row g-3">
                @csrf

                <div class="col-md-4">
                    <label class="form-label" for="kamusKeputusan">Keputusan</label>
                    <select id="kamusKeputusan" name="keputusan" class="form-select @error('keputusan') is-invalid @enderror" required>
                        @foreach ($kamusVerificationLabels as $value => $label)
                            <option value="{{ $value }}" @selected(old('keputusan', $manualTerakhir?->keputusan ?? \App\Models\LaporanKamusVerifikasi::KEPUTUSAN_PERLU_VERIFIKASI) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('keputusan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-8">
                    <label class="form-label" for="kamusEntri">Entri Kamus Acuan</label>
                    <select id="kamusEntri" name="entri_id" class="form-select @error('entri_id') is-invalid @enderror">
                        <option value="">— Pilih entri (wajib untuk keputusan sesuai kamus) —</option>
                        @foreach ($kamusEntries as $entry)
                            <option value="{{ $entry->id }}" @selected((string) old('entri_id', $report->kamus_entri_id) === (string) $entry->id)>
                                {{ $entry->kode }} — {{ $entry->klasifikasi }}@if ($kamusVersion && (int) $entry->kamus_acuan_versi_id !== (int) $kamusVersion->id) (versi lama)@endif
                            </option>
                        @endforeach
                    </select>
                    @error('entri_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Daftar ini berasal dari versi kamus yang aktif. Entri lama tetap tersimpan pada riwayat.</div>
                </div>

                <div class="col-12">
                    <label class="form-label" for="kamusCatatan">Catatan Verifikasi</label>
                    <textarea id="kamusCatatan" name="catatan" rows="3" maxlength="2000"
                              class="form-control @error('catatan') is-invalid @enderror"
                              placeholder="Alasan keputusan manual ini (opsional).">{{ old('catatan', $report->kamus_catatan_verifikasi) }}</textarea>
                    @error('catatan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-emerald">
                        <i class="ri-check-double-line" aria-hidden="true"></i>
                        Simpan Keputusan Manual
                    </button>
                </div>
            </form>
        @else
            <div class="demo-note mt-3 mb-0">
                <i class="ri-lock-2-line" aria-hidden="true"></i>
                <div>Hasil Kamus Acuan hanya dapat diverifikasi oleh Super Admin.</div>
            </div>
        @endif
    </div>
</div>

@push('styles')
    <style>
        .kamus-status-box { display: flex; align-items: flex-start; gap: 14px; padding: 18px; border-radius: 18px; border: 1px solid #e5e9e6; }
        .kamus-status-box.is-registered { border-color: #bfe3cc; background: #f1faf4; }
        .kamus-status-box.is-unregistered { border-color: #e4e0d8; background: #f7f6f3; }
        .kamus-status-icon { display: inline-flex; align-items: center; justify-content: center; width: 46px; height: 46px; border-radius: 14px; font-size: 22px; flex-shrink: 0; }
        .kamus-status-box.is-registered .kamus-status-icon { background: #dcf3e4; color: #14663f; }
        .kamus-status-box.is-unregistered .kamus-status-icon { background: #ece8e1; color: #8a5b5b; }
        .kamus-status-body { min-width: 0; }
        .kamus-status-badge { display: inline-flex; align-items: center; border-radius: 999px; padding: 5px 12px; font-size: 12px; font-weight: 800; letter-spacing: .01em; }
        .kamus-status-box.is-registered .kamus-status-badge { background: #14663f; color: #ffffff; }
        .kamus-status-box.is-unregistered .kamus-status-badge { background: #b45b5b; color: #ffffff; }
        .kamus-status-meta { margin: 8px 0 0; font-size: 11.5px; line-height: 1.7; color: #6b7873; }
    </style>
@endpush
