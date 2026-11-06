<?php

use App\Models\User;

beforeEach(function () {
    $this->staff = User::factory()->staff()->create();
});

test('pages are in English until someone picks a language', function () {
    $this->actingAs($this->staff)
        ->get(route('admin.applications.index'))
        ->assertOk()
        ->assertSee('<html lang="en">', false)
        ->assertSee('Log out');
});

test('staff can switch the interface to Japanese', function () {
    $this->actingAs($this->staff)
        ->from(route('admin.applications.index'))
        ->put(route('locale.update'), ['locale' => 'ja'])
        ->assertRedirect(route('admin.applications.index'));

    expect($this->staff->fresh()->locale)->toBe('ja');

    $this->actingAs($this->staff->fresh())
        ->get(route('admin.applications.index'))
        ->assertSee('<html lang="ja">', false)
        ->assertSee('ログアウト')
        ->assertSee('給水スポット')
        ->assertDontSee('Log out');
});

test('staff can switch back to English', function () {
    $this->staff->update(['locale' => 'ja']);

    $this->actingAs($this->staff)
        ->put(route('locale.update'), ['locale' => 'en']);

    $this->actingAs($this->staff->fresh())
        ->get(route('admin.applications.index'))
        ->assertSee('Log out');
});

test('each person keeps their own language', function () {
    $this->staff->update(['locale' => 'ja']);
    $colleague = User::factory()->staff()->create();

    $this->actingAs($colleague)
        ->get(route('admin.applications.index'))
        ->assertSee('Log out');
});

test('a language the app does not have is refused', function () {
    $this->actingAs($this->staff)
        ->put(route('locale.update'), ['locale' => 'fr'])
        ->assertSessionHasErrors('locale');

    expect($this->staff->fresh()->locale)->toBeNull();
});

test('people who are not signed in cannot change the language', function () {
    $this->put(route('locale.update'), ['locale' => 'ja'])
        ->assertRedirect(route('login'));
});
