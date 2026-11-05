<?php

use App\Models\PartnerApplication;
use App\Models\RefillSpot;
use App\Models\User;
use App\Services\DuplicateSpotFinder;

// 0.00018 degrees of latitude is about 20 m.
beforeEach(function () {
    $this->spot = RefillSpot::factory()->create([
        'name' => '渋谷のカフェ',
        'latitude' => 35.658000,
        'longitude' => 139.701000,
    ]);
});

function applicationAt(string $name, float $latitude, float $longitude = 139.701000): PartnerApplication
{
    return PartnerApplication::factory()->create([
        'store_name' => $name,
        'latitude' => $latitude,
        'longitude' => $longitude,
    ]);
}

test('names are tidied before comparing', function () {
    expect(DuplicateSpotFinder::normaliseName('ｶﾌｪ ﾐｽﾞ ＡＢＣ　'))->toBe('カフェミズabc');
});

test('the same name with a full-width space about 20 m away is flagged', function () {
    $matches = app(DuplicateSpotFinder::class)->findFor(applicationAt('渋谷のカフェ　', 35.658180));

    expect($matches)->toHaveCount(1)
        ->and($matches[0]['spot']->is($this->spot))->toBeTrue()
        ->and($matches[0]['distance_m'])->toBe(20);
});

test('half-width katakana and full-width letters still match', function () {
    $spot = RefillSpot::factory()->create([
        'name' => 'カフェ ミズ ABC',
        'latitude' => 35.658000,
        'longitude' => 139.701000,
    ]);

    $matches = app(DuplicateSpotFinder::class)->findFor(applicationAt('ｶﾌｪﾐｽﾞ ＡＢＣ', 35.658090));

    expect($matches)->toHaveCount(1)
        ->and($matches[0]['spot']->is($spot))->toBeTrue();
});

test('49 m away is flagged, 51 m away is not', function () {
    $finder = app(DuplicateSpotFinder::class);

    expect($finder->findFor(applicationAt('渋谷のカフェ', 35.658440)))->toHaveCount(1)
        ->and($finder->findFor(applicationAt('渋谷のカフェ', 35.658460)))->toBeEmpty();
});

test('a different name nearby is not flagged', function () {
    expect(app(DuplicateSpotFinder::class)->findFor(applicationAt('原宿の本屋', 35.658180)))->toBeEmpty();
});

test('removed spots are not flagged', function () {
    $this->spot->delete();

    expect(app(DuplicateSpotFinder::class)->findFor(applicationAt('渋谷のカフェ', 35.658180)))->toBeEmpty();
});

test('the detail page shows the warning with the spot and distance', function () {
    $application = applicationAt('渋谷のカフェ　', 35.658180);

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.applications.show', $application))
        ->assertOk()
        ->assertSee('Possible duplicate')
        ->assertSee(route('admin.refill-spots.edit', $this->spot))
        ->assertSee('20 m away');
});

test('no warning when there is no match', function () {
    $application = applicationAt('原宿の本屋', 35.658180);

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.applications.show', $application))
        ->assertOk()
        ->assertDontSee('Possible duplicate');
});
