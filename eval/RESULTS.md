# Eval results

Run on 2026-11-09 06:36:54 with `php artisan eval --write`. All cases are made up.

## Readings in Latin letters (8 of 10)

| Kana | Expected | Got | OK | Note |
| --- | --- | --- | --- | --- |
| シモキタザワ ノ カフェ | Shimokitazawa no Kafe | Shimokitazawa no Kafe | ✓ |  |
| ヨヨギ ノ ラーメンテン | Yoyogi no Rāmenten | Yoyogi no Rāmenten | ✓ |  |
| ナカメグロ ノ ホンヤ | Nakameguro no Hon'ya | Nakameguro no Hon'ya | ✓ |  |
| マッチャ ト ミズ | Matcha to Mizu | Matcha to Mizu | ✓ |  |
| きっさ はなび | Kissa Hanabi | Kissa Hanabi | ✓ |  |
| ｶﾌｪ ﾐｽﾞ | Kafe Mizu | Kafe Mizu | ✓ |  |
| ヴィーガン ショップ | Vīgan Shoppu | Vīgan Shoppu | ✓ |  |
| ティー・ルーム | Tī Rūmu | Tī Rūmu | ✓ |  |
| オモテサンドウ | Omotesandō | Omotesandou | ✗ | Known limit: ウ after o stays "ou". |
| コンニチハ カフェ | Konnichiwa Kafe | Konnichiha Kafe | ✗ | Known limit: は as a particle stays "ha". |

## Duplicate warning (5 of 6)

| Case | Expected | Got | OK | Note |
| --- | --- | --- | --- | --- |
| Same name with a full-width space, 20 m away | flag | flag | ✓ |  |
| Half-width katakana, 30 m away | flag | flag | ✓ |  |
| Full-width letters, 10 m away | flag | flag | ✓ |  |
| Same name, 60 m away (a second branch) | no flag | no flag | ✓ |  |
| Different name, 10 m away (two shops in one building) | no flag | no flag | ✓ |  |
| Hiragana vs katakana spelling, 15 m away | flag | no flag | ✗ | Known limit: the rule doesn't treat hiragana and katakana as the same. |

## Refill checks (7 of 7)

| Case | Expected | Got | OK | Note |
| --- | --- | --- | --- | --- |
| 199 m from the spot | confirmed | confirmed | ✓ |  |
| 201 m from the spot | too_far | too_far | ✓ |  |
| Same spot again after 4 minutes | duplicate | duplicate | ✓ |  |
| Same spot again after 6 minutes | confirmed | confirmed | ✓ |  |
| Another spot 1 minute later | confirmed | confirmed | ✓ |  |
| 11th refill of the day | daily_limit | daily_limit | ✓ |  |
| First refill after midnight in Tokyo, 10 the day before | confirmed | confirmed | ✓ |  |

## Glossary: with vs without

| Japanese | Our term | Without glossary | With glossary | Note |
| --- | --- | --- | --- | --- |
| マイボトルをお持ちください。 | reusable bottle | Please bring your own water bottle. ✗ | Please bring reusable bottle. ✓ |  |
| 給水スポットとして登録したいです。 | refill spot | I would like to register this as a water refill station. ✗ | I would like to register this as a refill spot. ✓ |  |
| ペットボトルの水は販売していません。 | plastic bottle | We do not sell bottled water. ✗ | We do not sell plastic bottle water. ✓ |  |
| 給水パートナーになりたいです。 | refill partner | I want to be your water supply partner. ✗ | I want to be refill partner. ✓ |  |
| 申請は差し戻しになりました。 | returned for revision | The application has been returned for submission. ✗ | The application has been returned for revision for submission. ✓ |  |

## First English view vs saved

| Visit | Time (ms) |  |  |  |
| --- | --- | --- | --- | --- |
| First view (asks Google) | 973 |  |  |  |
| Next views (saved English) | 5 |  |  |  |

