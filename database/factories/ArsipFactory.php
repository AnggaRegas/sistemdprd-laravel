<?php

namespace Database\Factories;

use App\Models\Arsip;
use App\Models\DprdMember;
use App\Models\KategoriArsip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Arsip>
 */
class ArsipFactory extends Factory
{
    protected $model = Arsip::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nomor' => 'ARS-'.fake()->unique()->numerify('######'),
            'nama' => fake()->sentence(5),
            'kategori_arsip_id' => KategoriArsip::factory(),
            'dprd_member_id' => DprdMember::factory(),
            'jenis' => fake()->randomElement(array_keys(Arsip::JENIS_LABELS)),
            'status' => fake()->randomElement(array_keys(Arsip::STATUS_LABELS)),
            'tanggal' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'deskripsi' => fake()->paragraph(),
            'uploaded_by' => null,
            'verified_by' => null,
            'verified_at' => null,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Arsip::STATUS_TERVERIFIKASI,
            'verified_at' => now(),
        ]);
    }
}
