<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Tidak ada akun staf palsu yang dibuat. Administrator hanya dibuat jika
     * kredensialnya disediakan melalui environment variable.
     */
    public function run(): void
    {
        $this->call([
            KategoriArsipSeeder::class,
            AdministratorSeeder::class,
            KomisiSeeder::class,
            MemberSeeder::class,
            StaffSeeder::class,
        ]);
    }
}
