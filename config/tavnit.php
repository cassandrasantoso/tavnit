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

    'stats' => [
        // What one confirmed check-in means to the user (used in Step 6).
        'unit_label' => env('TAVNIT_UNIT_LABEL', 'bottles saved'),
    ],
];
