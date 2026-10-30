<?php

namespace App\Http\Requests;

use App\Models\Arsip;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArchiveRequest extends FormRequest
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
        /** @var Arsip|null $archive */
        $archive = $this->route('archive');

        return [
            'nomor' => [
                'required',
                'string',
                'max:100',
                Rule::unique('arsips', 'nomor')->ignore($archive?->id),
            ],
            'nama' => ['required', 'string', 'max:200'],
            'kategori_arsip_id' => [
                'required',
                'integer',
                Rule::exists('kategori_arsips', 'id')->whereNull('deleted_at'),
            ],
            'dprd_member_id' => [
                'nullable',
                'integer',
                Rule::exists('dprd_members', 'id')->whereNull('deleted_at'),
            ],
            'jenis' => ['required', 'string', Rule::in(array_keys(Arsip::JENIS_LABELS))],
            'status' => ['required', 'string', Rule::in(array_keys(Arsip::STATUS_LABELS))],
            'tanggal' => ['required', 'date'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'file' => [
                'nullable',
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
            'nomor' => 'nomor arsip',
            'nama' => 'nama dokumen',
            'kategori_arsip_id' => 'kategori arsip',
            'dprd_member_id' => 'anggota DPRD terkait',
            'jenis' => 'jenis arsip',
            'status' => 'status arsip',
            'tanggal' => 'tanggal arsip',
            'deskripsi' => 'keterangan',
            'file' => 'berkas arsip',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nomor' => trim((string) $this->input('nomor')),
            'nama' => trim((string) $this->input('nama')),
            'deskripsi' => trim((string) $this->input('deskripsi')) ?: null,
        ]);
    }
}
