<?php

use App\Models\Refill;
use App\Models\RefillSpot;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

// Login and logout

test('app users get a token with the right password', function () {
    $user = User::factory()->create();

    $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'test phone',
    ])
        ->assertCreated()
        ->assertJsonStructure(['token']);
});

test('a wrong password gets no token', function () {
    $user = User::factory()->create();

    $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'wrong',
        'device_name' => 'test phone',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    expect($user->tokens()->count())->toBe(0);
});

test('logging out deletes the token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test phone')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/logout')
        ->assertNoContent();

    expect($user->tokens()->count())->toBe(0);
});

// Spots

test('the spots list needs a token', function () {
    $this->getJson('/api/v1/spots')->assertUnauthorized();
});

test('the spots list shows only active spots', function () {
    RefillSpot::factory()->create(['name' => '表参道のカフェ']);
    RefillSpot::factory()->create(['name' => '休止中のカフェ', 'is_active' => false]);
    RefillSpot::factory()->create(['name' => '閉店したカフェ'])->delete();

    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/spots')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', '表参道のカフェ');
});

// Refills

test('an app user can send a refill at a spot', function () {
    $user = User::factory()->create();
    $spot = RefillSpot::factory()->create(['latitude' => 35.6595, 'longitude' => 139.7005]);
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/refills', [
        'client_id' => (string) Str::uuid(),
        'refill_spot_id' => $spot->id,
        'latitude' => 35.6595,
        'longitude' => 139.7005,
    ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'confirmed');

    expect($user->refills()->count())->toBe(1);
});

test('sending the same refill twice only saves it once', function () {
    $spot = RefillSpot::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $payload = [
        'client_id' => (string) Str::uuid(),
        'refill_spot_id' => $spot->id,
        'latitude' => 35.6595,
        'longitude' => 139.7005,
    ];

    $first = $this->postJson('/api/v1/refills', $payload)->assertCreated();
    $this->postJson('/api/v1/refills', $payload)
        ->assertOk()
        ->assertJsonPath('data.id', $first->json('data.id'));

    expect(Refill::count())->toBe(1);
});

test('a refill at an inactive spot is refused', function () {
    $spot = RefillSpot::factory()->create(['is_active' => false]);
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/refills', [
        'client_id' => (string) Str::uuid(),
        'refill_spot_id' => $spot->id,
        'latitude' => 35.6595,
        'longitude' => 139.7005,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('refill_spot_id');
});

test('a refill needs a real client id and a location', function () {
    $spot = RefillSpot::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/refills', [
        'client_id' => 'not-a-uuid',
        'refill_spot_id' => $spot->id,
        'latitude' => 200,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['client_id', 'latitude', 'longitude']);
});

test('history and stats need a token', function () {
    $this->getJson('/api/v1/refills')->assertUnauthorized();
    $this->getJson('/api/v1/me/stats')->assertUnauthorized();
});

test('the API slows down a user who sends too many requests', function () {
    Sanctum::actingAs(User::factory()->create());

    foreach (range(1, 60) as $i) {
        $this->getJson('/api/v1/me')->assertOk();
    }

    $this->getJson('/api/v1/me')->assertTooManyRequests();
});
