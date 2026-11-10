<?php

use App\Models\PartnerApplication;
use App\Models\User;
use Illuminate\Support\Facades\App;

test('the shop type is shown in the page language', function () {
    $application = PartnerApplication::factory()->make(['store_type' => 'bakery']);

    expect($application->storeTypeLabel())->toBe('Bakery');

    App::setLocale('ja');

    expect($application->storeTypeLabel())->toBe('ベーカリー');
});

test('a type with no wording yet is shown as stored', function () {
    $application = PartnerApplication::factory()->make(['store_type' => 'library']);

    expect($application->storeTypeLabel())->toBe('library');
});

test('the queue shows the type in words, not the code', function () {
    PartnerApplication::factory()->create(['store_type' => 'hotel']);

    $this->actingAs(User::factory()->staff()->create(['locale' => 'ja']))
        ->get(route('admin.applications.index'))
        ->assertSee('ホテル・ゲストハウス');
});
