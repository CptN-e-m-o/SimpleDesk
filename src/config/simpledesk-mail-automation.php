<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mail automation
    |--------------------------------------------------------------------------
    */

    'enabled' => env(
        'MAIL_AUTOMATION_ENABLED',
        true
    ),

    /*
    |--------------------------------------------------------------------------
    | Scheduler locking
    |--------------------------------------------------------------------------
    */

    'scheduler' => [
        'on_one_server' => env(
            'MAIL_AUTOMATION_ON_ONE_SERVER',
            true
        ),

        'overlap_expiration_minutes' => env(
            'MAIL_AUTOMATION_OVERLAP_EXPIRATION_MINUTES',
            10
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Incoming mailbox synchronization
    |--------------------------------------------------------------------------
    */

    'sync' => [
        'enabled' => env(
            'MAIL_AUTOMATION_SYNC_ENABLED',
            true
        ),

        'interval_minutes' => env(
            'MAIL_AUTOMATION_SYNC_INTERVAL_MINUTES',
            1
        ),

        'batch_size' => env(
            'MAIL_AUTOMATION_SYNC_BATCH_SIZE',
            100
        ),

        'queue' => env(
            'MAIL_AUTOMATION_SYNC_QUEUE',
            'mail-incoming'
        ),

        'queue_connection' => env(
            'MAIL_AUTOMATION_SYNC_QUEUE_CONNECTION'
        ),

        'dispatch_lock_seconds' => env(
            'MAIL_AUTOMATION_SYNC_DISPATCH_LOCK_SECONDS',
            55
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pipeline recovery
    |--------------------------------------------------------------------------
    */

    'recovery' => [
        'enabled' => env(
            'MAIL_AUTOMATION_RECOVERY_ENABLED',
            true
        ),

        'interval_minutes' => env(
            'MAIL_AUTOMATION_RECOVERY_INTERVAL_MINUTES',
            5
        ),

        'batch_size' => env(
            'MAIL_AUTOMATION_RECOVERY_BATCH_SIZE',
            100
        ),

        'grace_seconds' => env(
            'MAIL_AUTOMATION_RECOVERY_GRACE_SECONDS',
            120
        ),

        'incoming_processing_timeout_seconds' => env(
            'MAIL_AUTOMATION_INCOMING_PROCESSING_TIMEOUT_SECONDS',
            900
        ),

        'outgoing_sending_timeout_seconds' => env(
            'MAIL_AUTOMATION_OUTGOING_SENDING_TIMEOUT_SECONDS',
            900
        ),

        'dispatch_lock_seconds' => env(
            'MAIL_AUTOMATION_RECOVERY_DISPATCH_LOCK_SECONDS',
            300
        ),

        'incoming_queue' => env(
            'MAIL_AUTOMATION_RECOVERY_INCOMING_QUEUE',
            'mail-incoming'
        ),

        'outgoing_queue' => env(
            'MAIL_AUTOMATION_RECOVERY_OUTGOING_QUEUE',
            'mail-outgoing'
        ),

        'queue_connection' => env(
            'MAIL_AUTOMATION_RECOVERY_QUEUE_CONNECTION'
        ),
    ],
];
