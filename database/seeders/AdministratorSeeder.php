<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;

class AdministratorSeeder extends Seeder
{
    /**
     * Membuat administrator awal hanya jika kredensial disediakan lewat
     * environment variable. Tidak ada kata sandi default dan pengguna yang
     * sudah ada tidak pernah ditimpa.
     */
    public function run(): void
    {
        $name = trim((string) env('DPRD_ADMIN_NAME', ''));
        $username = trim((string) env('DPRD_ADMIN_USERNAME', ''));
        $email = trim((string) env('DPRD_ADMIN_EMAIL', ''));
        $password = (string) env('DPRD_ADMIN_PASSWORD', '');

        if ($name === '' || $username === '' || $email === '' || $password === '') {
            $this->command?->warn(
                'AdministratorSeeder dilewati: set DPRD_ADMIN_NAME, DPRD_ADMIN_USERNAME, DPRD_ADMIN_EMAIL, dan DPRD_ADMIN_PASSWORD untuk membuat administrator awal.'
            );

            return;
        }

        $validator = Validator::make([
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            $this->command?->warn(
                'AdministratorSeeder dilewati: kredensial administrator tidak valid. '.$validator->errors()->first()
            );

            return;
        }

        $existing = User::query()
            ->where('username', $username)
            ->orWhere('email', $email)
            ->exists();

        if ($existing) {
            $this->command?->warn(
                'AdministratorSeeder dilewati: username atau email sudah digunakan. Akun yang ada tidak diubah.'
            );

            return;
        }

        $user = new User();
        $user->forceFill([
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'role' => User::ROLE_ADMINISTRATOR,
            'dprd_member_id' => null,
            'email_verified_at' => now(),
        ])->save();

        $this->command?->info('Administrator awal berhasil dibuat melalui environment variable.');
    }
}
