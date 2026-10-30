@extends('layouts.admin', [
    'role' => auth()->user()->isStaff() ? 'staff' : 'admin',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route(auth()->user()->dashboardRouteName())],
        ['label' => 'Laporan Masyarakat', 'url' => $returnUrl],
        ['label' => $report->nomor_tiket],
    ],
])

@section('title', 'Detail Laporan '.$report->nomor_tiket)

@section('content')
    <x-admin.page-header
        eyebrow="Detail laporan masyarakat"
        :title="$report->ringkasan"
        :description="'Tiket '.$report->nomor_tiket.' · '.$report->kecamatan_label.' · '.$report->komisi_efektif_label"
    >
        <a href="{{ $returnUrl }}" class="btn btn-outline-soft">
            <i class="ri-arrow-left-line" aria-hidden="true"></i>
            Kembali ke Daftar Laporan
        </a>

        @if ($isAdministrator)
            <a href="#penugasan" class="btn btn-gold">
                <i class="ri-user-add-line" aria-hidden="true"></i>
                Atur Penugasan
            </a>
        @endif
    </x-admin.page-header>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="app-card mb-3">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Informasi Laporan</h2>
                        <p class="app-card-subtitle">Data pelapor, klasifikasi, dan status penanganan.</p>
                    </div>
                    <x-admin.status-badge :status="$report->status_label" />
                </div>

                <div class="app-card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <dl class="detail-list">
                                <div class="detail-row">
                                    <dt>Nomor tiket</dt>
                                    <dd>{{ $report->nomor_tiket }}</dd>
                                </div>
                                <div class="detail-row">
                                    <dt>Tanggal laporan</dt>
                                    <dd>{{ \App\Support\PortalData::date($report->tanggal_laporan?->toDateString(), true) }}</dd>
                                </div>
                                <div class="detail-row">
                                    <dt>Tanggal masuk</dt>
                                    <dd>{{ \App\Support\PortalData::dateTime($report->created_at) }}</dd>
                                </div>
                                <div class="detail-row">
                                    <dt>Kecamatan</dt>
                                    <dd>{{ $report->kecamatan_label }}</dd>
                                </div>
                                <div class="detail-row">
                                    <dt>Komisi rekomendasi</dt>
                                    <dd>{{ $report->klasifikasi_komisi_label }}</dd>
                                </div>
                                <div class="detail-row">
                                    <dt>Komisi final</dt>
                                    <dd>{{ $report->komisi_final_label }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="col-md-6">
                            <dl class="detail-list">
                                <div class="detail-row">
                                    <dt>Pelapor</dt>
                                    <dd>{{ $report->pelapor_nama }}</dd>
                                </div>
                                <div class="detail-row">
                                    <dt>Kontak</dt>
                                    <dd>{{ $report->pelapor_kontak ?: '—' }}</dd>
                                </div>
                                <div class="detail-row">
                                    <dt>Alamat</dt>
                                    <dd>{{ $report->pelapor_alamat ?: '—' }}</dd>
                                </div>
                                <div class="detail-row">
                                    <dt>Anggota DPRD</dt>
                                    <dd>{{ $report->member?->nama_lengkap ?: 'Belum ditentukan' }}</dd>
                                </div>
                                <div class="detail-row">
                                    <dt>Staf pendamping</dt>
                                    <dd>{{ $report->assignee?->name ?: 'Belum ditugaskan' }}</dd>
                                </div>
                                <div class="detail-row">
                                    <dt>Ditutup pada</dt>
                                    <dd>{{ $report->ditutup_at ? \App\Support\PortalData::dateTime($report->ditutup_at) : '—' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-card mb-3" id="klasifikasi">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Klasifikasi Komisi</h2>
                        <p class="app-card-subtitle">Rekomendasi otomatis berbasis aturan kata kunci berbobot, bukan AI atau machine learning.</p>
                    </div>
                    <span class="status-badge {{ $report->klasifikasi_status === \App\Models\LaporanMasyarakat::KLASIFIKASI_DIREKOMENDASIKAN ? 'status-success' : 'status-warning' }}">
                        {{ $report->klasifikasi_status_label }}
                    </span>
                </div>

                <div class="app-card-body">
                    @if ($report->hasClassification())
                        <dl class="detail-list">
                            <div class="detail-row">
                                <dt>Rekomendasi</dt>
                                <dd>{{ $report->klasifikasi_komisi_label }}</dd>
                            </div>
                            <div class="detail-row">
                                <dt>Skor</dt>
                                <dd>
                                    {{ number_format((float) $report->klasifikasi_skor, 2, ',', '.') }}
                                    @if ($report->klasifikasi_skor_kedua !== null)
                                        <span class="text-muted">(komisi berikutnya {{ number_format((float) $report->klasifikasi_skor_kedua, 2, ',', '.') }})</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="detail-row">
                                <dt>Kata kunci</dt>
                                <dd>
                                    @forelse (collect($report->klasifikasi_keywords)->pluck('term') as $term)
                                        <span class="status-badge status-neutral mb-1">{{ $term }}</span>
                                    @empty
                                        —
                                    @endforelse
                                </dd>
                            </div>
                            <div class="detail-row">
                                <dt>Metode</dt>
                                <dd>{{ $report->klasifikasi_metode ?: '—' }}</dd>
                            </div>
                            <div class="detail-row">
                                <dt>Dianalisis</dt>
                                <dd>{{ $report->klasifikasi_dianalisis_at ? \App\Support\PortalData::dateTime($report->klasifikasi_dianalisis_at) : '—' }}</dd>
                            </div>
                        </dl>

                        @if ($report->klasifikasi_alasan)
                            <p class="mb-0 mt-2 text-muted" style="font-size: 11px; line-height: 1.7;">
                                {{ $report->klasifikasi_alasan }}
                            </p>
                        @endif
                    @else
                        <p class="mb-0 text-muted" style="font-size: 11px;">
                            Laporan ini belum memiliki hasil klasifikasi otomatis. Administrator tetap dapat menetapkan komisi secara manual.
                        </p>
                    @endif

                    @if ($report->komisiFinal)
                        <div class="soft-divider"></div>
                        <p class="mb-0" style="font-size: 11px; line-height: 1.7;">
                            <strong>Komisi final: {{ $report->komisiFinal->name }}.</strong>
                            Ditetapkan oleh {{ $report->komisiFinalOleh?->name ?: 'Administrator' }}
                            pada {{ $report->komisi_final_ditetapkan_at ? \App\Support\PortalData::dateTime($report->komisi_final_ditetapkan_at) : '—' }}.
                        </p>
                    @endif

                    @if ($isAdministrator)
                        @if ($errors->commission->any())
                            <div class="alert alert-danger app-alert mt-3 mb-0" role="alert">
                                <i class="ri-error-warning-line" aria-hidden="true"></i>
                                <div>
                                    <strong>Komisi final belum dapat disimpan.</strong>
                                    <ul class="mb-0 mt-1 ps-3">
                                        @foreach ($errors->commission->all() as $message)
                                            <li>{{ $message }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        <div class="soft-divider"></div>

                        <form method="POST" action="{{ route('laporan.komisi.update', $report) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="return_to" value="{{ $returnUrl }}">

                            <div class="mb-3">
                                <label class="form-label" for="commissionFinal">Komisi Final</label>
                                <select id="commissionFinal" name="komisi_final_id" class="form-select" required>
                                    <option value="">Pilih komisi</option>
                                    @foreach ($commissions as $commission)
                                        <option value="{{ $commission->id }}"
                                            @selected((string) old('komisi_final_id', $report->komisi_final_id ?? $report->klasifikasi_komisi_id) === (string) $commission->id)>
                                            {{ $commission->name }}{{ $commission->field ? ' — '.$commission->field : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="form-text">Pilihan awal mengikuti rekomendasi otomatis. Administrator dapat mengoreksinya.</p>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="commissionNote">Catatan Keputusan</label>
                                <textarea id="commissionNote" name="catatan" rows="2" maxlength="1000" class="form-control" placeholder="Alasan konfirmasi atau koreksi komisi (opsional).">{{ old('catatan') }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-emerald w-100">
                                <i class="ri-checkbox-circle-line" aria-hidden="true"></i>
                                Simpan Komisi Final
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="app-card mb-3">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Isi Aspirasi</h2>
                        <p class="app-card-subtitle">Uraian yang dikirimkan oleh masyarakat.</p>
                    </div>
                </div>

                <div class="app-card-body">
                    <div class="report-quote">{!! nl2br(e($report->isi ?: $report->ringkasan)) !!}</div>

                    @if ($report->catatan_penyelesaian)
                        <div class="soft-divider"></div>
                        <h3 class="app-card-title mb-2">Catatan Tindak Lanjut</h3>
                        <p class="mb-0 text-muted" style="font-size: 11.5px; line-height: 1.7;">
                            {!! nl2br(e($report->catatan_penyelesaian)) !!}
                        </p>
                    @endif
                </div>
            </div>

            <div class="app-card mb-3">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Lampiran</h2>
                        <p class="app-card-subtitle">Berkas pendukung disimpan secara privat.</p>
                    </div>
                </div>

                <div class="app-card-body">
                    @if ($report->hasLampiran())
                        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                            <div class="d-flex align-items-center gap-3">
                                <span class="d-flex align-items-center justify-content-center rounded-3" style="width: 42px; height: 42px; background: #e6f0eb; color: #155043;">
                                    <i class="ri-attachment-2" aria-hidden="true" style="font-size: 20px;"></i>
                                </span>
                                <div>
                                    <strong class="d-block" style="font-size: 11.5px;">{{ $report->lampiran_nama ?: 'Lampiran' }}</strong>
                                    <span class="text-muted" style="font-size: 9.5px;">
                                        {{ $report->lampiran_size_label }}
                                        @if ($report->lampiran_mime)
                                            &middot; {{ $report->lampiran_mime }}
                                        @endif
                                    </span>
                                </div>
                            </div>

                            <a href="{{ route('laporan.lampiran', $report) }}" class="btn btn-emerald btn-sm">
                                <i class="ri-download-2-line" aria-hidden="true"></i>
                                Unduh Lampiran
                            </a>
                        </div>
                    @else
                        <p class="mb-0 text-muted" style="font-size: 11px;">Tidak ada lampiran pada laporan ini.</p>
                    @endif
                </div>
            </div>

            <div class="app-card">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Riwayat Penugasan</h2>
                        <p class="app-card-subtitle">Catatan perpindahan staf pendamping untuk laporan ini.</p>
                    </div>
                </div>

                <div class="app-card-body">
                    @if ($report->penugasans->isEmpty())
                        <x-admin.empty-state
                            icon="ri-user-follow-line"
                            title="Belum ada riwayat penugasan"
                            description="Riwayat akan muncul setelah administrator menugaskan staf pendamping."
                        />
                    @else
                        <ul class="timeline">
                            @foreach ($report->penugasans as $assignment)
                                <li class="timeline-item {{ $assignment->released_at ? 'is-done' : 'is-active' }}">
                                    <strong>{{ $assignment->user?->name ?: 'Staf tidak ditemukan' }}</strong>
                                    <p>
                                        Ditugaskan {{ \App\Support\PortalData::dateTime($assignment->assigned_at) }}
                                        @if ($assignment->assignedBy)
                                            oleh {{ $assignment->assignedBy->name }}
                                        @endif
                                    </p>
                                    <span>
                                        @if ($assignment->released_at)
                                            Dilepas {{ \App\Support\PortalData::dateTime($assignment->released_at) }}
                                        @else
                                            Penugasan aktif
                                        @endif
                                    </span>
                                    @if ($assignment->catatan)
                                        <p class="mt-1">{{ $assignment->catatan }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            @if ($isAdministrator)
                <div class="app-card mb-3" id="penugasan">
                    <div class="app-card-header">
                        <div>
                            <h2 class="app-card-title">Penugasan</h2>
                            <p class="app-card-subtitle">Pilih anggota dan staf pendamping aktifnya.</p>
                        </div>
                    </div>

                    <div class="app-card-body">
                        @if ($errors->assignment->any())
                            <div class="alert alert-danger app-alert mb-3" role="alert">
                                <i class="ri-error-warning-line" aria-hidden="true"></i>
                                <div>
                                    <strong>Penugasan belum dapat disimpan.</strong>
                                    <ul class="mb-0 mt-1 ps-3">
                                        @foreach ($errors->assignment->all() as $message)
                                            <li>{{ $message }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('laporan.penugasan.update', $report) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="return_to" value="{{ $returnUrl }}">

                            <div class="mb-3">
                                <label class="form-label" for="assignmentMember">Anggota DPRD</label>
                                <select id="assignmentMember" name="dprd_member_id" class="form-select">
                                    <option value="">Belum ditentukan</option>
                                    @foreach ($members as $member)
                                        <option value="{{ $member->id }}" @selected((string) old('dprd_member_id', $report->dprd_member_id) === (string) $member->id)>
                                            {{ $member->nama_lengkap }}{{ $member->komisi ? ' — '.$member->komisi : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="assignmentStaff">Staf Pendamping</label>
                                <select id="assignmentStaff" name="assigned_to" class="form-select">
                                    <option value="">Belum ditugaskan</option>
                                    @foreach ($staffOptions as $staff)
                                        <option value="{{ $staff['id'] }}"
                                                data-member="{{ $staff['member_id'] }}"
                                                @selected((string) old('assigned_to', $report->assigned_to) === (string) $staff['id'])>
                                            {{ $staff['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="form-text">Hanya staf aktif yang terhubung ke anggota terpilih yang dapat ditugaskan.</p>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="assignmentNote">Catatan Penugasan</label>
                                <textarea id="assignmentNote" name="catatan" rows="3" maxlength="1000" class="form-control" placeholder="Arahan singkat untuk staf pendamping.">{{ old('catatan') }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-emerald w-100">
                                <i class="ri-save-3-line" aria-hidden="true"></i>
                                Simpan Penugasan
                            </button>
                        </form>
                    </div>
                </div>

                <div class="app-card" id="status">
                    <div class="app-card-header">
                        <div>
                            <h2 class="app-card-title">Status &amp; Tindak Lanjut</h2>
                            <p class="app-card-subtitle">Hanya transisi status yang sah yang ditampilkan.</p>
                        </div>
                    </div>

                    <div class="app-card-body">
                        @if (in_array($report->status, [\App\Models\LaporanMasyarakat::STATUS_SELESAI, \App\Models\LaporanMasyarakat::STATUS_DITOLAK], true))
                            <div class="alert alert-info app-alert mb-0" role="status">
                                <i class="ri-lock-2-line" aria-hidden="true"></i>
                                <div>Laporan sudah ditutup dan tidak dapat diubah lagi statusnya.</div>
                            </div>
                        @else
                            @if ($errors->status->any())
                                <div class="alert alert-danger app-alert mb-3" role="alert">
                                    <i class="ri-error-warning-line" aria-hidden="true"></i>
                                    <div>
                                        <strong>Status belum dapat disimpan.</strong>
                                        <ul class="mb-0 mt-1 ps-3">
                                            @foreach ($errors->status->all() as $message)
                                                <li>{{ $message }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('laporan.status.update', $report) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="return_to" value="{{ $returnUrl }}">

                                <div class="mb-3">
                                    <label class="form-label" for="reportStatus">Status Laporan</label>
                                    <select id="reportStatus" name="status" class="form-select">
                                        @foreach ($statusOptions as $value => $label)
                                            <option value="{{ $value }}" @selected(old('status', $report->status) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="reportStatusNote">Catatan Tindak Lanjut</label>
                                    <textarea id="reportStatusNote" name="catatan" rows="4" maxlength="2000" class="form-control" placeholder="Tuliskan hasil koordinasi atau alasan perubahan status.">{{ old('catatan', $report->catatan_penyelesaian) }}</textarea>
                                </div>

                                <button type="submit" class="btn btn-emerald w-100">
                                    <i class="ri-check-double-line" aria-hidden="true"></i>
                                    Simpan Status
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @else
                <div class="app-card">
                    <div class="app-card-header">
                        <div>
                            <h2 class="app-card-title">Penanganan Laporan</h2>
                            <p class="app-card-subtitle">Akses staf pendamping bersifat baca.</p>
                        </div>
                    </div>

                    <div class="app-card-body">
                        <div class="alert alert-info app-alert mb-0" role="status">
                            <i class="ri-information-line" aria-hidden="true"></i>
                            <div>
                                Anda dapat melihat detail laporan yang ditugaskan kepada Anda. Perubahan penugasan dan status hanya dapat dilakukan administrator.
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const memberSelect = document.getElementById('assignmentMember');
            const staffSelect = document.getElementById('assignmentStaff');

            if (!memberSelect || !staffSelect) {
                return;
            }

            const filterStaff = function () {
                const memberId = memberSelect.value;

                Array.from(staffSelect.options).forEach(function (option) {
                    if (!option.value) {
                        return;
                    }

                    const matches = option.getAttribute('data-member') === memberId;
                    option.hidden = !matches;
                    option.disabled = !matches;
                });

                const selected = staffSelect.selectedOptions[0];

                if (selected && selected.disabled) {
                    staffSelect.value = '';
                }
            };

            memberSelect.addEventListener('change', filterStaff);
            filterStaff();
        });
    </script>
@endpush
