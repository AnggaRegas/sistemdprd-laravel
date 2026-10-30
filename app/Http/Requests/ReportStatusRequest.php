<?php

namespace App\Http\Requests;

use App\Models\LaporanMasyarakat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportStatusRequest extends FormRequest
{
    /**
     * Error status tidak menimpa error form penugasan pada halaman detail.
     */
    protected $errorBag = 'status';

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
            'status' => [
                'required',
                'string',
                Rule::in(array_keys(LaporanMasyarakat::STATUS_LABELS)),
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
            'status.required' => 'Status laporan wajib dipilih.',
            'status.in' => 'Status laporan yang dipilih tidak tersedia.',
            'catatan.max' => 'Catatan tindak lanjut maksimal 2.000 karakter.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => 'status laporan',
            'catatan' => 'catatan tindak lanjut',
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function ($validator): void {
                $report = $this->route('laporan');

                if (! $report instanceof LaporanMasyarakat) {
                    return;
                }

                $status = (string) $this->input('status');

                if ($status !== '' && ! $report->canTransitionTo($status)) {
                    $from = $report->status_label;
                    $to = LaporanMasyarakat::STATUS_LABELS[$status] ?? $status;

                    $validator->errors()->add(
                        'status',
                        'Perubahan status dari '.$from.' ke '.$to.' tidak diizinkan.'
                    );
                }
            },
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
