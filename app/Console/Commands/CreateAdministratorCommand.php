<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateAdministratorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create
        {--name= : Nama lengkap administrator}
        {--username= : Username login administrator}
        {--email= : Alamat email administrator}
        {--password= : Kata sandi administrator (kosongkan untuk prompt tersembunyi)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membuat akun administrator baru tanpa menimpa akun yang sudah ada';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = trim((string) ($this->option('name') ?: $this->ask('Nama lengkap administrator')));
        $username = trim((string) ($this->option('username') ?: $this->ask('Username administrator')));
        $email = trim((string) ($this->option('email') ?: $this->ask('Alamat email administrator')));

        $password = (string) $this->option('password');

        if ($password === '') {
            $password = (string) $this->secret('Kata sandi administrator (minimal 8 karakter)');
            $confirmation = (string) $this->secret('Ulangi kata sandi administrator');

            if ($password !== $confirmation) {
                $this->error('Konfirmasi kata sandi tidak cocok. Akun tidak dibuat.');

                return self::FAILURE;
            }
        }

        $validator = Validator::make([
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('users', 'username'),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'password' => ['required', 'string', 'min:8'],
        ], [], [
            'name' => 'nama',
            'username' => 'username',
            'email' => 'email',
            'password' => 'kata sandi',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            $this->warn('Akun administrator tidak dibuat. Periksa kembali data yang dimasukkan.');

            return self::FAILURE;
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

        $this->info('Akun administrator berhasil dibuat.');
        $this->line('Nama     : '.$user->name);
        $this->line('Username : '.$user->username);
        $this->line('Email    : '.$user->email);
        $this->line('Role     : '.$user->role_label);

        return self::SUCCESS;
    }
}
