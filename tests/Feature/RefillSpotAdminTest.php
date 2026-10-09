<?php

use App\Models\RefillSpot;
use App\Models\User;

// Who can get in

test('guests are sent to the login page', function () {
    $this->get(route('admin.refill-spots.index'))
        ->assertRedirect(route('login'));
});

test('app users who are not staff get 403', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.refill-spots.index'))
        ->assertForbidden();
});

test('staff can log in and land on the spots list', function () {
    $staff = User::factory()->staff()->create();

    $this->post(route('login'), ['email' => $staff->email, 'password' => 'password'])
        ->assertRedirect(route('admin.refill-spots.index'));

    $this->assertAuthenticatedAs($staff);
});

test('app users cannot log in to the admin', function () {
    $user = User::factory()->create();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('staff can log out', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

// What staff can do

test('staff see every spot, removed ones included', function () {
    RefillSpot::factory()->create(['name' => '渋谷のカフェ']);
    RefillSpot::factory()->create(['name' => '原宿の本屋'])->delete();

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.refill-spots.index'))
        ->assertOk()
        ->assertSee('渋谷のカフェ')
        ->assertSee('原宿の本屋')
        ->assertSee('Restore');
});

test('staff can add a spot', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->post(route('admin.refill-spots.store'), [
            'name' => '恵比寿のベーカリー',
            'latitude' => '35.646',
            'longitude' => '139.710',
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.refill-spots.index'));

    $this->assertDatabaseHas('refill_spots', ['name' => '恵比寿のベーカリー', 'is_active' => true]);
});

test('a spot needs a name and a valid location', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->post(route('admin.refill-spots.store'), [
            'name' => '',
            'latitude' => '200',
            'longitude' => 'east',
        ])
        ->assertSessionHasErrors(['name', 'latitude', 'longitude']);

    $this->assertDatabaseCount('refill_spots', 0);
});

test('unticking "Show in the app" makes a spot inactive', function () {
    $spot = RefillSpot::factory()->create(['is_active' => true]);

    $this->actingAs(User::factory()->staff()->create())
        ->put(route('admin.refill-spots.update', $spot), [
            'name' => $spot->name,
            'latitude' => $spot->latitude,
            'longitude' => $spot->longitude,
            'is_active' => '0',
        ])
        ->assertRedirect(route('admin.refill-spots.index'));

    expect($spot->fresh()->is_active)->toBeFalse();
});

test('removing a spot keeps the row', function () {
    $spot = RefillSpot::factory()->create();

    $this->actingAs(User::factory()->staff()->create())
        ->delete(route('admin.refill-spots.destroy', $spot));

    $this->assertSoftDeleted($spot);
});

test('staff can restore a removed spot', function () {
    $spot = RefillSpot::factory()->create();
    $spot->delete();

    $this->actingAs(User::factory()->staff()->create())
        ->post(route('admin.refill-spots.restore', $spot));

    $this->assertNotSoftDeleted($spot);
});

test('spot names are shown as text, not run as code', function () {
    RefillSpot::factory()->create(['name' => '<script>alert(1)</script>']);

    // false = compare the raw HTML instead of escaping our search text first.
    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.refill-spots.index'))
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
});
