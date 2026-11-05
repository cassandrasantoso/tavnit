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
    ],

    'stats' => [
        // What one confirmed check-in means to the user (used in Step 6).
        'unit_label' => env('TAVNIT_UNIT_LABEL', 'bottles saved'),
    ],
];
