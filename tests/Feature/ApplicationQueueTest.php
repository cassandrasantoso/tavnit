<?php

use App\Enums\ApplicationState;
use App\Models\PartnerApplication;
use App\Models\User;

test('app users who are not staff cannot see the queue', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.applications.index'))
        ->assertForbidden();
});

test('the queue shows submitted applications, oldest first', function () {
    PartnerApplication::factory()->create(['store_name' => '新しいカフェ', 'created_at' => now()->subDay()]);
    PartnerApplication::factory()->create(['store_name' => '古いベーカリー', 'created_at' => now()->subDays(5)]);

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.applications.index'))
        ->assertOk()
        ->assertSeeInOrder(['古いベーカリー', '新しいカフェ']);
});

test('each tab shows only its own state', function () {
    PartnerApplication::factory()->create(['store_name' => '待ちの店']);
    PartnerApplication::factory()->create(['store_name' => '承認済みの店', 'state' => ApplicationState::Approved]);

    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->get(route('admin.applications.index'))
        ->assertSee('待ちの店')
        ->assertDontSee('承認済みの店')
        ->assertSee('Approved (1)');

    $this->actingAs($staff)
        ->get(route('admin.applications.index', ['state' => 'approved']))
        ->assertSee('承認済みの店')
        ->assertDontSee('待ちの店');
});

test('an unknown state falls back to submitted', function () {
    PartnerApplication::factory()->create(['store_name' => '待ちの店']);

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.applications.index', ['state' => 'nonsense']))
        ->assertOk()
        ->assertSee('待ちの店');
});

test('the detail page shows what the shop typed as text, not code', function () {
    $application = PartnerApplication::factory()->create([
        'description_ja' => '<script>alert(1)</script>',
    ]);

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.applications.show', $application))
        ->assertOk()
        ->assertSee($application->store_name)
        ->assertSee('<script>alert(1)</script>')
        ->assertDontSee('<script>alert(1)</script>', false);
});
