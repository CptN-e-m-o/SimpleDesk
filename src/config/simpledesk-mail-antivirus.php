<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Attachment antivirus scanning
    |--------------------------------------------------------------------------
    */

    'enabled' => env(
        'MAIL_ANTIVIRUS_ENABLED',
        false
    ),

    'driver' => env(
        'MAIL_ANTIVIRUS_DRIVER',
        'clamav'
    ),

    'verify_checksums' => env(
        'MAIL_ANTIVIRUS_VERIFY_CHECKSUMS',
        true
    ),

    'processing_lock_seconds' => env(
        'MAIL_ANTIVIRUS_PROCESSING_LOCK_SECONDS',
        300
    ),

    /*
    |--------------------------------------------------------------------------
    | ClamAV daemon
    |--------------------------------------------------------------------------
    */

    'clamav' => [
        'host' => env(
            'MAIL_ANTIVIRUS_CLAMAV_HOST',
            'clamav'
        ),

        'port' => env(
            'MAIL_ANTIVIRUS_CLAMAV_PORT',
            3310
        ),

        'connection_timeout_seconds' => env(
            'MAIL_ANTIVIRUS_CLAMAV_CONNECTION_TIMEOUT_SECONDS',
            5
        ),

        'read_timeout_seconds' => env(
            'MAIL_ANTIVIRUS_CLAMAV_READ_TIMEOUT_SECONDS',
            60
        ),

        'chunk_bytes' => env(
            'MAIL_ANTIVIRUS_CLAMAV_CHUNK_BYTES',
            8192
        ),

        'max_stream_bytes' => env(
            'MAIL_ANTIVIRUS_CLAMAV_MAX_STREAM_BYTES',
            25 * 1024 * 1024
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scan queue
    |--------------------------------------------------------------------------
    */

    'queue' => [
        'name' => env(
            'MAIL_ANTIVIRUS_QUEUE',
            'mail-antivirus'
        ),

        'connection' => env(
            'MAIL_ANTIVIRUS_QUEUE_CONNECTION'
        ),

        'tries' => env(
            'MAIL_ANTIVIRUS_QUEUE_TRIES',
            5
        ),

        'timeout' => env(
            'MAIL_ANTIVIRUS_QUEUE_TIMEOUT',
            120
        ),

        'lock_seconds' => env(
            'MAIL_ANTIVIRUS_QUEUE_LOCK_SECONDS',
            300
        ),

        'dispatch_lock_seconds' => env(
            'MAIL_ANTIVIRUS_DISPATCH_LOCK_SECONDS',
            300
        ),

        'backoff' => [
            30,
            120,
            300,
            900,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Recovery
    |--------------------------------------------------------------------------
    */

    'recovery' => [
        'enabled' => env(
            'MAIL_ANTIVIRUS_RECOVERY_ENABLED',
            true
        ),

        'interval_minutes' => env(
            'MAIL_ANTIVIRUS_RECOVERY_INTERVAL_MINUTES',
            5
        ),

        'batch_size' => env(
            'MAIL_ANTIVIRUS_RECOVERY_BATCH_SIZE',
            100
        ),

        'on_one_server' => env(
            'MAIL_ANTIVIRUS_RECOVERY_ON_ONE_SERVER',
            true
        ),

        'overlap_expiration_minutes' => env(
            'MAIL_ANTIVIRUS_RECOVERY_OVERLAP_EXPIRATION_MINUTES',
            10
        ),

        'grace_seconds' => env(
            'MAIL_ANTIVIRUS_RECOVERY_GRACE_SECONDS',
            120
        ),

        'stuck_timeout_seconds' => env(
            'MAIL_ANTIVIRUS_STUCK_TIMEOUT_SECONDS',
            600
        ),
    ],
];
