<?php

namespace App\Http\Requests;

use App\Models\ArchiveFolder;
use App\Models\ArchiveYear;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArchiveFolderRequest extends FormRequest
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
        /** @var ArchiveYear|null $archiveYear */
        $archiveYear = $this->route('archiveYear');
        /** @var ArchiveFolder|null $folder */
        $folder = $this->route('folder');

        return [
            'nama' => [
                'required',
                'string',
                'max:150',
                Rule::unique('archive_folders', 'nama')
                    ->where(fn ($query) => $query->where('archive_year_id', $archiveYear?->id))
                    ->ignore($folder?->id),
            ],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nama' => 'nama folder',
            'deskripsi' => 'keterangan folder',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.unique' => 'Nama folder sudah digunakan pada tahun anggaran ini.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nama' => trim((string) $this->input('nama')),
            'deskripsi' => trim((string) $this->input('deskripsi')) ?: null,
        ]);
    }
}
