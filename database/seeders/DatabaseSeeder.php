<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        // Seed categories first (needed for admin/jobdesk assignment)
        $this->call([\Database\Seeders\CategorySeeder::class]);

        // Seed example users (super_admin, admin, agent, customer)
        $this->call([UserSeeder::class]);

        // Keep a simple test user as convenience
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => bcrypt('password'), 'role' => 'customer', 'email_verified_at' => now()]
        );
    }
}
