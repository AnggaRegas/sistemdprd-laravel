<?php

namespace App\Http\Requests;

use App\Models\LandingAgenda;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LandingAgendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-landing-page') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:10000'],
            'event_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after_or_equal:start_time'],
            'location' => ['required', 'string', 'max:180'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'event_status' => [
                'required',
                'string',
                Rule::in(array_keys(LandingAgenda::EVENT_STATUSES)),
            ],
            'status' => [
                'required',
                'string',
                Rule::in([
                    LandingAgenda::STATUS_DRAFT,
                    LandingAgenda::STATUS_PUBLISHED,
                    LandingAgenda::STATUS_ARCHIVED,
                ]),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'judul kegiatan',
            'description' => 'deskripsi kegiatan',
            'event_date' => 'tanggal kegiatan',
            'start_time' => 'waktu mulai',
            'end_time' => 'waktu selesai',
            'location' => 'lokasi kegiatan',
            'image' => 'gambar kegiatan',
            'event_status' => 'status kegiatan',
            'status' => 'status publikasi',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'end_time.after_or_equal' => 'Waktu selesai tidak boleh lebih awal daripada waktu mulai.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => trim((string) $this->input('title')),
            'description' => trim((string) $this->input('description')),
            'location' => trim((string) $this->input('location')),
            'event_date' => trim((string) $this->input('event_date')) ?: null,
            'start_time' => trim((string) $this->input('start_time')),
            'end_time' => trim((string) $this->input('end_time')),
            'event_status' => (string) $this->input('event_status', LandingAgenda::EVENT_UPCOMING),
            'status' => (string) $this->input('status', LandingAgenda::STATUS_DRAFT),
        ]);
    }
}
