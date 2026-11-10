<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KamusUploadRequest extends FormRequest
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
                'max:'.max(1, (int) config('kamus_acuan.upload_max_kb', 20480)),
                'mimes:csv,txt,xlsx,xls,pdf',
            ],
            'mode' => ['nullable', 'string', 'in:gabung,ganti'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'file' => 'berkas Kamus Acuan',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxKb = max(1, (int) config('kamus_acuan.upload_max_kb', 20480));
        $maxLabel = $maxKb >= 1024
            ? rtrim(rtrim(number_format($maxKb / 1024, 1, ',', '.'), '0'), ',').' MB'
            : $maxKb.' KB';

        return [
            'file.required' => 'Pilih berkas Kamus Acuan yang akan diunggah.',
            'file.mimes' => 'Berkas harus berformat CSV, XLSX, XLS, atau PDF.',
            'file.max' => 'Ukuran berkas melebihi batas aplikasi '.$maxLabel.'. Batas PHP (upload_max_filesize/post_max_size) juga berlaku.',
        ];
    }
}
