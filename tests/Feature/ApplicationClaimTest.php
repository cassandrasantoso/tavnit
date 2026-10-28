<?php

use App\Enums\ApplicationState;
use App\Models\PartnerApplication;
use App\Models\User;

test('staff can claim a submitted application', function () {
    $staff = User::factory()->staff()->create();
    $application = PartnerApplication::factory()->create();

    $this->actingAs($staff)
        ->post(route('admin.applications.claim', $application), ['version' => 1])
        ->assertRedirect(route('admin.applications.show', $application))
        ->assertSessionHas('status');

    $application->refresh();

    expect($application->state)->toBe(ApplicationState::InReview)
        ->and($application->claimedBy->is($staff))->toBeTrue()
        ->and($application->claimed_at)->not->toBeNull()
        ->and($application->version)->toEqual(2);
});

test('a page with an old version cannot claim', function () {
    $application = PartnerApplication::factory()->create();
    // Someone else changed it after our page loaded: the row is now version 2.
    $application->forceFill(['version' => 2])->save();
    $page = route('admin.applications.show', $application);

    $this->actingAs(User::factory()->staff()->create())
        ->from($page)
        ->post(route('admin.applications.claim', $application), ['version' => 1])
        ->assertRedirect($page)
        ->assertSessionHasErrors('application');

    expect($application->fresh()->state)->toBe(ApplicationState::Submitted)
        ->and($application->fresh()->claimed_by)->toBeNull();
});

test('when two staff claim the same application, the first one keeps it', function () {
    $first = User::factory()->staff()->create();
    $second = User::factory()->staff()->create();
    $application = PartnerApplication::factory()->create();
    $page = route('admin.applications.show', $application);

    // Both opened the page while it was version 1.
    $this->actingAs($first)
        ->post(route('admin.applications.claim', $application), ['version' => 1]);

    $this->actingAs($second)
        ->from($page)
        ->post(route('admin.applications.claim', $application), ['version' => 1])
        ->assertRedirect($page)
        ->assertSessionHasErrors('application');

    expect($application->fresh()->claimedBy->is($first))->toBeTrue();
});

test('claiming needs a version', function () {
    $application = PartnerApplication::factory()->create();

    $this->actingAs(User::factory()->staff()->create())
        ->post(route('admin.applications.claim', $application))
        ->assertSessionHasErrors('version');

    expect($application->fresh()->state)->toBe(ApplicationState::Submitted);
});

test('app users who are not staff cannot claim', function () {
    $application = PartnerApplication::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('admin.applications.claim', $application), ['version' => 1])
        ->assertForbidden();
});
