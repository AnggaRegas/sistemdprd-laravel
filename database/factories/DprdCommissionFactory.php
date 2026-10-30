<?php

namespace Database\Factories;

use App\Models\DprdCommission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DprdCommission>
 */
class DprdCommissionFactory extends Factory
{
    protected $model = DprdCommission::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Komisi '.fake()->unique()->numberBetween(1, 999);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'field' => fake()->randomElement([
                'Pemerintahan, Hukum, dan Ketertiban',
                'Ekonomi, Keuangan, dan Pembangunan',
                'Kesejahteraan Rakyat dan Pendidikan',
            ]),
            'description' => fake()->sentence(12),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
