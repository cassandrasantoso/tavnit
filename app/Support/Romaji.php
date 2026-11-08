<?php

namespace App\Support;

/**
 * Turns a kana reading into Latin letters (Hepburn), so staff who don't
 * read Japanese can say a shop's name: シモキタザワ ノ カフェ → Shimokitazawa No Kafe.
 *
 * Kana follows fixed rules, so this needs no API. Kanji has no single
 * reading, which is why the shop gives us the kana and we never guess it.
 * Known limits: は/へ used as particles come out as "ha"/"he", and long
 * vowels written with kana (おう, えい) are kept as written; only ー becomes a macron.
 */
class Romaji
{
    // Two kana that make one sound. Checked before single kana.
    private const PAIRS = [
        'きゃ' => 'kya', 'きゅ' => 'kyu', 'きょ' => 'kyo',
        'しゃ' => 'sha', 'しゅ' => 'shu', 'しょ' => 'sho', 'しぇ' => 'she',
        'ちゃ' => 'cha', 'ちゅ' => 'chu', 'ちょ' => 'cho', 'ちぇ' => 'che',
        'にゃ' => 'nya', 'にゅ' => 'nyu', 'にょ' => 'nyo',
        'ひゃ' => 'hya', 'ひゅ' => 'hyu', 'ひょ' => 'hyo',
        'みゃ' => 'mya', 'みゅ' => 'myu', 'みょ' => 'myo',
        'りゃ' => 'rya', 'りゅ' => 'ryu', 'りょ' => 'ryo',
        'ぎゃ' => 'gya', 'ぎゅ' => 'gyu', 'ぎょ' => 'gyo',
        'じゃ' => 'ja', 'じゅ' => 'ju', 'じょ' => 'jo', 'じぇ' => 'je',
        'ぢゃ' => 'ja', 'ぢゅ' => 'ju', 'ぢょ' => 'jo',
        'びゃ' => 'bya', 'びゅ' => 'byu', 'びょ' => 'byo',
        'ぴゃ' => 'pya', 'ぴゅ' => 'pyu', 'ぴょ' => 'pyo',
        // Sounds from other languages, mostly written in katakana.
        'ふぁ' => 'fa', 'ふぃ' => 'fi', 'ふぇ' => 'fe', 'ふぉ' => 'fo',
        'てぃ' => 'ti', 'でぃ' => 'di', 'とぅ' => 'tu', 'どぅ' => 'du',
        'うぃ' => 'wi', 'うぇ' => 'we', 'うぉ' => 'wo', 'いぇ' => 'ye',
        'ゔぁ' => 'va', 'ゔぃ' => 'vi', 'ゔぇ' => 've', 'ゔぉ' => 'vo',
        'つぁ' => 'tsa',
    ];

    private const SINGLE = [
        'あ' => 'a', 'い' => 'i', 'う' => 'u', 'え' => 'e', 'お' => 'o',
        'か' => 'ka', 'き' => 'ki', 'く' => 'ku', 'け' => 'ke', 'こ' => 'ko',
        'さ' => 'sa', 'し' => 'shi', 'す' => 'su', 'せ' => 'se', 'そ' => 'so',
        'た' => 'ta', 'ち' => 'chi', 'つ' => 'tsu', 'て' => 'te', 'と' => 'to',
        'な' => 'na', 'に' => 'ni', 'ぬ' => 'nu', 'ね' => 'ne', 'の' => 'no',
        'は' => 'ha', 'ひ' => 'hi', 'ふ' => 'fu', 'へ' => 'he', 'ほ' => 'ho',
        'ま' => 'ma', 'み' => 'mi', 'む' => 'mu', 'め' => 'me', 'も' => 'mo',
        'や' => 'ya', 'ゆ' => 'yu', 'よ' => 'yo',
        'ら' => 'ra', 'り' => 'ri', 'る' => 'ru', 'れ' => 're', 'ろ' => 'ro',
        'わ' => 'wa', 'ゐ' => 'i', 'ゑ' => 'e', 'を' => 'o', 'ん' => 'n',
        'が' => 'ga', 'ぎ' => 'gi', 'ぐ' => 'gu', 'げ' => 'ge', 'ご' => 'go',
        'ざ' => 'za', 'じ' => 'ji', 'ず' => 'zu', 'ぜ' => 'ze', 'ぞ' => 'zo',
        'だ' => 'da', 'ぢ' => 'ji', 'づ' => 'zu', 'で' => 'de', 'ど' => 'do',
        'ば' => 'ba', 'び' => 'bi', 'ぶ' => 'bu', 'べ' => 'be', 'ぼ' => 'bo',
        'ぱ' => 'pa', 'ぴ' => 'pi', 'ぷ' => 'pu', 'ぺ' => 'pe', 'ぽ' => 'po',
        'ゔ' => 'vu',
        'ぁ' => 'a', 'ぃ' => 'i', 'ぅ' => 'u', 'ぇ' => 'e', 'ぉ' => 'o',
        'ゃ' => 'ya', 'ゅ' => 'yu', 'ょ' => 'yo', 'ゎ' => 'wa',
        '・' => ' ',
    ];

    private const MACRONS = ['a' => 'ā', 'i' => 'ī', 'u' => 'ū', 'e' => 'ē', 'o' => 'ō'];

    public static function fromKana(string $kana): string
    {
        // Half-width katakana and full-width spaces/letters to their usual
        // forms, then katakana to hiragana so one table covers both.
        // (Two passes: mb_convert_kana doesn't chain options. ヴ has no 'c' conversion.)
        $kana = mb_convert_kana(mb_convert_kana($kana, 'asKV'), 'c');
        $chars = mb_str_split(str_replace('ヴ', 'ゔ', $kana));
        $sounds = [];
        $doubleNext = false;

        for ($i = 0; $i < count($chars); $i++) {
            $char = $chars[$i];
            $pair = $char.($chars[$i + 1] ?? '');

            if (isset(self::PAIRS[$pair])) {
                $sound = self::PAIRS[$pair];
                $i++;
            } elseif ($char === 'っ') {
                // A small tsu doubles the next consonant: きって → kitte.
                $doubleNext = true;

                continue;
            } elseif ($char === 'ー') {
                // The long-vowel mark puts a macron on the vowel before it: ラーメン → rāmen.
                $sounds[] = self::lengthen(array_pop($sounds) ?? '');

                continue;
            } else {
                // Anything that isn't kana (letters, numbers, spaces) stays as it is.
                $sound = self::SINGLE[$char] ?? $char;
            }

            if ($doubleNext && preg_match('/^[b-df-hj-np-tv-z]/', $sound)) {
                $sound = (str_starts_with($sound, 'ch') ? 't' : $sound[0]).$sound; // まっちゃ → matcha
            }
            $doubleNext = false;

            $sounds[] = $sound;
        }

        $text = '';
        foreach ($sounds as $k => $sound) {
            $text .= $sound;

            // ん before a vowel or y gets an apostrophe, so ほんや reads hon'ya, not ho-nya.
            if ($sound === 'n' && preg_match('/^[aiueoy]/', $sounds[$k + 1] ?? '')) {
                $text .= "'";
            }
        }

        // One space between words, each word starting with a capital.
        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);

        return implode(' ', array_map(
            fn (string $word) => mb_strtoupper(mb_substr($word, 0, 1)).mb_substr($word, 1),
            $words,
        ));
    }

    private static function lengthen(string $sound): string
    {
        $last = mb_substr($sound, -1);

        return isset(self::MACRONS[$last])
            ? mb_substr($sound, 0, -1).self::MACRONS[$last]
            : $sound;
    }
}
