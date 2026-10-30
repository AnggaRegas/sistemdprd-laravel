<?php

namespace App\Http\Requests;

use App\Models\ArchiveYear;
use App\Models\DprdMember;
use App\Models\KategoriArsip;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArchiveYearRequest extends FormRequest
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
        /** @var DprdMember|null $member */
        $member = $this->route('member');
        /** @var KategoriArsip|null $category */
        $category = $this->route('category');
        /** @var ArchiveYear|null $archiveYear */
        $archiveYear = $this->route('archiveYear');

        return [
            'tahun' => [
                'required',
                'integer',
                'between:1900,2100',
                Rule::unique('archive_years', 'tahun')
                    ->where(fn ($query) => $query
                        ->where('dprd_member_id', $member?->id)
                        ->where('kategori_arsip_id', $category?->id))
                    ->ignore($archiveYear?->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'tahun' => 'tahun anggaran',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tahun.between' => 'Tahun anggaran harus berada di antara 1900 dan 2100.',
            'tahun.unique' => 'Tahun anggaran tersebut sudah ada untuk anggota dan kategori ini.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tahun' => trim((string) $this->input('tahun')),
        ]);
    }
}
