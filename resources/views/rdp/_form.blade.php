@php
    $isEdit = isset($meeting) && $meeting->exists;
    $selectedCommission = old('dprd_commission_id', $meeting->dprd_commission_id ?? $commission->id);
    $selectedStatus = old('status', $meeting->status ?? \App\Models\RdpMeeting::STATUS_DRAF);
    $meetingDate = old('meeting_date', $meeting->meeting_date?->format('Y-m-d') ?? now()->toDateString());
@endphp

<form method="POST" action="{{ $isEdit ? route('komisi.rdp.update', [$commission, $meeting]) : route('komisi.rdp.store', $commission) }}">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-12">
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
                        @if ($isEdit)
                            <div class="col-md-6">
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
                            </div>
                        @else
                            <input type="hidden" name="dprd_commission_id" value="{{ $selectedCommission }}">
                        @endif

                        <div class="col-md-6">
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
                            <label class="form-label" for="meeting_date">Tanggal <span class="req">*</span></label>
                            <input type="date" id="meeting_date" name="meeting_date"
                                   value="{{ $meetingDate }}"
                                   class="form-control @error('meeting_date') is-invalid @enderror" required>
                            @error('meeting_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
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
                            Nomor RDP dan waktu dicatat otomatis oleh sistem. RDP tersimpan pada komisi
                            {{ $commission->name }} dan tidak tercampur dengan komisi lain.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-grid mt-3">
        <button type="submit" class="btn btn-emerald">
            <i class="ri-save-3-line" aria-hidden="true"></i>
            {{ $isEdit ? 'Simpan Perubahan' : 'Simpan RDP' }}
        </button>
    </div>
</form>
