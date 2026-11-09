<?php

namespace App\Http\Requests;

use App\Models\RdpDocument;
use App\Support\ArchiveFileStorage;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class RdpDocumentRequest extends FormRequest
{
    /**
     * Error unggah dokumen tidak menimpa error form lain di halaman detail.
     */
    protected $errorBag = 'documentUpload';

    public function authorize(): bool
    {
        return $this->user()?->can('manage-rdp-archives') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'category' => [
                'required',
                'string',
                Rule::in(array_keys(RdpDocument::CATEGORY_LABELS)),
            ],
            'document_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
            'file' => [
                'required',
                'file',
                'max:'.ArchiveFileStorage::maxUploadKilobytes(),
                'mimes:'.ArchiveFileStorage::allowedExtensionsString(),
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
            'title' => 'judul dokumen',
            'category' => 'kategori dokumen',
            'document_date' => 'tanggal dokumen',
            'description' => 'keterangan',
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
            'file.required' => 'Berkas dokumen wajib diunggah.',
            'file.file' => 'Berkas dokumen yang dipilih tidak valid.',
            'file.uploaded' => "Berkas gagal diunggah atau melebihi batas maksimal server ({$maxMegabytes} MB).",
            'file.max' => "Ukuran berkas dokumen tidak boleh melebihi {$maxMegabytes} MB.",
            'file.mimes' => "Format berkas harus berupa {$formatsLabel}.",
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => trim((string) $this->input('title')),
            'description' => trim((string) $this->input('description')) ?: null,
        ]);
    }
}
