<?php

use App\Enums\ApplicationState;
use App\Models\PartnerApplication;
use App\Models\RefillSpot;
use App\Models\User;
use Illuminate\Support\Arr;

beforeEach(function () {
    $this->staff = User::factory()->staff()->create(['locale' => 'ja']);
});

test('every English text has a Japanese version, and the other way round', function (string $file) {
    $english = array_keys(Arr::dot(require lang_path("en/{$file}.php")));
    $japanese = array_keys(Arr::dot(require lang_path("ja/{$file}.php")));

    expect(array_diff($english, $japanese))->toBe([], "Missing in lang/ja/{$file}.php")
        ->and(array_diff($japanese, $english))->toBe([], "Missing in lang/en/{$file}.php");
})->with(['ui', 'login', 'spots', 'applications', 'review']);

test('the spots list is in Japanese', function () {
    RefillSpot::factory()->create(['name' => '渋谷のカフェ', 'is_active' => true]);

    $this->actingAs($this->staff)
        ->get(route('admin.refill-spots.index'))
        ->assertOk()
        ->assertSee('スポットを追加')
        ->assertSee('公開中')
        ->assertDontSee('Add a spot');
});

test('the spot form is in Japanese', function () {
    $this->actingAs($this->staff)
        ->get(route('admin.refill-spots.create'))
        ->assertSee('給水スポットを追加')
        ->assertSee('緯度')
        ->assertSee('アプリに表示する');
});

test('saving a spot confirms it in Japanese', function () {
    $this->actingAs($this->staff)
        ->post(route('admin.refill-spots.store'), [
            'name' => '原宿の本屋', 'latitude' => 35.67, 'longitude' => 139.70, 'is_active' => 1,
        ])
        ->assertSessionHas('status', '原宿の本屋 を追加しました。');
});

test('the review queue and its states are in Japanese', function () {
    PartnerApplication::factory()->create(['store_name' => '待ちの店']);

    $this->actingAs($this->staff)
        ->get(route('admin.applications.index'))
        ->assertSee('パートナー店舗の申請')
        ->assertSee('受付済み (1)')
        ->assertSee('担当者')
        ->assertDontSee('Submitted');
});

test('an application page is in Japanese, with each reason shown in English too', function () {
    $application = PartnerApplication::factory()->create();
    $application->forceFill(['state' => ApplicationState::Returned, 'version' => 3])->save();
    $application->stateChanges()->create([
        'user_id' => $this->staff->id,
        'from_state' => ApplicationState::InReview,
        'to_state' => ApplicationState::Returned,
        'reason' => 'possible_duplicate',
    ]);

    $this->actingAs($this->staff)
        ->get(route('admin.applications.show', $application))
        ->assertSee('一覧に戻る')
        ->assertSee('店舗からのメッセージ')
        ->assertSee('審査中 → 差し戻し')
        ->assertSee('すでに登録されている可能性があります')
        ->assertSee('This place may already be listed');
});

test('errors are in Japanese too', function () {
    $application = PartnerApplication::factory()->create();
    $application->forceFill(['state' => ApplicationState::Approved, 'version' => 3])->save();

    $this->actingAs($this->staff)
        ->post(route('admin.applications.claim', $application), ['version' => 3])
        ->assertSessionHasErrors(['application' => 'この申請はすでに担当者待ちではありません。']);
});

test('the login page uses the default language', function () {
    $this->get(route('login'))
        ->assertSee('<html lang="en">', false)
        ->assertSee('Staff login');
});
