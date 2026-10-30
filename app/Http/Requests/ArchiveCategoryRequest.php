<?php

namespace App\Http\Requests;

use App\Models\KategoriArsip;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArchiveCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdministrator() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var KategoriArsip|null $category */
        $category = $this->route('category');

        return [
            'nama' => [
                'required',
                'string',
                'max:100',
                Rule::unique('kategori_arsips', 'nama')->ignore($category?->id),
            ],
            'kode' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('kategori_arsips', 'kode')->ignore($category?->id),
            ],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nama' => 'nama kategori',
            'kode' => 'kode kategori',
            'deskripsi' => 'deskripsi kategori',
            'is_active' => 'status kategori',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nama' => trim((string) $this->input('nama')),
            'kode' => trim((string) $this->input('kode')) ?: null,
            'deskripsi' => trim((string) $this->input('deskripsi')) ?: null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
