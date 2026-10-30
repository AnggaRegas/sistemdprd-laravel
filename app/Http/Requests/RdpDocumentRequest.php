<?php

namespace App\Http\Requests;

use App\Models\RdpDocument;
use Illuminate\Foundation\Http\FormRequest;
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
                'max:10240',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,webp,txt',
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

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => trim((string) $this->input('title')),
            'description' => trim((string) $this->input('description')) ?: null,
        ]);
    }
}
