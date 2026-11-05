<?php

// Rules for checking a refill (or any check-in at a place). Nothing here is
// about water, so another project changes these numbers, not the code.
return [
    'checkins' => [
        'max_distance_m' => (int) env('TAVNIT_MAX_DISTANCE_M', 200),
        'daily_limit' => (int) env('TAVNIT_DAILY_LIMIT', 10),
        'repeat_window_minutes' => (int) env('TAVNIT_REPEAT_WINDOW_MINUTES', 5),
        'timezone' => env('TAVNIT_TIMEZONE', 'Asia/Tokyo'),
    ],

    'review' => [
        // An application is flagged if an existing spot with the same name is this close.
        'duplicate_radius_m' => (int) env('TAVNIT_DUPLICATE_RADIUS_M', 50),

        // Reasons staff can give when sending an application back or rejecting it.
        // The wording lives in lang/en/review.php and lang/ja/review.php.
        'reasons' => ['address_unconfirmed', 'possible_duplicate', 'missing_information', 'other'],
    ],

    'translation' => [
        // How long a page waits for Google before showing the Japanese instead.
        'timeout_seconds' => (int) env('TAVNIT_TRANSLATION_TIMEOUT', 5),

        // Stop calling Google after this many characters in a month
        // (kept below Google's free 500,000 so a mistake can't cost money).
        'monthly_char_limit' => (int) env('TAVNIT_TRANSLATION_MONTHLY_LIMIT', 400000),
    ],

    'stats' => [
        // What one confirmed check-in means to the user (used in Step 6).
        'unit_label' => env('TAVNIT_UNIT_LABEL', 'bottles saved'),
    ],
];
