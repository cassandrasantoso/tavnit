<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\RefillSpot;
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
        // Local test accounts only. The password for all of them is "password".
        $staff = User::factory()->create([
            'name' => 'Staff Member',
            'email' => 'staff@tavnit.test',
        ]);
        $staff->forceFill(['is_admin' => true])->save();

        User::factory()->create(['name' => 'App User One', 'email' => 'user1@tavnit.test']);
        User::factory()->create(['name' => 'App User Two', 'email' => 'user2@tavnit.test']);

        RefillSpot::factory(20)->create();
    }
}
