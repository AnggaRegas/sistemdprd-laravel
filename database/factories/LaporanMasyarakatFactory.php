<?php

namespace Database\Factories;

use App\Models\DprdMember;
use App\Models\LaporanMasyarakat;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LaporanMasyarakat>
 */
class LaporanMasyarakatFactory extends Factory
{
    protected $model = LaporanMasyarakat::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nomor_tiket' => 'LPR-'.fake()->unique()->numerify('######'),
            'pelapor_nama' => fake()->name(),
            'pelapor_kontak' => fake()->numerify('08##########'),
            'pelapor_alamat' => fake()->address(),
            'ringkasan' => fake()->sentence(7),
            'isi' => fake()->paragraph(),
            'kategori' => fake()->randomElement(LaporanMasyarakat::KATEGORI_OPTIONS),
            'kecamatan' => fake()->randomElement(LaporanMasyarakat::KECAMATAN_OPTIONS),
            'lampiran_path' => null,
            'lampiran_nama' => null,
            'lampiran_mime' => null,
            'lampiran_size' => null,
            'dprd_member_id' => DprdMember::factory(),
            'assigned_to' => null,
            'assigned_at' => null,
            'status' => LaporanMasyarakat::STATUS_BARU,
            'tanggal_laporan' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'ditutup_at' => null,
            'catatan_penyelesaian' => null,
        ];
    }

    public function assignedTo(User $staff): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_to' => $staff->id,
            'assigned_at' => now(),
            'status' => LaporanMasyarakat::STATUS_DIPROSES,
        ]);
    }

    public function status(string $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
