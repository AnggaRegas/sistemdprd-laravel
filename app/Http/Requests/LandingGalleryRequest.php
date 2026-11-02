<?php

namespace App\Http\Requests;

use App\Models\LandingGallery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LandingGalleryRequest extends FormRequest
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
            'description' => ['nullable', 'string', 'max:5000'],
            'activity_date' => ['required', 'date', 'before_or_equal:today'],
            'location' => ['nullable', 'string', 'max:180'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'status' => [
                'required',
                'string',
                Rule::in([
                    LandingGallery::STATUS_DRAFT,
                    LandingGallery::STATUS_PUBLISHED,
                    LandingGallery::STATUS_ARCHIVED,
                ]),
            ],

            'photos' => ['nullable', 'array', 'max:50'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'captions' => ['nullable', 'array'],
            'captions.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'judul album',
            'description' => 'deskripsi album',
            'activity_date' => 'tanggal kegiatan',
            'location' => 'lokasi kegiatan',
            'cover_image' => 'foto sampul',
            'status' => 'status publikasi',
            'photos' => 'foto kegiatan',
            'photos.*' => 'foto kegiatan',
            'captions.*' => 'caption foto',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => trim((string) $this->input('title')),
            'description' => trim((string) $this->input('description')),
            'location' => trim((string) $this->input('location')),
            'status' => (string) $this->input('status', LandingGallery::STATUS_DRAFT),
            'activity_date' => trim((string) $this->input('activity_date')) ?: null,
        ]);
    }
}
