<?php

// The review queue pages. Japanese is in lang/ja/applications.php.
return [
    'title' => 'Partner applications',
    'columns' => [
        'shop' => 'Shop',
        'type' => 'Type',
        'sent' => 'Sent',
        'reviewer' => 'Reviewer',
    ],
    'store_types' => [
        'cafe' => 'Cafe',
        'bakery' => 'Bakery',
        'shop' => 'General store',
        'hotel' => 'Hotel or guest house',
    ],
    'open' => 'Open',
    'empty' => 'Nothing in this list.',

    'duplicate_title' => 'Possible duplicate.',
    'duplicate_intro' => 'These spots have the same name and are within :radius m:',
    'distance' => '(:distance m away)',
    'sent' => 'sent :time',

    'claim' => 'Claim',
    'decision' => 'Decision',
    'decisions' => [
        'approved' => 'Approve',
        'returned' => 'Send back to the shop',
        'rejected' => 'Reject',
    ],
    'reason_label' => 'Reason (needed to send back or reject)',
    'note_label' => 'Note (needed if the reason is "Other")',
    'save_decision' => 'Save decision',

    'publish_anyway' => 'Publish anyway: it is not the same place',
    'publish' => 'Publish as a refill spot',
    'published_as' => 'Published as',

    'fields' => [
        'type' => 'Type',
        'address' => 'Address',
        'location' => 'Location',
        'description' => 'Description',
        'hours' => 'Opening hours',
        'message' => 'Message from the shop',
        'opening_date' => 'Opening date',
        'contact' => 'Contact',
        'reviewer' => 'Reviewer',
    ],
    'nobody' => 'Nobody yet',

    'history' => 'History',
    'someone' => 'Someone',
    'no_changes' => 'No changes yet.',

    'flash' => [
        'claimed' => 'You are now reviewing this application.',
        'decided' => 'Decision saved: :state.',
        'published' => 'Published as :name.',
    ],

    'errors' => [
        'not_waiting' => 'This application is not waiting for a reviewer any more.',
        'changed' => 'Someone else changed this application first. Reload the page to see the latest.',
        'not_reviewer' => 'Only the staff member reviewing this application can decide on it.',
        'cannot_decide' => "This application can't be decided right now.",
        'not_approved' => 'Only approved applications can be published.',
        'confirm_duplicate' => 'This looks like an existing spot. Tick the box to publish it anyway.',
    ],
];
