<?php

namespace Database\Factories;

use App\Models\DprdCommission;
use App\Models\RdpMeeting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RdpMeeting>
 */
class RdpMeetingFactory extends Factory
{
    protected $model = RdpMeeting::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dprd_commission_id' => DprdCommission::factory(),
            'title' => fake()->sentence(6),
            'code' => 'RDP-'.Str::upper(fake()->unique()->bothify('####-????')),
            'meeting_date' => fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d'),
            'start_time' => fake()->time('H:i'),
            'location' => 'Ruang Rapat '.fake()->randomElement(['Komisi 1', 'Komisi 2', 'Komisi 3']),
            'description' => fake()->paragraph(),
            'status' => RdpMeeting::STATUS_DRAF,
            'notes' => null,
            'created_by' => null,
        ];
    }

    public function forCommission(DprdCommission $commission): static
    {
        return $this->state(fn (array $attributes) => [
            'dprd_commission_id' => $commission->id,
        ]);
    }

    public function status(string $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
