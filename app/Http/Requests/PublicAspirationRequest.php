<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicAspirationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $regionId = (int) $this->input('dprd_region_id');

        return [
            'pelapor_nama' => [
                'required',
                'string',
                'min:3',
                'max:150',
                'regex:/^[\p{L}\p{M}\s.,\'\-]+$/u',
            ],
            'pelapor_kontak' => [
                'required',
                'string',
                'max:150',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $value = trim((string) $value);

                    if (str_contains($value, '@')) {
                        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $fail('Format alamat email belum benar. Contoh: nama@email.com.');
                        }

                        return;
                    }

                    if (! preg_match('/^[0-9+\-().\s]{8,30}$/', $value)) {
                        $fail('Format nomor telepon belum benar. Gunakan 8–30 karakter angka, spasi, +, -, titik, atau tanda kurung.');
                    }
                },
            ],
            'dprd_region_id' => [
                'required',
                'integer',
                Rule::exists('dprd_regions', 'id')
                    ->where('is_active', true),
            ],
            'dprd_member_id' => [
                'required',
                'integer',
                Rule::exists('dprd_members', 'id')
                    ->where('is_active', true)
                    ->whereNull('deleted_at')
                    ->where('dprd_region_id', $regionId),
            ],
            'isi' => ['required', 'string', 'min:20', 'max:5000'],
            'lampiran' => [
                'nullable',
                'file',
                'max:5120',
                'mimes:pdf,jpg,jpeg,png,webp,doc,docx',
            ],
            'persetujuan' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pelapor_nama.required' => 'Nama lengkap wajib diisi.',
            'pelapor_nama.min' => 'Nama lengkap minimal 3 karakter.',
            'pelapor_nama.max' => 'Nama lengkap maksimal 150 karakter.',
            'pelapor_nama.regex' => 'Nama lengkap hanya boleh berisi huruf, spasi, titik, koma, apostrof, atau tanda hubung.',
            'pelapor_kontak.required' => 'Nomor telepon atau email wajib diisi agar petugas dapat menghubungi Anda.',
            'pelapor_kontak.max' => 'Nomor telepon atau email maksimal 150 karakter.',
            'dprd_region_id.required' => 'Kecamatan atau dapil wajib dipilih.',
            'dprd_region_id.integer' => 'Kecamatan atau dapil yang dipilih tidak valid.',
            'dprd_region_id.exists' => 'Kecamatan atau dapil yang dipilih tidak tersedia.',
            'dprd_member_id.required' => 'Anggota DPRD wajib dipilih.',
            'dprd_member_id.integer' => 'Anggota DPRD yang dipilih tidak valid.',
            'dprd_member_id.exists' => 'Anggota yang dipilih tidak aktif atau tidak terhubung dengan kecamatan/dapil tersebut.',
            'isi.required' => 'Isi aspirasi wajib diisi.',
            'isi.min' => 'Isi aspirasi minimal 20 karakter agar dapat dipahami petugas.',
            'isi.max' => 'Isi aspirasi maksimal 5.000 karakter.',
            'lampiran.file' => 'Lampiran harus berupa berkas yang valid.',
            'lampiran.max' => 'Ukuran lampiran maksimal 5 MB.',
            'lampiran.mimes' => 'Jenis lampiran harus PDF, JPG, JPEG, PNG, WEBP, DOC, atau DOCX.',
            'persetujuan.accepted' => 'Persetujuan pemrosesan data wajib dicentang sebelum aspirasi dikirim.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'pelapor_nama' => 'nama lengkap',
            'pelapor_kontak' => 'nomor telepon atau email',
            'dprd_region_id' => 'kecamatan atau dapil',
            'dprd_member_id' => 'anggota DPRD',
            'isi' => 'isi aspirasi',
            'lampiran' => 'lampiran',
            'persetujuan' => 'persetujuan pemrosesan data',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'pelapor_nama' => trim((string) $this->input('pelapor_nama')),
            'pelapor_kontak' => trim((string) $this->input('pelapor_kontak')),
            'isi' => trim((string) $this->input('isi')),
        ]);
    }
}
