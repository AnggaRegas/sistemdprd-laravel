<?php

namespace App\Http\Requests;

use App\Models\LaporanPenugasan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CompleteAssignmentRequest extends FormRequest
{
    protected $errorBag = 'penugasan';

    /**
     * Hanya staf pendamping pemilik penugasan aktif yang boleh menandai
     * penugasan selesai. Pemeriksaan dilakukan di backend, bukan hanya UI.
     */
    public function authorize(): bool
    {
        $penugasan = $this->route('penugasan');
        $user = $this->user();

        return $penugasan instanceof LaporanPenugasan
            && ($user?->isStaff() ?? false)
            && (int) $penugasan->user_id === (int) $user->id
            && $penugasan->released_at === null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'hasil_tindak_lanjut' => ['required', 'string', 'min:10', 'max:5000'],
            'keterangan_tambahan' => ['nullable', 'string', 'max:2000'],
            'bukti' => [
                'nullable',
                'file',
                'max:20480',
                'mimes:pdf,jpg,jpeg,png,webp,doc,docx',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'hasil_tindak_lanjut.required' => 'Hasil tindak lanjut wajib diisi.',
            'hasil_tindak_lanjut.min' => 'Hasil tindak lanjut minimal 10 karakter.',
            'hasil_tindak_lanjut.max' => 'Hasil tindak lanjut maksimal 5.000 karakter.',
            'keterangan_tambahan.max' => 'Keterangan tambahan maksimal 2.000 karakter.',
            'bukti.file' => 'Bukti tindak lanjut harus berupa file yang valid.',
            'bukti.max' => 'Ukuran file maksimal 20 MB.',
            'bukti.mimes' => 'Format file tidak didukung. Gunakan PDF, JPG, JPEG, PNG, WEBP, DOC, atau DOCX.',
            'bukti.uploaded' => 'Gagal mengunggah bukti tindak lanjut. Silakan coba kembali.',
        ];
    }

    /**
     * Petakan kegagalan PHP pada tingkat transport (file terlalu besar untuk
     * upload_max_filesize, upload terputus, atau penyimpanan sementara gagal)
     * menjadi pesan yang menjelaskan penyebab sebenarnya, bukan pesan umum
     * "Kolom bukti gagal diunggah." yang tidak membedakan penyebabnya.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $file = $this->file('bukti');

            if ($file === null || $file->isValid()) {
                return;
            }

            $message = match ($file->getError()) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ukuran file maksimal 20 MB.',
                UPLOAD_ERR_PARTIAL => 'Unggahan bukti tidak lengkap. Silakan coba kembali.',
                UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'Gagal menyimpan bukti tindak lanjut. Silakan coba kembali.',
                UPLOAD_ERR_NO_FILE => 'Tidak ada file bukti yang terkirim. Silakan pilih file kembali.',
                default => 'Gagal mengunggah bukti tindak lanjut. Silakan coba kembali.',
            };

            $validator->errors()->forget('bukti');
            $validator->errors()->add('bukti', $message);
        });
    }

    protected function prepareForValidation(): void
    {
        $hasil = $this->input('hasil_tindak_lanjut');
        $keterangan = $this->input('keterangan_tambahan');

        /*
         * Nilai bukti yang kosong diperlakukan sebagai "tidak ada file", bukan
         * sebagai kegagalan unggah, karena bukti bersifat opsional.
         */
        $upload = $this->input('bukti');

        if (($upload === null || $upload === '') && ! $this->hasFile('bukti')) {
            $this->request->remove('bukti');
        }

        $this->merge([
            'hasil_tindak_lanjut' => is_string($hasil) ? trim($hasil) : $hasil,
            'keterangan_tambahan' => is_string($keterangan) ? (trim($keterangan) ?: null) : $keterangan,
        ]);
    }
}
