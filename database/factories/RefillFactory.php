<?php

namespace Database\Factories;

use App\Models\Refill;
use App\Models\RefillSpot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Refill>
 */
class RefillFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'refill_spot_id' => RefillSpot::factory(),
            'client_id' => fake()->uuid(),
            'latitude' => 35.6595,
            'longitude' => 139.7005,
            'logged_at' => now(),
        ];
    }

    // A refill that already passed the checks.
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'auto_status' => 'confirmed',
            'status' => 'confirmed',
        ]);
    }
}
