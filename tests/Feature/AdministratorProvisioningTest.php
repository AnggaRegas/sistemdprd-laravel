<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdministratorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdministratorProvisioningTest extends TestCase
{
    use RefreshDatabase;

    private const ENV_KEYS = [
        'DPRD_ADMIN_NAME',
        'DPRD_ADMIN_USERNAME',
        'DPRD_ADMIN_EMAIL',
        'DPRD_ADMIN_PASSWORD',
    ];

    protected function tearDown(): void
    {
        $this->clearAdministratorEnvironment();

        parent::tearDown();
    }

    public function test_command_creates_an_administrator_with_a_hashed_password(): void
    {
        $this->artisan('admin:create', [
            '--name' => 'Admin Baru',
            '--username' => 'admin-baru',
            '--email' => 'admin-baru@example.test',
            '--password' => 'rahasia-sekali',
        ])->assertExitCode(0);

        $user = User::query()->where('username', 'admin-baru')->first();

        $this->assertNotNull($user);
        $this->assertSame(User::ROLE_ADMINISTRATOR, $user->role);
        $this->assertNull($user->dprd_member_id);
        $this->assertTrue(Hash::check('rahasia-sekali', $user->password));
    }

    public function test_command_does_not_overwrite_an_existing_user(): void
    {
        $existing = User::factory()->administrator()->create([
            'name' => 'Admin Lama',
            'username' => 'admin-lama',
            'email' => 'admin-lama@example.test',
        ]);

        $this->artisan('admin:create', [
            '--name' => 'Admin Pengganti',
            '--username' => 'admin-lama',
            '--email' => 'admin-baru@example.test',
            '--password' => 'rahasia-sekali',
        ])->assertExitCode(1);

        $existing->refresh();

        $this->assertSame('Admin Lama', $existing->name);
        $this->assertSame('admin-lama@example.test', $existing->email);
        $this->assertSame(1, User::query()->where('username', 'admin-lama')->count());
    }

    public function test_command_rejects_a_short_password(): void
    {
        $this->artisan('admin:create', [
            '--name' => 'Admin Baru',
            '--username' => 'admin-baru',
            '--email' => 'admin-baru@example.test',
            '--password' => 'pendek',
        ])->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['username' => 'admin-baru']);
    }

    public function test_seeder_creates_an_administrator_from_environment(): void
    {
        $this->setAdministratorEnvironment([
            'DPRD_ADMIN_NAME' => 'Admin Seeder',
            'DPRD_ADMIN_USERNAME' => 'admin-seeder',
            'DPRD_ADMIN_EMAIL' => 'admin-seeder@example.test',
            'DPRD_ADMIN_PASSWORD' => 'rahasia-seeder',
        ]);

        $this->seed(AdministratorSeeder::class);

        $user = User::query()->where('username', 'admin-seeder')->first();

        $this->assertNotNull($user);
        $this->assertSame(User::ROLE_ADMINISTRATOR, $user->role);
        $this->assertTrue(Hash::check('rahasia-seeder', $user->password));
    }

    public function test_seeder_does_not_overwrite_an_existing_user(): void
    {
        $existing = User::factory()->administrator()->create([
            'name' => 'Admin Lama',
            'username' => 'admin-seeder',
            'email' => 'admin-seeder@example.test',
        ]);

        $this->setAdministratorEnvironment([
            'DPRD_ADMIN_NAME' => 'Admin Baru',
            'DPRD_ADMIN_USERNAME' => 'admin-seeder',
            'DPRD_ADMIN_EMAIL' => 'admin-seeder@example.test',
            'DPRD_ADMIN_PASSWORD' => 'rahasia-seeder',
        ]);

        $this->seed(AdministratorSeeder::class);

        $existing->refresh();

        $this->assertSame('Admin Lama', $existing->name);
        $this->assertSame(1, User::query()->where('username', 'admin-seeder')->count());
    }

    public function test_seeder_is_skipped_without_environment_credentials(): void
    {
        $this->clearAdministratorEnvironment();

        $this->seed(AdministratorSeeder::class);

        $this->assertSame(0, User::query()->count());
    }

    /**
     * @param  array<string, string>  $values
     */
    private function setAdministratorEnvironment(array $values): void
    {
        foreach ($values as $key => $value) {
            putenv($key.'='.$value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    private function clearAdministratorEnvironment(): void
    {
        foreach (self::ENV_KEYS as $key) {
            putenv($key.'=');
            unset($_ENV[$key], $_SERVER[$key]);
        }
    }
}
