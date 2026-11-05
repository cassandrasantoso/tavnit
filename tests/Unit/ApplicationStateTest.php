<?php

use App\Enums\ApplicationState;

it('allows the normal path from submitted to published', function () {
    expect(ApplicationState::Submitted->canMoveTo(ApplicationState::InReview))->toBeTrue()
        ->and(ApplicationState::InReview->canMoveTo(ApplicationState::Approved))->toBeTrue()
        ->and(ApplicationState::Approved->canMoveTo(ApplicationState::Published))->toBeTrue();
});

it('lets a sent-back application be submitted again', function () {
    expect(ApplicationState::InReview->canMoveTo(ApplicationState::Returned))->toBeTrue()
        ->and(ApplicationState::Returned->canMoveTo(ApplicationState::Submitted))->toBeTrue();
});

it('does not allow skipping a step', function () {
    expect(ApplicationState::Submitted->canMoveTo(ApplicationState::Approved))->toBeFalse()
        ->and(ApplicationState::Submitted->canMoveTo(ApplicationState::Published))->toBeFalse()
        ->and(ApplicationState::InReview->canMoveTo(ApplicationState::Published))->toBeFalse()
        ->and(ApplicationState::Returned->canMoveTo(ApplicationState::Approved))->toBeFalse();
});

it('treats rejected and published as final', function () {
    foreach (ApplicationState::cases() as $state) {
        expect(ApplicationState::Rejected->canMoveTo($state))->toBeFalse()
            ->and(ApplicationState::Published->canMoveTo($state))->toBeFalse();
    }

    expect(ApplicationState::Rejected->isFinal())->toBeTrue()
        ->and(ApplicationState::Published->isFinal())->toBeTrue()
        ->and(ApplicationState::InReview->isFinal())->toBeFalse();
});
