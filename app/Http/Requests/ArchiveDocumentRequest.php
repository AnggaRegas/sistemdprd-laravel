<?php

namespace App\Http\Requests;

use App\Models\Arsip;
use Illuminate\Foundation\Http\FormRequest;
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

        return [
            'nama' => ['required', 'string', 'max:200'],
            'jenis' => ['required', 'string', Rule::in(array_keys(Arsip::JENIS_LABELS))],
            'tanggal' => ['nullable', 'date'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'file' => [
                $isCreate ? 'required' : 'nullable',
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
        return [
            'file.required' => 'Berkas dokumen wajib diunggah saat menambahkan dokumen baru.',
            'file.max' => 'Ukuran berkas maksimal 10 MB.',
            'file.mimes' => 'Format berkas harus PDF, Word, Excel, PowerPoint, JPG, PNG, WEBP, atau TXT.',
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
