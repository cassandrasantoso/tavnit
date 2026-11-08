<?php

use App\Enums\ApplicationState;
use App\Models\PartnerApplication;
use App\Models\RefillSpot;
use App\Models\User;

beforeEach(function () {
    $this->staff = User::factory()->staff()->create();
});

test('in English, a name shows its reading and English name, never a translation', function () {
    RefillSpot::factory()->create([
        'name' => '下北沢のカフェ',
        'name_kana' => 'シモキタザワ ノ カフェ',
        'name_en' => 'Shimokita Cafe',
    ]);

    $this->actingAs($this->staff)
        ->get(route('admin.refill-spots.index'))
        ->assertSeeInOrder(['下北沢のカフェ', 'Shimokitazawa No Kafe', 'Shimokita Cafe']);
});

test('in Japanese, a name shows its kana instead', function () {
    $this->staff->update(['locale' => 'ja']);
    RefillSpot::factory()->create(['name' => '下北沢のカフェ', 'name_kana' => 'シモキタザワ ノ カフェ']);

    $this->actingAs($this->staff)
        ->get(route('admin.refill-spots.index'))
        ->assertSee('（シモキタザワ ノ カフェ）')
        ->assertDontSee('Shimokitazawa');
});

test('a name with no reading is shown on its own', function () {
    RefillSpot::factory()->create(['name' => '代々木の公園', 'name_kana' => null]);

    $this->actingAs($this->staff)
        ->get(route('admin.refill-spots.index'))
        ->assertSee('代々木の公園');
});

test('staff can save a reading and an English name', function () {
    $this->actingAs($this->staff)
        ->post(route('admin.refill-spots.store'), [
            'name' => '原宿の本屋', 'name_kana' => 'ハラジュク ノ ホンヤ', 'name_en' => 'Harajuku Books',
            'latitude' => 35.67, 'longitude' => 139.70, 'is_active' => 1,
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('refill_spots', ['name_kana' => 'ハラジュク ノ ホンヤ', 'name_en' => 'Harajuku Books']);
});

test('a reading must be kana', function () {
    $this->actingAs($this->staff)
        ->post(route('admin.refill-spots.store'), [
            'name' => '原宿の本屋', 'name_kana' => '原宿の本屋',
            'latitude' => 35.67, 'longitude' => 139.70, 'is_active' => 1,
        ])
        ->assertSessionHasErrors(['name_kana' => 'Use only hiragana or katakana for the reading.']);
});

test('publishing copies the reading and the English name to the spot', function () {
    $application = PartnerApplication::factory()->create([
        'store_name' => '恵比寿のベーカリー',
        'store_name_kana' => 'エビス ノ ベーカリー',
        'store_name_en' => 'Ebisu Bakery',
    ]);
    $application->forceFill(['state' => ApplicationState::Approved, 'version' => 3])->save();

    $this->actingAs($this->staff)
        ->post(route('admin.applications.publish', $application), ['version' => 3])
        ->assertSessionHasNoErrors();

    expect(RefillSpot::sole())
        ->name_kana->toBe('エビス ノ ベーカリー')
        ->name_en->toBe('Ebisu Bakery');
});
