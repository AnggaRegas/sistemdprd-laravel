<?php

namespace Database\Factories;

use App\Models\KamusAcuanEntri;
use App\Models\KamusAcuanVersi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KamusAcuanEntri>
 */
class KamusAcuanEntriFactory extends Factory
{
    protected $model = KamusAcuanEntri::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $klasifikasi = fake()->randomElement([
            'Infrastruktur jalan dan drainase',
            'Pelayanan publik dan administrasi kependudukan',
            'Pendidikan dan kesehatan masyarakat',
        ]);

        return [
            'kamus_acuan_versi_id' => KamusAcuanVersi::factory(),
            'dprd_commission_id' => null,
            'kode' => 'KOM-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'klasifikasi' => $klasifikasi,
            'uraian' => fake()->paragraph(),
            'kata_kunci' => [
                ['term' => 'jalan rusak', 'weight' => 3],
                ['term' => 'drainase', 'weight' => 2],
            ],
            'sinonim' => [],
            'kriteria' => fake()->sentence(8),
            'prioritas' => null,
            'tindak_lanjut' => null,
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
