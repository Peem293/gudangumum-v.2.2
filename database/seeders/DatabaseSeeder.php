<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Daftar 6 Role Utama sesuai rancangan sistem kita
        $roles = [
            'administrator',
            'admin_gudang',
            'staf_unit',
            'manager',
            'manager_keuangan',
            'direktur',
        ];

        $this->command->info('Creating Custom Roles...');
        foreach ($roles as $roleName) {
            // Gunakan firstOrCreate agar tidak duplikat jika seeder dijalankan ulang
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web'
            ]);
        }

        // 2. Membuat Akun Administrator Utama secara otomatis
        $this->command->info('Creating Administrator Account...');
        $admin = User::firstOrCreate(
            ['email' => 'admin@gudang.com'],
            [
                'name' => 'Administrator',
                'password' => bcrypt('password'), // Silakan ganti password ini nanti
            ]
        );

        // Pasangkan role administrator ke user ini
        $admin->assignRole('administrator');

        $this->command->info('Seeding Completed! Admin login: admin@gudang.com | password');
    }
}
