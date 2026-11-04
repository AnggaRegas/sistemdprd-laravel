<?php

namespace App\Http\Requests;

use App\Models\LaporanMasyarakat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaffReportStatusRequest extends FormRequest
{
    protected $errorBag = 'status';

    public function authorize(): bool
    {
        $report = $this->route('laporan');

        return ($this->user()?->isStaff() ?? false)
            && $report instanceof LaporanMasyarakat
            && (int) $report->assigned_to === (int) $this->user()->id
            && (int) $report->dprd_member_id === (int) $this->user()->dprd_member_id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in([
                    LaporanMasyarakat::STATUS_DIPROSES,
                    LaporanMasyarakat::STATUS_SELESAI,
                ]),
            ],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Status tindak lanjut wajib dipilih.',
            'status.in' => 'Staf pendamping hanya dapat memilih status Diproses atau Selesai.',
            'catatan.max' => 'Catatan tindak lanjut maksimal 2.000 karakter.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => trim((string) $this->input('status')),
            'catatan' => trim((string) $this->input('catatan')) ?: null,
        ]);
    }
}
