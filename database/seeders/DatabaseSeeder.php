<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create default admin user
        User::create([
            'name' => 'Admin TambaQ',
            'email' => 'admin@tambaq.com',
            'phone_number' => '081234567890',
            'password' => bcrypt('password123'),
        ]);

        // Seeder untuk TambaQ
        $this->call([
            ActuatorSeeder::class,
            SensorReadingSeeder::class,
            FuzzyDecisionSeeder::class,
        ]);
    }
}
