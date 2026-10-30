<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportCommissionRequest extends FormRequest
{
    /**
     * Error komisi final tidak menimpa error penugasan/status.
     */
    protected $errorBag = 'commission';

    public function authorize(): bool
    {
        return $this->user()?->isAdministrator() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'komisi_final_id' => [
                'required',
                'integer',
                Rule::exists('dprd_commissions', 'id')
                    ->whereNull('deleted_at')
                    ->where('is_active', true),
            ],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'komisi_final_id.required' => 'Komisi final wajib dipilih.',
            'komisi_final_id.exists' => 'Komisi yang dipilih tidak tersedia atau sudah nonaktif.',
            'catatan.max' => 'Catatan komisi maksimal 1000 karakter.',
        ];
    }
}
