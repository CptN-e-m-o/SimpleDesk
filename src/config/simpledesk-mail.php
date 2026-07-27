<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Registered mail drivers
    |--------------------------------------------------------------------------
    */

    'drivers' => [
        'incoming' => [
            /*
             * На следующем этапе:
             *
             * 'imap' =>
             *     App\Services\Mail\Drivers\Imap\ImapMailDriver::class,
             */
        ],

        'outgoing' => [
            'smtp' =>
                App\Services\Admin\Mail\Drivers\Smtp\SmtpMailDriver::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Message and attachment storage
    |--------------------------------------------------------------------------
    */

    'storage' => [
        'disk' => env(
            'MAIL_STORAGE_DISK',
            'local'
        ),

        'raw_messages_path' => env(
            'MAIL_RAW_MESSAGES_PATH',
            'mail/raw'
        ),

        'attachments_path' => env(
            'MAIL_ATTACHMENTS_PATH',
            'mail/attachments'
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mail queues
    |--------------------------------------------------------------------------
    */

    'queues' => [
        'incoming' => env(
            'MAIL_INCOMING_QUEUE',
            'mail-incoming'
        ),

        'outgoing' => env(
            'MAIL_OUTGOING_QUEUE',
            'mail-outgoing'
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Incoming synchronization
    |--------------------------------------------------------------------------
    */

    'sync' => [
        'batch_size' => 100,

        'max_pages_per_run' => 10,

        'default_post_fetch_action' =>
            'mark_read',

        'message_processing_lock_seconds' =>
            600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Outgoing email
    |--------------------------------------------------------------------------
    */

    'outgoing' => [
        /*
         * Максимальный размер одного сохранённого
         * вложения до MIME/base64-кодирования.
         */
        'max_attachment_bytes' => env(
            'MAIL_MAX_ATTACHMENT_BYTES',
            25 * 1024 * 1024,
        ),

        /*
         * Максимальный общий размер всех вложений.
         */
        'max_total_attachment_bytes' => env(
            'MAIL_MAX_TOTAL_ATTACHMENT_BYTES',
            40 * 1024 * 1024,
        ),

        /*
         * Перед отправкой проверяем, что файл
         * не был повреждён или заменён.
         */
        'verify_attachment_checksums' => env(
            'MAIL_VERIFY_ATTACHMENT_CHECKSUMS',
            true,
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Failover
    |--------------------------------------------------------------------------
    */

    'failover' => [
        'failed_channel_cooldown_seconds' =>
            300,

        'sending_lock_seconds' => 600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue jobs
    |--------------------------------------------------------------------------
    */

    'jobs' => [
        'incoming' => [
            'tries' => 5,
            'timeout' => 300,
            'lock_seconds' => 600,

            'backoff' => [
                30,
                120,
                300,
                900,
            ],
        ],

        'outgoing' => [
            'tries' => 5,
            'timeout' => 300,
            'lock_seconds' => 600,

            'backoff' => [
                30,
                120,
                300,
                900,
            ],
        ],
    ],
];
