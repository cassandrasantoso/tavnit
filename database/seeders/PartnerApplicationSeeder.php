<?php

namespace Database\Seeders;

use App\Models\PartnerApplication;
use App\Models\RefillSpot;
use Illuminate\Database\Seeder;

class PartnerApplicationSeeder extends Seeder
{
    public function run(): void
    {
        PartnerApplication::factory(9)->create();

        // One near-copy of an existing spot: same name plus a full-width space,
        // about 20 m away. The duplicate check (later step) should flag it.
        $spot = RefillSpot::first();

        if ($spot) {
            PartnerApplication::factory()->create([
                'store_name' => $spot->name.'　',
                'latitude' => $spot->latitude + 0.00018,
                'longitude' => $spot->longitude,
            ]);
        }
    }
}
