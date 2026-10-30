<?php

namespace App\Http\Requests;

use App\Models\RdpMeeting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RdpMeetingRequest extends FormRequest
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
        /** @var RdpMeeting|null $meeting */
        $meeting = $this->route('meeting');
        $commissionId = $this->input('dprd_commission_id');

        return [
            'dprd_commission_id' => [
                'required',
                'integer',
                Rule::exists('dprd_commissions', 'id')->where(function ($query) {
                    $query->whereNull('deleted_at');
                }),
            ],
            'title' => ['required', 'string', 'max:200'],
            'code' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('rdp_meetings', 'code')
                    ->where(function ($query) use ($commissionId) {
                        $query->where('dprd_commission_id', (int) $commissionId)
                            ->whereNull('deleted_at');
                    })
                    ->ignore($meeting?->id),
            ],
            'meeting_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'string', Rule::in(array_keys(RdpMeeting::STATUS_LABELS))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'dprd_commission_id' => 'komisi',
            'title' => 'judul atau agenda RDP',
            'code' => 'nomor atau kode RDP',
            'meeting_date' => 'tanggal pelaksanaan',
            'start_time' => 'waktu pelaksanaan',
            'location' => 'tempat pelaksanaan',
            'description' => 'deskripsi',
            'status' => 'status RDP',
            'notes' => 'catatan',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => trim((string) $this->input('title')),
            'code' => trim((string) $this->input('code')) ?: null,
            'location' => trim((string) $this->input('location')) ?: null,
            'description' => trim((string) $this->input('description')) ?: null,
            'notes' => trim((string) $this->input('notes')) ?: null,
        ]);
    }
}
