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
        // Made-up names, each part with its kana reading.
        $areas = [
            '渋谷' => 'シブヤ', '原宿' => 'ハラジュク', '恵比寿' => 'エビス', '代々木' => 'ヨヨギ',
            '中目黒' => 'ナカメグロ', '下北沢' => 'シモキタザワ', '表参道' => 'オモテサンドウ',
        ];
        $types = [
            'カフェ' => 'カフェ', 'ベーカリー' => 'ベーカリー', '本屋' => 'ホンヤ',
            'ギャラリー' => 'ギャラリー', 'ラーメン店' => 'ラーメンテン', '公園' => 'コウエン',
        ];
        $area = fake()->randomKey($areas);
        $type = fake()->randomKey($types);

        return [
            'name' => $area.'の'.$type,
            'name_kana' => $areas[$area].' ノ '.$types[$type],
            'name_en' => null,
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
