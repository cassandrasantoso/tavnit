<?php

use App\Models\PartnerApplication;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'services.google_translate.project' => 'test-project',
        'services.google_translate.glossary' => null,
    ]);
    Cache::put('google_access_token', 'test-token', now()->addHour());

    $this->staff = User::factory()->staff()->create();
    $this->application = PartnerApplication::factory()->create([
        'description_ja' => '店内で給水できます。',
        'hours_note_ja' => '平日 9:00–18:00',
        'message_ja' => null,
    ]);
});

// Pretends to be Google: "EN: " plus the original, one per text sent.
function fakeGoogleForPage(): void
{
    Http::fake(['translation.googleapis.com/*' => fn (Request $request) => Http::response([
        'translations' => array_map(fn ($text) => ['translatedText' => "EN: {$text}"], $request['contents']),
    ])]);
}

test('in English, the shop text is machine translated with the original one click away', function () {
    fakeGoogleForPage();

    $this->actingAs($this->staff)
        ->get(route('admin.applications.show', $this->application))
        ->assertOk()
        ->assertSee('Free text from the shop is machine translated.')
        ->assertSeeInOrder(['EN: 店内で給水できます。', 'Machine translated', 'Show original', '店内で給水できます。'])
        ->assertSee('EN: 平日 9:00–18:00');
});

test('the second visit uses the saved English', function () {
    fakeGoogleForPage();

    $this->actingAs($this->staff)->get(route('admin.applications.show', $this->application));
    $this->actingAs($this->staff)->get(route('admin.applications.show', $this->application));

    Http::assertSentCount(1);
});

test('in Japanese, the text is shown as written and Google is not called', function () {
    fakeGoogleForPage();
    $this->staff->update(['locale' => 'ja']);

    $this->actingAs($this->staff)
        ->get(route('admin.applications.show', $this->application))
        ->assertSee('店内で給水できます。')
        ->assertDontSee('EN: ')
        ->assertDontSee('機械翻訳です');

    Http::assertNothingSent();
});

test('if Google fails, the page still opens in Japanese with a notice', function () {
    Http::fake(['translation.googleapis.com/*' => Http::response(['error' => 'unavailable'], 503)]);

    $this->actingAs($this->staff)
        ->get(route('admin.applications.show', $this->application))
        ->assertOk()
        ->assertSee("Translation isn't available right now")
        ->assertSee('店内で給水できます。')
        ->assertDontSee('Show original');
});

test('without a Google project set up, nothing is sent', function () {
    config(['services.google_translate.project' => null]);

    $this->actingAs($this->staff)
        ->get(route('admin.applications.show', $this->application))
        ->assertOk()
        ->assertSee('店内で給水できます。');

    Http::assertNothingSent();
});

test('HTML in a translation is shown as text, not run', function () {
    Http::fake(['translation.googleapis.com/*' => fn (Request $request) => Http::response([
        'translations' => array_map(fn () => ['translatedText' => '<script>alert(1)</script>'], $request['contents']),
    ])]);

    $this->actingAs($this->staff)
        ->get(route('admin.applications.show', $this->application))
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
});
