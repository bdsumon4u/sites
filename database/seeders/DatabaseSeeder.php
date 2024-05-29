<?php

namespace Database\Seeders;

use App\Models\Site;
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
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Cyber 32',
            'email' => 'support@cyber32.com',
        ]);

        Site::create([
            'uname' => 'cyber32n',
            'domain' => 'scom1.cyber32.net',
            'directory' => 'scom1.cyber32.net',
            'status' => 'Active',
        ]);
    }
}
