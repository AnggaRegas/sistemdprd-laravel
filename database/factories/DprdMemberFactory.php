<?php

namespace Database\Factories;

use App\Models\DprdMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DprdMember>
 */
class DprdMemberFactory extends Factory
{
    protected $model = DprdMember::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->numerify('DWN-###'),
            'nama_lengkap' => fake()->name(),
            'jabatan' => fake()->randomElement([
                'Ketua DPRD',
                'Wakil Ketua DPRD',
                'Ketua Komisi I',
                'Ketua Komisi II',
                'Anggota Komisi III',
            ]),
            'partai' => fake()->randomElement([
                'Partai Golkar',
                'Partai NasDem',
                'PDI Perjuangan',
                'Partai Gerindra',
                'Partai Demokrat',
            ]),
            'dapil' => fake()->randomElement([
                'Dapil Bacukiki',
                'Dapil Bacukiki Barat',
                'Dapil Ujung',
                'Dapil Soreang',
            ]),
            'kecamatan' => fake()->randomElement([
                'Kecamatan Bacukiki',
                'Kecamatan Bacukiki Barat',
                'Kecamatan Ujung',
                'Kecamatan Soreang',
            ]),
            'komisi' => fake()->randomElement([
                'Pimpinan DPRD',
                'Komisi I',
                'Komisi II',
                'Komisi III',
            ]),
            'email' => fake()->unique()->safeEmail(),
            'telepon' => fake()->numerify('08##########'),
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
