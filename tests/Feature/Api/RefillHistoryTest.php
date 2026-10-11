<?php

use App\Models\Refill;
use App\Models\RefillSpot;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

test('history shows only my refills, newest first', function () {
    $spot = RefillSpot::factory()->create(['name' => '代々木の公園']);
    Refill::factory()->for($this->user)->for($spot, 'spot')->create(['logged_at' => now()->subDay()]);
    $newest = Refill::factory()->for($this->user)->for($spot, 'spot')->create(['logged_at' => now()]);
    Refill::factory()->create(); // someone else's

    $this->getJson('/api/v1/refills')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $newest->id)
        ->assertJsonPath('data.0.spot_name', '代々木の公園');
});

test('I can open my own refill', function () {
    $refill = Refill::factory()->for($this->user)->create();

    $this->getJson("/api/v1/refills/{$refill->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $refill->id);
});

test("someone else's refill looks like it doesn't exist", function () {
    $theirs = Refill::factory()->create();

    $this->getJson("/api/v1/refills/{$theirs->id}")->assertNotFound();
});

test('stats count only confirmed refills', function () {
    Refill::factory()->count(3)->confirmed()->for($this->user)->create();
    Refill::factory()->for($this->user)->create([
        'auto_status' => 'rejected',
        'status' => 'rejected',
        'auto_reason' => 'too_far',
    ]);

    $this->getJson('/api/v1/me/stats')
        ->assertOk()
        ->assertJsonPath('data.confirmed_refills', 3)
        ->assertJsonPath('data.impact.value', 3)
        ->assertJsonPath('data.impact.label', 'bottles saved');
});
