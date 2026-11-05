<?php

use App\Models\PartnerApplication;
use App\Models\RefillSpot;
use App\Models\Translation;
use App\Services\FieldTranslator;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'services.google_translate.project' => 'test-project',
        'services.google_translate.glossary' => null,
        'tavnit.translation.monthly_char_limit' => 400000,
    ]);

    // A cached token means the tests never ask Google for a real one.
    Cache::put('google_access_token', 'test-token', now()->addHour());
});

// Pretends to be Google: "EN: " plus the original, one per text sent.
function fakeGoogle(bool $withGlossary = false): void
{
    Http::fake(['translation.googleapis.com/*' => function (Request $request) use ($withGlossary) {
        $body = ['translations' => array_map(fn ($text) => ['translatedText' => "EN: {$text}"], $request['contents'])];

        if ($withGlossary) {
            $body['glossaryTranslations'] = array_map(fn ($text) => ['translatedText' => "GLOSSARY: {$text}"], $request['contents']);
        }

        return Http::response($body);
    }]);
}

function translator(): FieldTranslator
{
    return app(FieldTranslator::class);
}

test('missing fields of several records go to Google in one request', function () {
    fakeGoogle();
    $application = PartnerApplication::factory()->create([
        'description_ja' => '静かな店です。', 'hours_note_ja' => '日曜休み', 'message_ja' => null,
    ]);
    $spot = RefillSpot::factory()->create(['description_ja' => '店内で給水できます。']);

    expect(translator()->ensure([$application, $spot]))->toBeTrue();

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request) => $request['contents'] === ['静かな店です。', '日曜休み', '店内で給水できます。']
        && $request['sourceLanguageCode'] === 'ja'
        && $request['targetLanguageCode'] === 'en');

    expect($application->savedTranslation('description_ja'))->toBe('EN: 静かな店です。')
        ->and($application->savedTranslation('hours_note_ja'))->toBe('EN: 日曜休み')
        ->and($spot->savedTranslation('description_ja'))->toBe('EN: 店内で給水できます。')
        ->and(Translation::count())->toBe(3);
});

test('nothing is sent when every translation is saved and current', function () {
    fakeGoogle();
    $spot = RefillSpot::factory()->create(['description_ja' => '店内で給水できます。']);
    translator()->ensure($spot);

    expect(translator()->ensure($spot->fresh()))->toBeTrue();

    Http::assertSentCount(1);
});

test('a changed original is translated again and its row updated', function () {
    fakeGoogle();
    $spot = RefillSpot::factory()->create(['description_ja' => '店内で給水できます。']);
    translator()->ensure($spot);

    $spot->update(['description_ja' => '入口で給水できます。']);
    translator()->ensure($spot);

    expect($spot->savedTranslation('description_ja'))->toBe('EN: 入口で給水できます。')
        ->and(Translation::count())->toBe(1);
    Http::assertSentCount(2);
});

test('the glossary version is saved when there is one', function () {
    config(['services.google_translate.glossary' => 'test-glossary']);
    fakeGoogle(withGlossary: true);
    $spot = RefillSpot::factory()->create(['description_ja' => '給水スポットです。']);

    translator()->ensure($spot);

    expect($spot->savedTranslation('description_ja'))->toBe('GLOSSARY: 給水スポットです。')
        ->and($spot->translations->first()->provider)->toBe('google-v3+glossary');
    Http::assertSent(fn (Request $request) => str_ends_with($request['glossaryConfig']['glossary'], '/glossaries/test-glossary'));
});

test('a Google error leaves the page in Japanese instead of breaking it', function () {
    Http::fake(['translation.googleapis.com/*' => Http::response(['error' => 'quota'], 429)]);
    $spot = RefillSpot::factory()->create(['description_ja' => '店内で給水できます。']);

    expect(translator()->ensure($spot))->toBeFalse()
        ->and(Translation::count())->toBe(0);
});

test('a timeout or lost connection also falls back to Japanese', function () {
    Http::fake(['translation.googleapis.com/*' => Http::failedConnection()]);
    $spot = RefillSpot::factory()->create(['description_ja' => '店内で給水できます。']);

    expect(translator()->ensure($spot))->toBeFalse()
        ->and(Translation::count())->toBe(0);
});

test('characters sent are counted for the month', function () {
    fakeGoogle();
    $spot = RefillSpot::factory()->create(['description_ja' => '店内で給水できます。']); // 10 characters

    translator()->ensure($spot);

    expect(translator()->charactersUsedThisMonth())->toBe(10);
});

test('Google is not called once the monthly limit would be passed', function () {
    fakeGoogle();
    config(['tavnit.translation.monthly_char_limit' => 5]);
    $spot = RefillSpot::factory()->create(['description_ja' => '店内で給水できます。']);

    expect(translator()->ensure($spot))->toBeFalse();

    Http::assertNothingSent();
});
