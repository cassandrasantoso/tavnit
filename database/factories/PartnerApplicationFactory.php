<?php

namespace Database\Factories;

use App\Models\PartnerApplication;
use App\Support\Romaji;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartnerApplication>
 */
class PartnerApplicationFactory extends Factory
{
    public function definition(): array
    {
        // Each type with the Japanese word used in its made-up name, and its kana reading.
        $types = [
            'cafe' => ['カフェ', 'カフェ', 'Cafe'],
            'bakery' => ['ベーカリー', 'ベーカリー', 'Bakery'],
            'shop' => ['雑貨店', 'ザッカテン', 'Goods'],
            'hotel' => ['ゲストハウス', 'ゲストハウス', 'Guesthouse'],
        ];
        $type = fake()->randomKey($types);
        [$typeJa, $typeKana, $typeEn] = $types[$type];

        // A made-up three-kana word, so names rarely repeat: カフェ ミソラ.
        $word = implode('', fake()->randomElements(
            ['ミ', 'ソ', 'ラ', 'ハ', 'ナ', 'カ', 'サ', 'キ', 'ト', 'モ', 'リ', 'ユ', 'メ', 'ア', 'オ', 'タ', 'マ', 'ヒ', 'ル', 'コ'],
            3,
        ));

        return [
            'store_name' => $typeJa.' '.$word,
            'store_name_kana' => $typeKana.' '.$word,
            // Some shops have an English name they use themselves.
            'store_name_en' => fake()->boolean(30) ? $typeEn.' '.Romaji::fromKana($word) : null,
            'store_type' => $type,
            'address' => '東京都渋谷区 '.fake()->numberBetween(1, 5).'-'.fake()->numberBetween(1, 30),
            'description_ja' => '店内で給水できます。マイボトルをお持ちください。',
            'hours_note_ja' => '平日 9:00–18:00',
            'message_ja' => fake()->optional()->randomElement(['よろしくお願いします。', '来月オープン予定です。']),
            'opening_date' => fake()->optional()->dateTimeBetween('now', '+1 month'),
            'contact_email' => fake()->unique()->userName().'@example.test',
            // Around Shibuya, like the spots.
            'latitude' => fake()->latitude(35.650, 35.670),
            'longitude' => fake()->longitude(139.690, 139.715),
            'created_at' => fake()->dateTimeBetween('-20 days', 'now'),
        ];
    }
}
