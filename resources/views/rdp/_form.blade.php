@php
    $isEdit = isset($meeting) && $meeting->exists;
    $selectedCommission = old('dprd_commission_id', $meeting->dprd_commission_id ?? $commission->id);
    $selectedStatus = old('status', $meeting->status ?? \App\Models\RdpMeeting::STATUS_DRAF);
    $meetingDate = old('meeting_date', $meeting->meeting_date?->format('Y-m-d') ?? now()->toDateString());
    $startTime = old('start_time', $meeting->start_time ? substr((string) $meeting->start_time, 0, 5) : '');
@endphp

<form method="POST" action="{{ $isEdit ? route('komisi.rdp.update', [$commission, $meeting]) : route('komisi.rdp.store', $commission) }}">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="app-card">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Informasi RDP</h2>
                        <p class="app-card-subtitle">Agenda, jadwal, tempat, dan status Rapat Dengar Pendapat.</p>
                    </div>
                    <span class="status-badge status-gold">{{ $commission->name }}</span>
                </div>

                <div class="app-card-body">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label" for="dprd_commission_id">Komisi <span class="req">*</span></label>
                            <select id="dprd_commission_id" name="dprd_commission_id"
                                    class="form-select @error('dprd_commission_id') is-invalid @enderror" required>
                                @foreach ($commissions as $option)
                                    <option value="{{ $option->id }}" @selected((string) $selectedCommission === (string) $option->id)>
                                        {{ $option->name }}{{ $option->field ? ' — '.$option->field : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('dprd_commission_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Komisi divalidasi ulang di server. ID komisi dari browser tidak dipercaya begitu saja.
                            </div>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label" for="status">Status RDP <span class="req">*</span></label>
                            <select id="status" name="status"
                                    class="form-select @error('status') is-invalid @enderror" required>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="title">Judul / Agenda RDP <span class="req">*</span></label>
                            <input type="text" id="title" name="title"
                                   value="{{ old('title', $meeting->title ?? '') }}"
                                   class="form-control @error('title') is-invalid @enderror"
                                   required maxlength="200" autofocus
                                   placeholder="Contoh: Pembahasan Rancangan Perda APBD">
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="code">Nomor / Kode RDP</label>
                            <input type="text" id="code" name="code"
                                   value="{{ old('code', $meeting->code ?? '') }}"
                                   class="form-control @error('code') is-invalid @enderror"
                                   maxlength="80" placeholder="Contoh: RDP-K1/001/2026">
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Kode unik per komisi. Kode lama dapat dipakai kembali setelah RDP dihapus.</div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="meeting_date">Tanggal <span class="req">*</span></label>
                            <input type="date" id="meeting_date" name="meeting_date"
                                   value="{{ $meetingDate }}"
                                   class="form-control @error('meeting_date') is-invalid @enderror" required>
                            @error('meeting_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="start_time">Waktu</label>
                            <input type="time" id="start_time" name="start_time"
                                   value="{{ $startTime }}"
                                   class="form-control @error('start_time') is-invalid @enderror">
                            @error('start_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="location">Tempat Pelaksanaan</label>
                            <input type="text" id="location" name="location"
                                   value="{{ old('location', $meeting->location ?? '') }}"
                                   class="form-control @error('location') is-invalid @enderror"
                                   maxlength="180" placeholder="Contoh: Ruang Rapat Komisi 1 DPRD Kota Parepare">
                            @error('location')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="description">Deskripsi / Ringkasan</label>
                            <textarea id="description" name="description"
                                      class="form-control @error('description') is-invalid @enderror"
                                      maxlength="5000" rows="4"
                                      placeholder="Ringkasan agenda, pihak yang diundang, atau pokok pembahasan.">{{ old('description', $meeting->description ?? '') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="notes">Catatan</label>
                            <textarea id="notes" name="notes"
                                      class="form-control @error('notes') is-invalid @enderror"
                                      maxlength="2000" rows="3"
                                      placeholder="Catatan internal mengenai pelaksanaan atau tindak lanjut RDP.">{{ old('notes', $meeting->notes ?? '') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="demo-note mt-3 mb-0">
                        <i class="ri-shield-check-line" aria-hidden="true"></i>
                        <div>
                            RDP selalu terikat pada satu komisi. Setelah disimpan, RDP hanya muncul pada folder komisi
                            yang dipilih dan tidak dapat tercampur dengan komisi lain.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="app-card mb-3">
                <div class="app-card-header">
                    <div>
                        <h2 class="app-card-title">Folder Komisi</h2>
                        <p class="app-card-subtitle">Tujuan penyimpanan arsip RDP.</p>
                    </div>
                </div>

                <div class="app-card-body">
                    <div class="d-flex align-items-start gap-3">
                        <span class="commission-icon commission-icon-sm" aria-hidden="true">
                            <i class="{{ $commission->icon }}"></i>
                        </span>
                        <div>
                            <strong class="d-block text-ink">{{ $commission->name }}</strong>
                            <span class="text-muted-soft fs-10">{{ $commission->field ?: 'Bidang belum diisi' }}</span>
                        </div>
                    </div>

                    <div class="demo-note mt-3 mb-0">
                        <i class="ri-information-line" aria-hidden="true"></i>
                        <div>
                            @if ($commission->is_active)
                                Komisi ini aktif dan dapat menerima RDP baru.
                            @else
                                Komisi ini nonaktif. RDP baru tidak dapat ditambahkan sampai komisi diaktifkan kembali.
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-card">
                <div class="app-card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-emerald">
                            <i class="ri-save-3-line" aria-hidden="true"></i>
                            {{ $isEdit ? 'Simpan Perubahan' : 'Simpan RDP' }}
                        </button>
                        <a href="{{ $isEdit ? route('komisi.rdp.show', [$commission, $meeting]) : route('komisi.rdp.index', $commission) }}"
                           class="btn btn-outline-soft">
                            <i class="ri-arrow-left-line" aria-hidden="true"></i>
                            Batal dan Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
