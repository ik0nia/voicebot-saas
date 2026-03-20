<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $password = Str::password(24);

        $admin = User::firstOrCreate(
            ['email' => 'admin@sambla.ro'],
            [
                'name' => 'Super Admin',
                'password' => $password,
                'email_verified_at' => now(),
            ]
        );

        $admin->assignRole('super_admin');

        if ($admin->wasRecentlyCreated) {
            $this->command->info('');
            $this->command->info('========================================');
            $this->command->info('  SUPER ADMIN CREATED');
            $this->command->info('  Email:    admin@sambla.ro');
            $this->command->info("  Password: {$password}");
            $this->command->info('  SAVE THIS PASSWORD - shown only once!');
            $this->command->info('========================================');
            $this->command->info('');
        } else {
            $this->command->info('Super admin already exists, skipping password reset.');
        }
    }
}
