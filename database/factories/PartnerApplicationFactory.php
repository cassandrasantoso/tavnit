<?php

namespace Database\Factories;

use App\Models\PartnerApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartnerApplication>
 */
class PartnerApplicationFactory extends Factory
{
    public function definition(): array
    {
        // Each type with the Japanese word used in its made-up name.
        $types = [
            'cafe' => 'カフェ',
            'bakery' => 'ベーカリー',
            'shop' => '雑貨店',
            'hotel' => 'ゲストハウス',
        ];
        $type = fake()->randomKey($types);

        return [
            'store_name' => $types[$type].' '.fake()->unique()->lexify('????'),
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
