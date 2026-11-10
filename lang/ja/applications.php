<?php

// 申請の審査画面。英語版は lang/en/applications.php にあります。
return [
    'title' => 'パートナー店舗の申請',
    'columns' => [
        'shop' => '店舗',
        'type' => '業種',
        'sent' => '受付',
        'reviewer' => '担当者',
    ],
    'store_types' => [
        'cafe' => 'カフェ',
        'bakery' => 'ベーカリー',
        'shop' => '雑貨店',
        'hotel' => 'ホテル・ゲストハウス',
    ],
    'open' => '開く',
    'empty' => 'この一覧には何もありません。',

    'duplicate_title' => '重複の可能性があります。',
    'duplicate_intro' => '次のスポットは同じ名前で、:radius m 以内にあります：',
    'distance' => '（:distance m）',
    'sent' => '受付：:time',

    'claim' => '担当する',
    'decision' => '判定',
    'decisions' => [
        'approved' => '承認する',
        'returned' => '店舗に差し戻す',
        'rejected' => '却下する',
    ],
    'reason_label' => '理由（差し戻し・却下の場合は必須）',
    'note_label' => 'メモ（理由が「その他」の場合は必須）',
    'save_decision' => '判定を保存',

    'publish_anyway' => '同じ場所ではないため、このまま公開する',
    'publish' => '給水スポットとして公開',
    'published_as' => '公開済み：',

    'fields' => [
        'type' => '業種',
        'address' => '住所',
        'location' => '位置',
        'description' => '説明',
        'hours' => '営業時間',
        'message' => '店舗からのメッセージ',
        'opening_date' => '開店日',
        'contact' => '連絡先',
        'reviewer' => '担当者',
    ],
    'nobody' => '未割り当て',

    'history' => '履歴',
    'someone' => '不明なユーザー',
    'no_changes' => 'まだ変更はありません。',

    'flash' => [
        'claimed' => 'この申請の担当になりました。',
        'decided' => '判定を保存しました：:state',
        'published' => ':name として公開しました。',
    ],

    'errors' => [
        'not_waiting' => 'この申請はすでに担当者待ちではありません。',
        'changed' => '他の人が先にこの申請を変更しました。ページを再読み込みして最新の内容を確認してください。',
        'not_reviewer' => 'この申請を判定できるのは担当者だけです。',
        'cannot_decide' => 'この申請は今は判定できません。',
        'not_approved' => '公開できるのは承認済みの申請だけです。',
        'confirm_duplicate' => '既存のスポットと重複している可能性があります。このまま公開する場合はチェックを入れてください。',
    ],
];
