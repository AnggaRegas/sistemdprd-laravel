<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KamusImportRequest extends FormRequest
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
            'file' => [
                'required',
                'file',
                'max:5120',
                'mimes:csv,txt',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'file' => 'berkas CSV',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.mimes' => 'Berkas harus berformat CSV atau TXT.',
            'file.max' => 'Ukuran berkas CSV maksimal 5 MB.',
        ];
    }
}
