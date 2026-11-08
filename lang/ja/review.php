<?php

// 申請の状態と、差し戻し・却下の理由。英語版は lang/en/review.php にあります。
return [
    'states' => [
        'submitted' => '受付済み',
        'in_review' => '審査中',
        'returned' => '差し戻し',
        'approved' => '承認済み',
        'rejected' => '却下',
        'published' => '公開済み',
    ],

    'reasons' => [
        'address_unconfirmed' => '住所を確認できませんでした',
        'possible_duplicate' => 'すでに登録されている可能性があります',
        'missing_information' => '情報が不足しています',
        'other' => 'その他（メモをご確認ください）',
    ],
];
