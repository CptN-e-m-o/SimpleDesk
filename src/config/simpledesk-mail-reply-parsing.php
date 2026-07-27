<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Reply parsing
    |--------------------------------------------------------------------------
    */

    'enabled' => env(
        'MAIL_REPLY_PARSING_ENABLED',
        true
    ),

    'prefer_plain_text' => env(
        'MAIL_REPLY_PARSING_PREFER_PLAIN_TEXT',
        true
    ),

    'strip_quoted_text' => env(
        'MAIL_REPLY_PARSING_STRIP_QUOTED_TEXT',
        true
    ),

    'strip_signatures' => env(
        'MAIL_REPLY_PARSING_STRIP_SIGNATURES',
        true
    ),

    'fallback_to_full_body' => env(
        'MAIL_REPLY_PARSING_FALLBACK_TO_FULL_BODY',
        false
    ),

    'empty_body_fallback' =>
        'Ответ не содержит нового текстового содержимого.',

    'max_body_characters' => env(
        'MAIL_REPLY_PARSING_MAX_BODY_CHARACTERS',
        200000
    ),

    /*
    |--------------------------------------------------------------------------
    | Incoming message filtering
    |--------------------------------------------------------------------------
    */

    'ignore' => [
        'same_mailbox_sender' => env(
            'MAIL_REPLY_PARSING_IGNORE_SAME_MAILBOX_SENDER',
            true
        ),

        'simpledesk_origin' => env(
            'MAIL_REPLY_PARSING_IGNORE_SIMPLEDESK_ORIGIN',
            true
        ),

        'auto_replies' => env(
            'MAIL_REPLY_PARSING_IGNORE_AUTO_REPLIES',
            true
        ),

        'delivery_status' => env(
            'MAIL_REPLY_PARSING_IGNORE_DELIVERY_STATUS',
            true
        ),

        'bulk' => env(
            'MAIL_REPLY_PARSING_IGNORE_BULK',
            true
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom separators
    |--------------------------------------------------------------------------
    |
    | Это точные строки, после которых содержимое считается
    | процитированной частью письма.
    |
    */

    'custom_separators' => [
        // 'Previous conversation:',
    ],
];
