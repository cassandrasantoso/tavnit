<?php

use App\Enums\ApplicationState;
use App\Models\PartnerApplication;
use App\Models\User;

// An application already claimed by $staff (claiming moved it to version 2).
function applicationClaimedBy(User $staff): PartnerApplication
{
    $application = PartnerApplication::factory()->create();
    $application->forceFill([
        'state' => ApplicationState::InReview,
        'claimed_by' => $staff->id,
        'claimed_at' => now(),
        'version' => 2,
    ])->save();

    return $application;
}

function postDecision(User $staff, PartnerApplication $application, array $input)
{
    return test()->actingAs($staff)
        ->from(route('admin.applications.show', $application))
        ->post(route('admin.applications.decide', $application), $input + ['version' => 2]);
}

beforeEach(function () {
    $this->staff = User::factory()->staff()->create(['name' => 'Staff One']);
});

test('the reviewer can approve without a reason', function () {
    $application = applicationClaimedBy($this->staff);

    postDecision($this->staff, $application, ['decision' => 'approved'])
        ->assertRedirect(route('admin.applications.show', $application))
        ->assertSessionHas('status');

    $application->refresh();
    $change = $application->stateChanges->last();

    expect($application->state)->toBe(ApplicationState::Approved)
        ->and($application->version)->toEqual(3)
        ->and($change->from_state)->toBe(ApplicationState::InReview)
        ->and($change->to_state)->toBe(ApplicationState::Approved)
        ->and($change->user->is($this->staff))->toBeTrue();
});

test('sending back needs a reason', function () {
    $application = applicationClaimedBy($this->staff);

    postDecision($this->staff, $application, ['decision' => 'returned'])
        ->assertSessionHasErrors('reason');

    expect($application->fresh()->state)->toBe(ApplicationState::InReview);
});

test('rejecting with a reason records the reason', function () {
    $application = applicationClaimedBy($this->staff);

    postDecision($this->staff, $application, ['decision' => 'rejected', 'reason' => 'possible_duplicate'])
        ->assertSessionHasNoErrors();

    expect($application->fresh()->state)->toBe(ApplicationState::Rejected)
        ->and($application->stateChanges()->latest('id')->first()->reason)->toBe('possible_duplicate');
});

test('the reason "other" needs a note', function () {
    $application = applicationClaimedBy($this->staff);

    postDecision($this->staff, $application, ['decision' => 'returned', 'reason' => 'other'])
        ->assertSessionHasErrors('note');

    expect($application->fresh()->state)->toBe(ApplicationState::InReview);
});

test('an unknown reason or decision is refused', function () {
    $application = applicationClaimedBy($this->staff);

    postDecision($this->staff, $application, ['decision' => 'returned', 'reason' => 'because'])
        ->assertSessionHasErrors('reason');
    postDecision($this->staff, $application, ['decision' => 'published'])
        ->assertSessionHasErrors('decision');

    expect($application->fresh()->state)->toBe(ApplicationState::InReview);
});

test('only the staff member who claimed it can decide', function () {
    $application = applicationClaimedBy($this->staff);
    $someoneElse = User::factory()->staff()->create();

    postDecision($someoneElse, $application, ['decision' => 'approved'])
        ->assertSessionHasErrors('application');

    expect($application->fresh()->state)->toBe(ApplicationState::InReview);
});

test('a page with an old version cannot decide', function () {
    $application = applicationClaimedBy($this->staff);
    $application->forceFill(['version' => 3])->save();

    postDecision($this->staff, $application, ['decision' => 'approved'])
        ->assertSessionHasErrors('application');

    expect($application->fresh()->state)->toBe(ApplicationState::InReview);
});

test('claiming is recorded in the history', function () {
    $application = PartnerApplication::factory()->create();

    $this->actingAs($this->staff)
        ->post(route('admin.applications.claim', $application), ['version' => 1]);

    $change = $application->stateChanges()->sole();

    expect($change->from_state)->toBe(ApplicationState::Submitted)
        ->and($change->to_state)->toBe(ApplicationState::InReview)
        ->and($change->user->is($this->staff))->toBeTrue();
});

test('the history shows the reason in English and Japanese', function () {
    $application = applicationClaimedBy($this->staff);
    postDecision($this->staff, $application, ['decision' => 'rejected', 'reason' => 'possible_duplicate']);

    $this->actingAs($this->staff)
        ->get(route('admin.applications.show', $application))
        ->assertOk()
        ->assertSee('Staff One')
        ->assertSee('This place may already be listed')
        ->assertSee('すでに登録されている可能性があります');
});

test('only the reviewer sees the decision form', function () {
    $application = applicationClaimedBy($this->staff);

    $this->actingAs($this->staff)
        ->get(route('admin.applications.show', $application))
        ->assertSee('Save decision');

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.applications.show', $application))
        ->assertDontSee('Save decision');
});
