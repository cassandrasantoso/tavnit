<?php

use App\Models\RefillSpot;

test('the eval runs offline and leaves nothing behind', function () {
    $this->artisan('eval')
        ->expectsOutputToContain('Readings in Latin letters')
        ->expectsOutputToContain('Duplicate warning')
        ->expectsOutputToContain('Refill checks (7 of 7)')
        ->expectsOutputToContain('Glossary: with vs without: skipped')
        ->assertSuccessful();

    expect(RefillSpot::count())->toBe(0);
});
