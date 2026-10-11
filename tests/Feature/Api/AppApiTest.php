<?php

use App\Models\RefillSpot;
use App\Models\User;
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
