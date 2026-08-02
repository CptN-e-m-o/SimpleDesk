<?php

return [
    'enabled' => env(
        'MAIL_AUTOMATION_ENABLED',
        true
    ),

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

    'attachment_recovery' => [
        'enabled' => env(
            'MAIL_AUTOMATION_ATTACHMENT_RECOVERY_ENABLED',
            true
        ),

        'interval_minutes' => env(
            'MAIL_AUTOMATION_ATTACHMENT_RECOVERY_INTERVAL_MINUTES',
            5
        ),
    ],

    'health' => [
        'enabled' => env(
            'MAIL_AUTOMATION_HEALTH_ENABLED',
            true
        ),

        'interval_minutes' => env(
            'MAIL_AUTOMATION_HEALTH_INTERVAL_MINUTES',
            15
        ),

        'batch_size' => env(
            'MAIL_AUTOMATION_HEALTH_BATCH_SIZE',
            100
        ),
    ],

    'retention' => [
        'enabled' => env(
            'MAIL_RETENTION_ENABLED',
            false
        ),

        'run_at' => env(
            'MAIL_RETENTION_RUN_AT',
            '02:30'
        ),

        'batch_size' => env(
            'MAIL_RETENTION_BATCH_SIZE',
            500
        ),

        'categories' => [
            'raw_messages' => [
                'enabled' => env(
                    'MAIL_RETENTION_RAW_MESSAGES_ENABLED',
                    true
                ),

                'days' => env(
                    'MAIL_RETENTION_RAW_MESSAGES_DAYS',
                    90
                ),
            ],

            'clean_attachments' => [
                'enabled' => env(
                    'MAIL_RETENTION_CLEAN_ATTACHMENTS_ENABLED',
                    true
                ),

                'days' => env(
                    'MAIL_RETENTION_CLEAN_ATTACHMENTS_DAYS',
                    180
                ),
            ],

            'quarantined_attachments' => [
                'enabled' => env(
                    'MAIL_RETENTION_QUARANTINED_ATTACHMENTS_ENABLED',
                    true
                ),

                'days' => env(
                    'MAIL_RETENTION_QUARANTINED_ATTACHMENTS_DAYS',
                    30
                ),
            ],

            'attempts' => [
                'enabled' => env(
                    'MAIL_RETENTION_ATTEMPTS_ENABLED',
                    true
                ),

                'days' => env(
                    'MAIL_RETENTION_ATTEMPTS_DAYS',
                    90
                ),
            ],

            'quarantines' => [
                'enabled' => env(
                    'MAIL_RETENTION_QUARANTINES_ENABLED',
                    true
                ),

                'days' => env(
                    'MAIL_RETENTION_QUARANTINES_DAYS',
                    90
                ),
            ],

            'messages' => [
                'enabled' => env(
                    'MAIL_RETENTION_MESSAGES_ENABLED',
                    true
                ),

                'days' => env(
                    'MAIL_RETENTION_MESSAGES_DAYS',
                    365
                ),
            ],

            'audit' => [
                'enabled' => env(
                    'MAIL_RETENTION_AUDIT_ENABLED',
                    false
                ),

                'days' => env(
                    'MAIL_RETENTION_AUDIT_DAYS',
                    365
                ),

                'model' => env(
                    'MAIL_RETENTION_AUDIT_MODEL'
                ),

                'timestamp_column' => env(
                    'MAIL_RETENTION_AUDIT_TIMESTAMP_COLUMN',
                    'created_at'
                ),
            ],
        ],
    ],
];
