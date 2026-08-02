<?php

return [
    'enabled' => env(
        'MAIL_QUARANTINE_ENABLED',
        true
    ),

    'queue' => env(
        'MAIL_QUARANTINE_RETRY_QUEUE',
        'mail-incoming'
    ),

    'queue_connection' => env(
        'MAIL_QUARANTINE_RETRY_QUEUE_CONNECTION'
    ),

    'max_metadata_events' => env(
        'MAIL_QUARANTINE_MAX_METADATA_EVENTS',
        50
    ),

    'command_list_limit' => env(
        'MAIL_QUARANTINE_COMMAND_LIST_LIMIT',
        50
    ),
];
