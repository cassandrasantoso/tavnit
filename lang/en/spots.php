<?php

// The refill spots pages. Japanese is in lang/ja/spots.php.
return [
    'title' => 'Refill spots',
    'add_link' => 'Add a spot',
    'columns' => [
        'name' => 'Name',
        'location' => 'Location',
        'status' => 'Status',
    ],
    'status' => [
        'removed' => 'Removed',
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],
    'restore' => 'Restore',
    'edit' => 'Edit',
    'remove' => 'Remove',
    'confirm_remove' => 'Remove this spot?',
    'empty' => 'No spots yet.',

    'create_title' => 'Add a refill spot',
    'add_button' => 'Add spot',
    'edit_title' => 'Edit :name',
    'save' => 'Save',

    'form' => [
        'name' => 'Name',
        'description' => 'Description (Japanese)',
        'latitude' => 'Latitude',
        'longitude' => 'Longitude',
        'show_in_app' => 'Show in the app',
    ],

    'flash' => [
        'added' => 'Added :name.',
        'saved' => 'Saved :name.',
        'removed' => 'Removed :name. You can restore it from the list.',
        'restored' => 'Restored :name.',
    ],
];
