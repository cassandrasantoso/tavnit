<?php

use App\Enums\ApplicationState;
use App\Models\PartnerApplication;
use App\Models\RefillSpot;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

// An approved application (claim and approval moved it to version 3).
function approvedApplication(array $attributes = []): PartnerApplication
{
    $application = PartnerApplication::factory()->create($attributes);
    $application->forceFill(['state' => ApplicationState::Approved, 'version' => 3])->save();

    return $application;
}

function postPublish(User $staff, PartnerApplication $application, array $input = [])
{
    return test()->actingAs($staff)
        ->from(route('admin.applications.show', $application))
        ->post(route('admin.applications.publish', $application), $input + ['version' => 3]);
}

beforeEach(function () {
    $this->staff = User::factory()->staff()->create();
});

test('publishing creates one active spot from the application', function () {
    $application = approvedApplication([
        'store_name' => '新しいカフェ　',
        'description_ja' => '店内で給水できます。',
        'latitude' => 35.661000,
        'longitude' => 139.704000,
    ]);

    postPublish($this->staff, $application)
        ->assertRedirect(route('admin.applications.show', $application))
        ->assertSessionHasNoErrors();

    $spot = RefillSpot::sole();
    $application->refresh();

    expect($spot->name)->toBe('新しいカフェ')
        ->and($spot->description_ja)->toBe('店内で給水できます。')
        ->and((float) $spot->latitude)->toBe(35.661)
        ->and($spot->is_active)->toBeTrue()
        ->and($application->state)->toBe(ApplicationState::Published)
        ->and($application->refill_spot_id)->toEqual($spot->id)
        ->and($application->version)->toEqual(4)
        ->and($application->stateChanges()->latest('id')->first()->to_state)->toBe(ApplicationState::Published);
});

test('the published spot appears in the app', function () {
    $application = approvedApplication(['store_name' => '新しいカフェ']);
    postPublish($this->staff, $application);

    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/spots')
        ->assertOk()
        ->assertJsonFragment(['name' => '新しいカフェ']);
});

test('only approved applications can be published', function () {
    $application = PartnerApplication::factory()->create();

    $this->actingAs($this->staff)
        ->post(route('admin.applications.publish', $application), ['version' => 1])
        ->assertSessionHasErrors('application');

    expect(RefillSpot::count())->toBe(0);
});

test('publishing twice from the same page creates only one spot', function () {
    $application = approvedApplication();

    postPublish($this->staff, $application)->assertSessionHasNoErrors();
    postPublish($this->staff, $application)->assertSessionHasErrors('application');

    expect(RefillSpot::count())->toBe(1);
});

test('a likely duplicate needs a tick to publish', function () {
    RefillSpot::factory()->create(['name' => '渋谷のカフェ', 'latitude' => 35.658000, 'longitude' => 139.701000]);
    $application = approvedApplication(['store_name' => '渋谷のカフェ　', 'latitude' => 35.658180, 'longitude' => 139.701000]);

    postPublish($this->staff, $application)->assertSessionHasErrors('confirm_duplicate');
    expect(RefillSpot::count())->toBe(1);

    postPublish($this->staff, $application, ['confirm_duplicate' => '1'])->assertSessionHasNoErrors();
    expect(RefillSpot::count())->toBe(2);
});

test('after publishing, the page links to the spot and its own spot is not a duplicate', function () {
    $application = approvedApplication(['store_name' => '新しいカフェ']);
    postPublish($this->staff, $application);

    $this->actingAs($this->staff)
        ->get(route('admin.applications.show', $application))
        ->assertOk()
        ->assertSee('Published as')
        ->assertDontSee('Possible duplicate')
        ->assertDontSee('Publish as a refill spot');
});

test('app users who are not staff cannot publish', function () {
    $application = approvedApplication();

    $this->actingAs(User::factory()->create())
        ->post(route('admin.applications.publish', $application), ['version' => 3])
        ->assertForbidden();

    expect(RefillSpot::count())->toBe(0);
});
