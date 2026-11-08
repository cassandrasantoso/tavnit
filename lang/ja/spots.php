<?php

// 給水スポットの画面。英語版は lang/en/spots.php にあります。
return [
    'title' => '給水スポット',
    'add_link' => 'スポットを追加',
    'columns' => [
        'name' => '名前',
        'location' => '位置',
        'status' => '状態',
    ],
    'status' => [
        'removed' => '削除済み',
        'active' => '公開中',
        'inactive' => '非公開',
    ],
    'restore' => '元に戻す',
    'edit' => '編集',
    'remove' => '削除',
    'confirm_remove' => 'このスポットを削除しますか？',
    'empty' => 'まだスポットがありません。',

    'create_title' => '給水スポットを追加',
    'add_button' => '追加する',
    'edit_title' => ':name を編集',
    'save' => '保存',

    'form' => [
        'name' => '名前',
        'description' => '説明（日本語）',
        'latitude' => '緯度',
        'longitude' => '経度',
        'show_in_app' => 'アプリに表示する',
    ],

    'flash' => [
        'added' => ':name を追加しました。',
        'saved' => ':name を保存しました。',
        'removed' => ':name を削除しました。一覧から元に戻せます。',
        'restored' => ':name を元に戻しました。',
    ],
];
