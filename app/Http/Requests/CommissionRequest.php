<?php

namespace App\Http\Requests;

use App\Models\DprdCommission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-rdp-archives') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var DprdCommission|null $commission */
        $commission = $this->route('commission');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('dprd_commissions', 'name')
                    ->where(function ($query) {
                        $query->whereNull('deleted_at');
                    })
                    ->ignore($commission?->id),
            ],
            'field' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'keywords_text' => ['nullable', 'string', 'max:20000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama komisi',
            'field' => 'bidang',
            'description' => 'deskripsi',
            'keywords_text' => 'kata kunci klasifikasi',
            'is_active' => 'status komisi',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'field' => trim((string) $this->input('field')),
            'description' => trim((string) $this->input('description')),
            'keywords_text' => trim((string) $this->input('keywords_text')),
        ]);
    }
}
