<?php

namespace Database\Factories;

use App\Models\RdpDocument;
use App\Models\RdpMeeting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RdpDocument>
 */
class RdpDocumentFactory extends Factory
{
    protected $model = RdpDocument::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(3, true).'.pdf';

        return [
            'rdp_meeting_id' => RdpMeeting::factory(),
            'title' => fake()->sentence(4),
            'category' => fake()->randomElement(array_keys(RdpDocument::CATEGORY_LABELS)),
            'document_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'description' => fake()->sentence(),
            'file_path' => 'rdp-documents/1/'.fake()->uuid().'.pdf',
            'original_name' => $name,
            'mime_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(1024, 2048000),
            'uploaded_by' => null,
        ];
    }

    public function forMeeting(RdpMeeting $meeting): static
    {
        return $this->state(fn (array $attributes) => [
            'rdp_meeting_id' => $meeting->id,
        ]);
    }
}
