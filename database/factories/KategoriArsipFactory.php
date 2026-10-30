<?php

namespace Database\Factories;

use App\Models\KategoriArsip;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KategoriArsip>
 */
class KategoriArsipFactory extends Factory
{
    protected $model = KategoriArsip::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nama = Str::title(fake()->unique()->words(2, true));

        return [
            'nama' => $nama,
            'kode' => Str::upper(fake()->unique()->lexify('???')),
            'deskripsi' => fake()->sentence(),
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
