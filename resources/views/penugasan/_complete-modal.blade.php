<div class="modal fade" id="completeAssignmentModal" tabindex="-1" aria-labelledby="completeAssignmentTitle" aria-hidden="true"
     data-open-on-load="{{ $errors->penugasan->any() ? '1' : '0' }}">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" id="completeAssignmentForm"
                  action="{{ old('penugasan_id') ? route('penugasan.selesai', old('penugasan_id')) : '#' }}"
                  enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="penugasan_id" id="completeAssignmentId" value="{{ old('penugasan_id') }}">

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="completeAssignmentTitle">Tandai Penugasan Selesai</h5>
                        <p class="app-card-subtitle mb-0" id="completeAssignmentSubtitle">Lengkapi hasil tindak lanjut sebelum disimpan.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body">
                    @if ($errors->penugasan->any())
                        <div class="alert alert-danger app-alert mb-3" role="alert">
                            <i class="ri-error-warning-line" aria-hidden="true"></i>
                            <div>
                                <strong>Penugasan belum dapat ditandai selesai.</strong>
                                <ul class="mb-0 mt-1 ps-3">
                                    @foreach ($errors->penugasan->all() as $message)
                                        <li>{{ $message }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label" for="hasilTindakLanjut">Hasil Tindak Lanjut <span class="req">*</span></label>
                        <textarea id="hasilTindakLanjut" name="hasil_tindak_lanjut" rows="4" required maxlength="5000"
                                  class="form-control @if ($errors->penugasan->has('hasil_tindak_lanjut')) is-invalid @endif"
                                  placeholder="Contoh: Koordinasi telah dilakukan dengan pihak terkait dan laporan sudah diteruskan untuk penanganan lebih lanjut.">{{ old('hasil_tindak_lanjut') }}</textarea>
                        @if ($errors->penugasan->has('hasil_tindak_lanjut'))
                            <div class="invalid-feedback">{{ $errors->penugasan->first('hasil_tindak_lanjut') }}</div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="keteranganTambahan">Keterangan Tambahan</label>
                        <textarea id="keteranganTambahan" name="keterangan_tambahan" rows="3" maxlength="2000"
                                  class="form-control @if ($errors->penugasan->has('keterangan_tambahan')) is-invalid @endif"
                                  placeholder="Catatan pelengkap (opsional).">{{ old('keterangan_tambahan') }}</textarea>
                        @if ($errors->penugasan->has('keterangan_tambahan'))
                            <div class="invalid-feedback">{{ $errors->penugasan->first('keterangan_tambahan') }}</div>
                        @endif
                    </div>

                    <div class="mb-2">
                        <label class="form-label" for="buktiTindakLanjut">Bukti Tindak Lanjut / Dokumen Pendukung</label>
                        <input type="file" id="buktiTindakLanjut" name="bukti"
                               class="form-control @if ($errors->penugasan->has('bukti')) is-invalid @endif"
                               accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx">
                        @if ($errors->penugasan->has('bukti'))
                            <div class="invalid-feedback">{{ $errors->penugasan->first('bukti') }}</div>
                        @endif
                        <div class="form-text">Opsional. Maksimal 20 MB. Format: PDF, JPG, PNG, WEBP, DOC, atau DOCX.</div>
                    </div>

                    <div class="demo-note mt-3 mb-0">
                        <i class="ri-information-line" aria-hidden="true"></i>
                        <div>
                            Setelah disimpan, status penugasan berubah menjadi <strong>Selesai Ditindaklanjuti</strong>
                            dan Superadmin menerima notifikasi. Status aspirasi tetap diverifikasi Superadmin
                            melalui alur status laporan.
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-soft" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-emerald">
                        <i class="ri-check-double-line" aria-hidden="true"></i>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modalEl = document.getElementById('completeAssignmentModal');

            if (! modalEl) {
                return;
            }

            function showCompleteModal() {
                if (window.bootstrap && window.bootstrap.Modal) {
                    window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
                }
            }

            document.querySelectorAll('[data-complete-assignment]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var form = document.getElementById('completeAssignmentForm');

                    if (! form || ! button.dataset.completeAction) {
                        return;
                    }

                    form.reset();
                    form.action = button.dataset.completeAction;
                    document.getElementById('completeAssignmentId').value = button.dataset.completeId || '';
                    document.getElementById('completeAssignmentSubtitle').textContent =
                        button.dataset.completeTitle || 'Lengkapi hasil tindak lanjut sebelum disimpan.';

                    showCompleteModal();
                });
            });

            var completeForm = document.getElementById('completeAssignmentForm');

            if (completeForm) {
                completeForm.addEventListener('submit', function () {
                    var submitButton = completeForm.querySelector('button[type="submit"]');

                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.classList.add('is-loading');
                    }
                });
            }

            if (modalEl.dataset.openOnLoad === '1') {
                showCompleteModal();
            }
        });
    </script>
@endpush
