<?php

namespace App\Http\Requests;

use App\Models\KamusAcuanVersi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KamusAcuanVersiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-kamus-acuan') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var KamusAcuanVersi|null $version */
        $version = $this->route('version');

        return [
            'tahun' => [
                'required',
                'integer',
                'between:2000,2100',
                Rule::unique('kamus_acuan_versi', 'tahun')->ignore($version?->id),
            ],
            'nama' => ['required', 'string', 'max:150'],
            'keterangan' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
            'salin_dari' => [
                'nullable',
                'integer',
                Rule::exists('kamus_acuan_versi', 'id'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'tahun' => 'tahun kamus',
            'nama' => 'nama versi',
            'keterangan' => 'keterangan',
            'is_active' => 'status aktif',
            'salin_dari' => 'sumber salinan entri',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tahun.unique' => 'Versi Kamus Acuan untuk tahun tersebut sudah ada.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tahun' => trim((string) $this->input('tahun')),
            'nama' => trim((string) $this->input('nama')),
            'keterangan' => trim((string) $this->input('keterangan')),
        ]);
    }
}
