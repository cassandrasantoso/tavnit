<?php

// Application states, and the reasons staff can give when sending one back or rejecting it.
return [
    'states' => [
        'submitted' => 'Submitted',
        'in_review' => 'In review',
        'returned' => 'Sent back',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'published' => 'Published',
    ],

    'reasons' => [
        'address_unconfirmed' => "The address can't be confirmed",
        'possible_duplicate' => 'This place may already be listed',
        'missing_information' => 'Some information is missing',
        'other' => 'Other (see the note)',
    ],
];
