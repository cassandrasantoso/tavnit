<?php

use App\Models\Refill;
use App\Models\RefillSpot;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

// Sends a refill for this spot. 0.001 degrees of latitude is about 111 m.
function sendRefill(RefillSpot $spot, float $latitudeOffset = 0.0)
{
    return test()->postJson('/api/v1/refills', [
        'client_id' => (string) Str::uuid(),
        'refill_spot_id' => $spot->id,
        'latitude' => (float) $spot->latitude + $latitudeOffset,
        'longitude' => (float) $spot->longitude,
    ]);
}

beforeEach(function () {
    // Fixed clock: noon in Tokyo. Stored as UTC, like everything in the database.
    $this->travelTo(Carbon::parse('2026-10-12 12:00', 'Asia/Tokyo')->utc());

    $this->spot = RefillSpot::factory()->create(['latitude' => 35.658, 'longitude' => 139.701]);
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

test('a refill near the spot is confirmed', function () {
    sendRefill($this->spot, 0.0005) // about 56 m
        ->assertCreated()
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonPath('data.reason', null);
});

test('a refill more than 200 m away is rejected as too far', function () {
    sendRefill($this->spot, 0.0019) // about 211 m
        ->assertJsonPath('data.status', 'rejected')
        ->assertJsonPath('data.reason', 'too_far');
});

test('the eleventh refill of the day is rejected', function () {
    Refill::factory()->count(10)->confirmed()->create([
        'user_id' => $this->user->id,
        'logged_at' => now()->subHour(),
    ]);

    sendRefill($this->spot)
        ->assertJsonPath('data.status', 'rejected')
        ->assertJsonPath('data.reason', 'daily_limit');
});

test('a new day in Tokyo starts a new count', function () {
    Refill::factory()->count(10)->confirmed()->create([
        'user_id' => $this->user->id,
        'logged_at' => Carbon::parse('2026-10-11 23:59', 'Asia/Tokyo')->utc(),
    ]);
    $this->travelTo(Carbon::parse('2026-10-12 00:01', 'Asia/Tokyo')->utc());

    sendRefill($this->spot)->assertJsonPath('data.status', 'confirmed');
});

test('the same spot again within 5 minutes is a duplicate', function () {
    sendRefill($this->spot)->assertJsonPath('data.status', 'confirmed');
    $this->travel(3)->minutes();

    sendRefill($this->spot)->assertJsonPath('data.reason', 'duplicate');
});

test('the same spot again after 5 minutes is fine', function () {
    sendRefill($this->spot)->assertJsonPath('data.status', 'confirmed');
    $this->travel(6)->minutes();

    sendRefill($this->spot)->assertJsonPath('data.status', 'confirmed');
});
