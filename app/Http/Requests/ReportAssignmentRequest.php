<?php

namespace App\Http\Requests;

use App\Models\LaporanMasyarakat;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportAssignmentRequest extends FormRequest
{
    /**
     * Error penugasan tidak menimpa error form status pada halaman detail.
     */
    protected $errorBag = 'assignment';

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
            'dprd_member_id' => [
                'nullable',
                'integer',
                Rule::exists('dprd_members', 'id')
                    ->whereNull('deleted_at')
                    ->where('is_active', true),
            ],
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')
                    ->whereNull('deleted_at')
                    ->where('role', User::ROLE_STAF_PENDAMPING)
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
            'dprd_member_id.exists' => 'Anggota DPRD yang dipilih tidak aktif atau tidak tersedia.',
            'assigned_to.exists' => 'Staf pendamping yang dipilih tidak aktif atau tidak tersedia.',
            'catatan.max' => 'Catatan penugasan maksimal 1.000 karakter.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'dprd_member_id' => 'anggota DPRD',
            'assigned_to' => 'staf pendamping',
            'catatan' => 'catatan penugasan',
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

                if ($report instanceof LaporanMasyarakat
                    && in_array($report->status, [
                        LaporanMasyarakat::STATUS_SELESAI,
                        LaporanMasyarakat::STATUS_DITOLAK,
                    ], true)) {
                    $validator->errors()->add(
                        'dprd_member_id',
                        'Laporan yang sudah selesai atau ditolak tidak dapat diubah penugasannya.'
                    );

                    return;
                }

                $memberId = $this->input('dprd_member_id');
                $staffId = $this->input('assigned_to');

                if (! $staffId) {
                    return;
                }

                if (! $memberId) {
                    $validator->errors()->add(
                        'assigned_to',
                        'Pilih anggota DPRD terlebih dahulu sebelum menugaskan staf pendamping.'
                    );

                    return;
                }

                $staff = User::query()
                    ->whereKey($staffId)
                    ->where('role', User::ROLE_STAF_PENDAMPING)
                    ->where('is_active', true)
                    ->first();

                if (! $staff || (int) $staff->dprd_member_id !== (int) $memberId) {
                    $validator->errors()->add(
                        'assigned_to',
                        'Staf yang dipilih bukan pendamping aktif dari anggota DPRD tersebut.'
                    );
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'dprd_member_id' => $this->filled('dprd_member_id')
                ? (int) $this->input('dprd_member_id')
                : null,
            'assigned_to' => $this->filled('assigned_to')
                ? (int) $this->input('assigned_to')
                : null,
            'catatan' => trim((string) $this->input('catatan')) ?: null,
        ]);
    }
}
