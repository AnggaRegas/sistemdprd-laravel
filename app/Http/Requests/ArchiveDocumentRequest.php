<?php

namespace App\Http\Requests;

use App\Models\Arsip;
use App\Support\ArchiveFileStorage;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class ArchiveDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdministrator()
            || $this->user()?->isStaff();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isCreate = $this->isMethod('post');
        $maxKilobytes = ArchiveFileStorage::maxUploadKilobytes();
        $allowedExtensions = ArchiveFileStorage::allowedExtensionsString();

        return [
            'nama' => ['required', 'string', 'max:200'],
            'jenis' => ['required', 'string', Rule::in(array_keys(Arsip::JENIS_LABELS))],
            'tanggal' => ['nullable', 'date'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'file' => [
                $isCreate ? 'required' : 'nullable',
                'file',
                "max:{$maxKilobytes}",
                "mimes:{$allowedExtensions}",
                static function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value instanceof UploadedFile) {
                        $error = ArchiveFileStorage::validateUploadedFile($value);
                        if ($error !== null) {
                            $fail($error);
                        }
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nama' => 'judul dokumen',
            'jenis' => 'jenis dokumen',
            'tanggal' => 'tanggal dokumen',
            'deskripsi' => 'keterangan dokumen',
            'file' => 'berkas dokumen',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxMegabytes = ArchiveFileStorage::maxUploadMegabytes();
        $formatsLabel = ArchiveFileStorage::supportedFormatsLabel();

        return [
            'file.required' => 'Berkas dokumen wajib diunggah saat menambahkan dokumen baru.',
            'file.file' => 'Berkas dokumen yang dipilih tidak valid.',
            'file.uploaded' => "Berkas gagal diunggah atau melebihi batas maksimal server ({$maxMegabytes} MB).",
            'file.max' => "Ukuran berkas dokumen tidak boleh melebihi {$maxMegabytes} MB.",
            'file.mimes' => "Format berkas harus berupa {$formatsLabel}.",
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nama' => trim((string) $this->input('nama')),
            'deskripsi' => trim((string) $this->input('deskripsi')) ?: null,
            'tanggal' => $this->input('tanggal') ?: null,
        ]);
    }
}
