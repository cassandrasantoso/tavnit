<?php

use App\Models\PartnerApplication;
use App\Models\RefillSpot;
use App\Models\Translation;
use Illuminate\Database\UniqueConstraintViolationException;

function saveEnglish($record, string $field, string $text): Translation
{
    return $record->translations()->create([
        'field' => $field,
        'locale' => 'en',
        'text' => $text,
        'source_hash' => $record::sourceHash($record->{$field}),
        'provider' => 'test',
        'translated_at' => now(),
    ]);
}

test('only free-text fields are translatable', function () {
    expect((new PartnerApplication)->translatableFields())
        ->toBe(['description_ja', 'hours_note_ja', 'message_ja'])
        ->not->toContain('store_name')
        ->not->toContain('address');

    expect((new RefillSpot)->translatableFields())->toBe(['description_ja']);
});

test('a saved translation is returned while the original is unchanged', function () {
    $spot = RefillSpot::factory()->create(['description_ja' => '店内で給水できます。']);
    saveEnglish($spot, 'description_ja', 'You can refill inside the shop.');

    expect($spot->fresh()->savedTranslation('description_ja'))
        ->toBe('You can refill inside the shop.');
});

test('a translation is ignored once the original changes', function () {
    $spot = RefillSpot::factory()->create(['description_ja' => '店内で給水できます。']);
    saveEnglish($spot, 'description_ja', 'You can refill inside the shop.');

    $spot->update(['description_ja' => '入口で給水できます。']);

    expect($spot->fresh()->savedTranslation('description_ja'))->toBeNull();
});

test('there is no translation for an empty field or another language', function () {
    $application = PartnerApplication::factory()->create(['message_ja' => null]);

    expect($application->savedTranslation('message_ja'))->toBeNull();

    $spot = RefillSpot::factory()->create(['description_ja' => '店内で給水できます。']);
    saveEnglish($spot, 'description_ja', 'You can refill inside the shop.');

    expect($spot->fresh()->savedTranslation('description_ja', 'fr'))->toBeNull();
});

test('records are saved with a short type name', function () {
    $spot = RefillSpot::factory()->create(['description_ja' => '店内で給水できます。']);
    saveEnglish($spot, 'description_ja', 'You can refill inside the shop.');

    $this->assertDatabaseHas('translations', [
        'translatable_type' => 'refill_spot',
        'translatable_id' => $spot->id,
    ]);
});

test('a field has only one translation per language', function () {
    $spot = RefillSpot::factory()->create(['description_ja' => '店内で給水できます。']);
    saveEnglish($spot, 'description_ja', 'First');

    saveEnglish($spot, 'description_ja', 'Second');
})->throws(UniqueConstraintViolationException::class);

test('translations are removed with their application', function () {
    $application = PartnerApplication::factory()->create(['description_ja' => '静かな店です。']);
    saveEnglish($application, 'description_ja', 'A quiet shop.');

    $application->delete();

    expect(Translation::count())->toBe(0);
});

test('a removed spot keeps its translations until it is deleted for good', function () {
    $spot = RefillSpot::factory()->create(['description_ja' => '店内で給水できます。']);
    saveEnglish($spot, 'description_ja', 'You can refill inside the shop.');

    $spot->delete();
    expect(Translation::count())->toBe(1);

    $spot->forceDelete();
    expect(Translation::count())->toBe(0);
});
