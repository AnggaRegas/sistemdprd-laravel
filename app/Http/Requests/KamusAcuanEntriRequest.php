<?php

namespace App\Http\Requests;

use App\Models\KamusAcuanEntri;
use App\Models\KamusAcuanVersi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KamusAcuanEntriRequest extends FormRequest
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
        /** @var KamusAcuanEntri|null $entry */
        $entry = $this->route('entry');
        /** @var KamusAcuanVersi|null $version */
        $version = $this->route('version');

        $versionId = $version?->id ?? $entry?->kamus_acuan_versi_id;

        return [
            'kode' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9][A-Za-z0-9._\-\/]*$/',
                Rule::unique('kamus_acuan_entri', 'kode')
                    ->where(static function ($query) use ($versionId) {
                        $query->where('kamus_acuan_versi_id', $versionId)
                            ->whereNull('deleted_at');
                    })
                    ->ignore($entry?->id),
            ],
            'klasifikasi' => ['required', 'string', 'max:180'],
            'uraian' => ['nullable', 'string', 'max:5000'],
            'kata_kunci_text' => ['nullable', 'string', 'max:50000'],
            'sinonim_text' => ['nullable', 'string', 'max:50000'],
            'kriteria' => ['nullable', 'string', 'max:5000'],
            'prioritas' => ['nullable', 'string', 'max:100'],
            'tindak_lanjut' => ['nullable', 'string', 'max:5000'],
            'dprd_commission_id' => [
                'nullable',
                'integer',
                Rule::exists('dprd_commissions', 'id')->whereNull('deleted_at'),
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'kode' => 'kode entri',
            'klasifikasi' => 'klasifikasi',
            'uraian' => 'uraian',
            'kata_kunci_text' => 'kata kunci',
            'sinonim_text' => 'sinonim',
            'kriteria' => 'kriteria',
            'prioritas' => 'prioritas',
            'tindak_lanjut' => 'tindak lanjut',
            'dprd_commission_id' => 'komisi',
            'is_active' => 'status entri',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kode.regex' => 'Kode hanya boleh berisi huruf, angka, titik, garis bawah, tanda hubung, atau garis miring.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'kode' => trim((string) $this->input('kode')),
            'klasifikasi' => trim((string) $this->input('klasifikasi')),
            'uraian' => trim((string) $this->input('uraian')),
            'kata_kunci_text' => trim((string) $this->input('kata_kunci_text')),
            'sinonim_text' => trim((string) $this->input('sinonim_text')),
            'kriteria' => trim((string) $this->input('kriteria')),
            'prioritas' => trim((string) $this->input('prioritas')),
            'tindak_lanjut' => trim((string) $this->input('tindak_lanjut')),
        ]);
    }
}
