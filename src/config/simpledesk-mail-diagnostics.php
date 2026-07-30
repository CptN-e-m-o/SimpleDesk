<?php

return [
    'recent_errors_limit' => env(
        'MAIL_DIAGNOSTICS_RECENT_ERRORS_LIMIT',
        10
    ),

    'recent_messages_limit' => env(
        'MAIL_DIAGNOSTICS_RECENT_MESSAGES_LIMIT',
        10
    ),

    'stale' => [
        'preparing_seconds' => env(
            'MAIL_DIAGNOSTICS_PREPARING_SECONDS',
            900
        ),

        'queued_seconds' => env(
            'MAIL_DIAGNOSTICS_QUEUED_SECONDS',
            900
        ),

        'processing_seconds' => env(
            'MAIL_DIAGNOSTICS_PROCESSING_SECONDS',
            900
        ),

        'sending_seconds' => env(
            'MAIL_DIAGNOSTICS_SENDING_SECONDS',
            900
        ),

        'attachment_pending_seconds' => env(
            'MAIL_DIAGNOSTICS_ATTACHMENT_PENDING_SECONDS',
            900
        ),

        'sync_seconds' => env(
            'MAIL_DIAGNOSTICS_SYNC_SECONDS',
            1800
        ),
    ],
];
