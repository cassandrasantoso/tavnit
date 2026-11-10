<?php

use App\Models\RefillSpot;
use App\Models\User;
use App\Services\FieldTranslator;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;

// Records log calls instead of writing them; Log::channel('timing') hands back the same recorder.
function spyOnTimingLog(): void
{
    Log::spy();
    Log::shouldReceive('channel')->with('timing')->andReturnSelf();
}

test('API responses say how long they took', function () {
    Sanctum::actingAs(User::factory()->create());

    $response = $this->getJson('/api/v1/spots')->assertOk();

    expect($response->headers->get('Server-Timing'))->toMatch('/^app;dur=\d+(\.\d+)?$/');
});

test('each API request is written to the timing log', function () {
    spyOnTimingLog();
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/spots');

    Log::shouldHaveReceived('info')->withArgs(fn ($message, $context) => $message === 'request'
        && $context['route'] === 'api/v1/spots'
        && $context['status'] === 200
        && is_float($context['ms']));
});

test('staff pages are not timed', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.refill-spots.index'))
        ->assertHeaderMissing('Server-Timing');
});

test('a translation is written to the timing log with its size', function () {
    config(['services.google_translate.project' => 'test-project', 'services.google_translate.glossary' => null]);
    Cache::put('google_access_token', 'test-token', now()->addHour());
    Http::fake(['translation.googleapis.com/*' => fn (Request $request) => Http::response([
        'translations' => array_map(fn ($text) => ['translatedText' => "EN: {$text}"], $request['contents']),
    ])]);
    spyOnTimingLog();

    app(FieldTranslator::class)->ensure(RefillSpot::factory()->create(['description_ja' => '店内で給水できます。']));

    Log::shouldHaveReceived('info')->withArgs(fn ($message, $context) => $message === 'translation'
        && $context['fields'] === 1
        && $context['characters'] === 10);
});
