<?php

namespace App\Http\Requests;

use App\Models\DprdMember;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var DprdMember|null $member */
        $member = $this->route('member');

        if ($member instanceof DprdMember) {
            return $this->user()?->can('update', $member) ?? false;
        }

        return $this->user()?->can('create', DprdMember::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var DprdMember|null $member */
        $member = $this->route('member');

        return [
            'kode' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('dprd_members', 'kode')
                    ->whereNull('deleted_at')
                    ->ignore($member?->id),
            ],
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'jabatan' => ['required', 'string', 'max:100'],
            'partai' => ['required', 'string', 'max:100'],
            'dapil' => ['nullable', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'dprd_region_id' => [
                'nullable',
                'integer',
                Rule::exists('dprd_regions', 'id')->where('is_active', true),
            ],
            'staff_id' => [
                'nullable',
                'integer',
                Rule::exists('dprd_staffs', 'id')->where('is_active', true),
                Rule::unique('dprd_members', 'staff_id')->ignore($member?->id),
            ],
            'komisi' => ['nullable', 'string', 'max:100'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'kode' => 'kode anggota',
            'nama_lengkap' => 'nama lengkap dan gelar',
            'jabatan' => 'jabatan',
            'partai' => 'partai politik atau fraksi',
            'dapil' => 'dapil',
            'kecamatan' => 'kecamatan asal',
            'dprd_region_id' => 'wilayah aspirasi',
            'staff_id' => 'staf pendamping',
            'komisi' => 'komisi',
            'is_active' => 'status anggota',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'staff_id.exists' => 'Staf pendamping yang dipilih tidak tersedia atau sudah tidak aktif.',
            'staff_id.unique' => 'Staf pendamping tersebut sudah ditugaskan ke anggota DPRD lain.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'kode' => Str::upper(trim((string) $this->input('kode'))),
            'nama_lengkap' => trim((string) $this->input('nama_lengkap')),
            'jabatan' => trim((string) $this->input('jabatan')),
            'partai' => trim((string) $this->input('partai')),
            'dapil' => trim((string) $this->input('dapil')) ?: null,
            'kecamatan' => trim((string) $this->input('kecamatan')) ?: null,
            'dprd_region_id' => $this->filled('dprd_region_id') ? (int) $this->input('dprd_region_id') : null,
            'staff_id' => $this->filled('staff_id') ? (int) $this->input('staff_id') : null,
            'komisi' => trim((string) $this->input('komisi')) ?: null,
        ]);
    }
}
