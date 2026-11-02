<?php

namespace App\Http\Requests;

use App\Models\LandingNews;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LandingNewsRequest extends FormRequest
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
        /** @var LandingNews|null $news */
        $news = $this->route('news');

        return [
            'title' => ['required', 'string', 'max:180'],

            'category' => [
                'nullable',
                'string',
                Rule::in(array_keys(LandingNews::CATEGORIES)),
            ],

            'excerpt' => ['nullable', 'string', 'max:500'],

            // Isi disimpan sebagai teks biasa; Blade meng-escape output sehingga
            // bebas dari celah XSS rich text.
            'content' => ['required', 'string', 'max:50000'],

            'thumbnail' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],

            'published_at' => ['nullable', 'date', 'before_or_equal:now'],

            'status' => [
                'required',
                'string',
                Rule::in([LandingNews::STATUS_DRAFT, LandingNews::STATUS_PUBLISHED, LandingNews::STATUS_ARCHIVED]),
            ],
        ];
    }

    /**
     * Berita yang dipublikasikan wajib memiliki gambar utama: unggahan baru
     * pada form ini atau thumbnail yang tersimpan sebelumnya (mode edit).
     */
    public function withValidator($validator): void
    {
        /** @var LandingNews|null $news */
        $news = $this->route('news');

        $validator->after(function ($validator) use ($news) {
            if ($this->input('status') !== LandingNews::STATUS_PUBLISHED) {
                return;
            }

            if (! $this->hasFile('thumbnail') && ! ($news?->thumbnail)) {
                $validator->errors()->add(
                    'thumbnail',
                    'Gambar utama wajib diunggah sebelum berita dipublikasikan.'
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'judul berita',
            'category' => 'kategori',
            'excerpt' => 'ringkasan',
            'content' => 'isi berita',
            'thumbnail' => 'gambar utama',
            'published_at' => 'tanggal publikasi',
            'status' => 'status publikasi',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => trim((string) $this->input('title')),
            'category' => trim((string) $this->input('category')) ?: null,
            'excerpt' => trim((string) $this->input('excerpt')),
            'content' => trim((string) $this->input('content')),
            'status' => (string) $this->input('status', LandingNews::STATUS_DRAFT),
            'published_at' => trim((string) $this->input('published_at')) ?: null,
        ]);
    }
}
