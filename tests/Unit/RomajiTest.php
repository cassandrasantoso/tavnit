<?php

use App\Support\Romaji;

test('kana readings become Latin letters', function (string $kana, string $latin) {
    expect(Romaji::fromKana($kana))->toBe($latin);
})->with([
    'words with spaces' => ['シモキタザワ ノ カフェ', 'Shimokitazawa No Kafe'],
    'hiragana' => ['しぶや', 'Shibuya'],
    'long vowel mark' => ['ラーメン', 'Rāmen'],
    'small tsu' => ['きって', 'Kitte'],
    'small tsu before ch' => ['マッチャ', 'Matcha'],
    'n before a vowel' => ['しんいち', "Shin'ichi"],
    'n before y' => ['ほんや', "Hon'ya"],
    'combined sounds' => ['ジュース', 'Jūsu'],
    'sounds from other languages' => ['ヴィーガン', 'Vīgan'],
    'half-width katakana' => ['ｶﾌｪ', 'Kafe'],
    'middle dot' => ['ティー・ルーム', 'Tī Rūmu'],
    'letters stay as they are' => ['ABC　カフェ', 'ABC Kafe'],
]);
