<?php

namespace App\Http\Requests;

use App\Models\LaporanKamusVerifikasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KamusVerifikasiRequest extends FormRequest
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
        return [
            'keputusan' => [
                'required',
                'string',
                Rule::in([
                    LaporanKamusVerifikasi::KEPUTUSAN_SESUAI,
                    LaporanKamusVerifikasi::KEPUTUSAN_TIDAK_DITEMUKAN,
                    LaporanKamusVerifikasi::KEPUTUSAN_PERLU_VERIFIKASI,
                ]),
            ],
            'entri_id' => [
                'nullable',
                'integer',
                Rule::exists('kamus_acuan_entri', 'id')->whereNull('deleted_at'),
                Rule::requiredIf(fn () => $this->input('keputusan') === LaporanKamusVerifikasi::KEPUTUSAN_SESUAI),
            ],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'keputusan' => 'keputusan verifikasi',
            'entri_id' => 'entri kamus',
            'catatan' => 'catatan verifikasi',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'entri_id.required_if' => 'Pilih entri Kamus Acuan jika keputusan adalah sesuai kamus.',
            'entri_id.required' => 'Pilih entri Kamus Acuan jika keputusan adalah sesuai kamus.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'catatan' => trim((string) $this->input('catatan')),
        ]);
    }
}
