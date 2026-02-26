<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Default admin user
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'username' => 'admin',
                'password' => Hash::make('Admin123!'),
                'role' => 'admin',
            ],
        );

        // Demo admin user (for testing)
        User::updateOrCreate(
            ['email' => 'admin@demo.com'],
            [
                'name' => 'John Doe',
                'username' => 'johndoe',
                'password' => Hash::make('admin'),
                'role' => 'admin',
            ],
        );

        // Demo client user (for testing)
        User::updateOrCreate(
            ['email' => 'client@demo.com'],
            [
                'name' => 'Jane Doe',
                'username' => 'janedoe',
                'password' => Hash::make('client'),
                'role' => 'client',
            ],
        );
    }
}
