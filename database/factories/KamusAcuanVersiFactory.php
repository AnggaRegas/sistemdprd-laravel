<?php

namespace Database\Factories;

use App\Models\KamusAcuanVersi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KamusAcuanVersi>
 */
class KamusAcuanVersiFactory extends Factory
{
    protected $model = KamusAcuanVersi::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tahun = fake()->unique()->numberBetween(2020, 2040);

        return [
            'tahun' => $tahun,
            'nama' => 'Kamus Acuan '.$tahun,
            'keterangan' => fake()->sentence(10),
            'is_active' => false,
            'ditetapkan_oleh' => null,
            'ditetapkan_at' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'ditetapkan_at' => now(),
        ]);
    }
}
