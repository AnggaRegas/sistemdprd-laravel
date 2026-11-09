@php
    $autoHasil = $report->kamusHasil;
    $manualTerakhir = $report->kamusVerifikasi->first();
    $kandidat = $autoHasil?->kandidat_urut ?? [];
@endphp

<div class="app-card mb-3" id="kamus-acuan">
    <div class="app-card-header">
        <div>
            <h2 class="app-card-title">Hasil Pemeriksaan Kamus Acuan</h2>
            <p class="app-card-subtitle">Hasil otomatis dan keputusan manual Super Admin disimpan terpisah agar riwayat tetap utuh.</p>
        </div>
        <span class="status-badge status-{{ $report->kamus_status_tone }}">{{ $report->kamus_status_label }}</span>
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

        <div class="row g-3">
            <div class="col-md-6">
                <dl class="detail-list">
                    <div class="detail-row">
                        <dt>Status akhir</dt>
                        <dd><span class="status-badge status-{{ $report->kamus_status_tone }}">{{ $report->kamus_status_label }}</span></dd>
                    </div>
                    <div class="detail-row">
                        <dt>Hasil otomatis terakhir</dt>
                        <dd>{{ $report->kamus_status_otomatis_label }}</dd>
                    </div>
                    <div class="detail-row">
                        <dt>Tahun kamus</dt>
                        <dd>{{ $report->kamus_tahun_label }}</dd>
                    </div>
                    <div class="detail-row">
                        <dt>Entri acuan</dt>
                        <dd>{{ $report->kamus_entri_label }}</dd>
                    </div>
                    <div class="detail-row">
                        <dt>Prioritas rekomendasi</dt>
                        <dd>{{ $report->kamus_prioritas_label }}</dd>
                    </div>
                </dl>
            </div>

            <div class="col-md-6">
                <dl class="detail-list">
                    <div class="detail-row">
                        <dt>Diperiksa otomatis</dt>
                        <dd>{{ $report->kamus_diperiksa_at ? \App\Support\PortalData::dateTime($report->kamus_diperiksa_at) : '—' }}</dd>
                    </div>
                    <div class="detail-row">
                        <dt>Diverifikasi manual</dt>
                        <dd>
                            @if ($report->kamus_diverifikasi_at)
                                {{ \App\Support\PortalData::dateTime($report->kamus_diverifikasi_at) }}
                                <span class="cell-sub d-block">{{ $report->kamusDiverifikasiOleh?->name ?: 'Super Admin' }}</span>
                            @else
                                Belum ada keputusan manual
                            @endif
                        </dd>
                    </div>
                    <div class="detail-row">
                        <dt>Catatan verifikasi</dt>
                        <dd class="text-break">{{ $report->kamus_catatan_verifikasi ?: '—' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        @if ($autoHasil)
            <div class="soft-divider"></div>
            <h3 class="app-card-title mb-2">Mengapa hasil ini muncul?</h3>
            <p style="font-size: 12px; line-height: 1.8;">{{ $autoHasil->alasan ?: 'Tidak ada penjelasan tersimpan.' }}</p>

            <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="status-badge status-neutral">Metode: {{ $autoHasil->metode_label }}</span>
                <span class="status-badge status-info">{{ $autoHasil->jumlah_kandidat }} kandidat</span>
                <span class="status-badge status-{{ $autoHasil->status_tone }}">{{ $autoHasil->status_label }}</span>
            </div>

            @if ($kandidat === [])
                <p class="cell-muted mb-0">Tidak ada kandidat entri yang cocok pada pemeriksaan terakhir.</p>
            @else
                <div class="table-responsive">
                    <table class="table-modern mb-0" style="min-width: 680px;">
                        <thead>
                            <tr>
                                <th scope="col">Kode</th>
                                <th scope="col">Klasifikasi</th>
                                <th scope="col">Komisi</th>
                                <th scope="col">Skor</th>
                                <th scope="col">Istilah cocok</th>
                                <th scope="col">Prioritas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($kandidat as $candidate)
                                <tr>
                                    <td><strong>{{ $candidate['kode'] ?? '—' }}</strong></td>
                                    <td>{{ $candidate['klasifikasi'] ?? '—' }}</td>
                                    <td><span class="cell-muted">{{ $candidate['komisi'] ?? '—' }}</span></td>
                                    <td><span class="status-badge status-info">{{ $candidate['skor'] ?? 0 }}</span></td>
                                    <td>
                                        @foreach (array_slice($candidate['matched_terms'] ?? [], 0, 6) as $term)
                                            <span class="cell-sub d-block">
                                                {{ $term['term'] ?? '—' }}
                                                <span class="cell-muted">· {{ $term['sumber'] ?? 'Kata kunci' }} · bobot {{ $term['weight'] ?? 0 }} · {{ $term['occurrences'] ?? 0 }}x</span>
                                            </span>
                                        @endforeach
                                    </td>
                                    <td>{{ $candidate['prioritas'] ?: 'Belum ditetapkan' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @else
            <div class="demo-note mt-3 mb-0">
                <i class="ri-information-line" aria-hidden="true"></i>
                <div>Laporan ini belum memiliki hasil pemeriksaan Kamus Acuan. Pemeriksaan otomatis berjalan saat aspirasi masuk; Super Admin juga dapat menjalankannya ulang dari panel ini.</div>
            </div>
        @endif

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
                <div>Hasil Kamus Acuan hanya dapat diverifikasi oleh Super Admin. Riwayat tetap dapat Anda lihat di panel ini.</div>
            </div>
        @endif

        <div class="soft-divider"></div>

        <h3 class="app-card-title mb-2">Riwayat Pemeriksaan Otomatis</h3>
        @if ($report->kamusHasilList->isEmpty())
            <p class="cell-muted mb-0">Belum ada pemeriksaan otomatis yang tersimpan.</p>
        @else
            <ul class="timeline mb-0">
                @foreach ($report->kamusHasilList as $hasil)
                    <li class="timeline-item {{ $loop->first ? 'is-active' : 'is-done' }}">
                        <strong>{{ $hasil->status_label }}</strong>
                        <p>
                            Kamus {{ $hasil->tahun_label }} · {{ $hasil->jumlah_kandidat }} kandidat
                            · {{ \App\Support\PortalData::dateTime($hasil->diperiksa_at) }}
                        </p>
                        <span>{{ $hasil->alasan ?: 'Tidak ada penjelasan tersimpan.' }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="soft-divider"></div>

        <h3 class="app-card-title mb-2">Riwayat Verifikasi Manual</h3>
        @if ($report->kamusVerifikasi->isEmpty())
            <p class="cell-muted mb-0">Belum ada keputusan manual dari Super Admin.</p>
        @else
            <ul class="timeline mb-0">
                @foreach ($report->kamusVerifikasi as $verifikasi)
                    <li class="timeline-item is-done">
                        <strong>{{ $verifikasi->keputusan_label }}</strong>
                        <p>
                            {{ $verifikasi->status_sebelum ? (\App\Models\LaporanKamusHasil::STATUS_LABELS[$verifikasi->status_sebelum] ?? $verifikasi->status_sebelum) : 'BELUM DIPERIKSA' }}
                            → {{ $verifikasi->status_sesudah_label }}
                            · {{ \App\Support\PortalData::dateTime($verifikasi->diverifikasi_at) }}
                            @if ($verifikasi->verifikator)
                                oleh {{ $verifikasi->verifikator->name }}
                            @endif
                        </p>
                        @if ($verifikasi->entri_snapshot)
                            <span>Entri: {{ $verifikasi->entri_snapshot['kode'] ?? '—' }} — {{ $verifikasi->entri_snapshot['klasifikasi'] ?? '—' }}</span>
                        @endif
                        @if ($verifikasi->catatan)
                            <p class="mt-1">{{ $verifikasi->catatan }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
