<?php

namespace Database\Factories;

use App\Models\RefillSpot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RefillSpot>
 */
class RefillSpotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $areas = ['渋谷', '原宿', '恵比寿', '代々木', '中目黒', '下北沢', '表参道'];
        $types = ['カフェ', 'ベーカリー', '本屋', 'ギャラリー', 'ラーメン店', '公園'];

        return [
            'name' => fake()->randomElement($areas) . 'の' . fake()->randomElement($types),
            'description_ja' => fake()->randomElement([
                'マイボトルを持参すれば、無料でお水を入れられます。',
                '店内のスタッフに声をかけてください。',
                '営業時間内はいつでも給水できます。',
                '冷たいお水とお湯の両方があります。',
            ]),
            'latitude' => fake()->randomFloat(6, 35.60, 35.75),
            'longitude' => fake()->randomFloat(6, 139.65, 139.80),
            'is_active' => true,
        ];
    }
}
