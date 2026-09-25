<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (User::where('email', 'admin@webnova.edu.gh')->exists()) {
            $this->command->warn('Central admin already exists, skipping.');
            return;
        }

        User::create([
            'name' => 'Admin',
            'email' => 'admin@webnova.edu.gh',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: admin@webnova.edu.gh');
        $this->command->info('Password: password');
        $this->command->warn('IMPORTANT: Change this password after logging in!');
    }
}
