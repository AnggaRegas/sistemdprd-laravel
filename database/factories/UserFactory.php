<?php

namespace Database\Factories;

use App\Models\DprdMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => User::ROLE_ADMINISTRATOR,
            'dprd_member_id' => null,
            'is_active' => true,
            'must_change_password' => false,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Akun administrator tanpa anggota binaan.
     */
    public function administrator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_ADMINISTRATOR,
            'dprd_member_id' => null,
        ]);
    }

    /**
     * Akun staf pendamping yang selalu terhubung ke satu anggota DPRD.
     */
    public function staff(?DprdMember $member = null): static
    {
        return $this->state(function (array $attributes) use ($member) {
            return [
                'role' => User::ROLE_STAF_PENDAMPING,
                'dprd_member_id' => $member?->id ?? DprdMember::factory()->create()->id,
            ];
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
