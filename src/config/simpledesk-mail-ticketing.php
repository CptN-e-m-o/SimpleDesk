<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Incoming email ticketing
    |--------------------------------------------------------------------------
    */

    'enabled' => env(
        'MAIL_TICKETING_ENABLED',
        true
    ),

    'queue' => env(
        'MAIL_TICKETING_QUEUE',
        'mail-incoming'
    ),

    'queue_connection' => env(
        'MAIL_TICKETING_QUEUE_CONNECTION'
    ),

    'processing_lock_seconds' => env(
        'MAIL_TICKETING_PROCESSING_LOCK_SECONDS',
        600
    ),

    /*
    |--------------------------------------------------------------------------
    | Requesters
    |--------------------------------------------------------------------------
    */

    'auto_create_requesters' => env(
        'MAIL_TICKETING_AUTO_CREATE_REQUESTERS',
        true
    ),

    'requester_role' => env(
        'MAIL_TICKETING_REQUESTER_ROLE',
        'user'
    ),

    /*
    |--------------------------------------------------------------------------
    | New tickets
    |--------------------------------------------------------------------------
    */

    'ticket_number_prefix' => env(
        'MAIL_TICKET_NUMBER_PREFIX',
        'SD'
    ),

    'default_status' => 'open',

    'default_priority' => 'medium',

    'subject_fallback' => 'Обращение по электронной почте',

    'empty_body_fallback' => 'Письмо не содержит текстового содержимого.',

    /*
    |--------------------------------------------------------------------------
    | Incoming customer replies
    |--------------------------------------------------------------------------
    */

    'customer_reply_status' => 'open',

    'reopen_resolved_tickets' => true,

    'closed_ticket_action' => 'new_ticket',

    /*
    |--------------------------------------------------------------------------
    | Outgoing agent replies
    |--------------------------------------------------------------------------
    */

    'outgoing_replies' => [
        'enabled' => env(
            'MAIL_TICKETING_OUTGOING_REPLIES_ENABLED',
            true
        ),

        'queue' => env(
            'MAIL_TICKETING_OUTGOING_QUEUE',
            'mail-outgoing'
        ),

        'queue_connection' => env(
            'MAIL_TICKETING_OUTGOING_QUEUE_CONNECTION'
        ),

        'subject_prefix' => 'Re: ',

        'max_references' => 50,

        'include_agent_signature' => env(
            'MAIL_TICKETING_INCLUDE_AGENT_SIGNATURE',
            true
        ),

        'include_department_signature' => env(
            'MAIL_TICKETING_INCLUDE_DEPARTMENT_SIGNATURE',
            true
        ),

        'job' => [
            'tries' => 5,
            'timeout' => 120,
            'lock_seconds' => 300,

            'backoff' => [
                15,
                60,
                180,
                600,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Incoming processing job
    |--------------------------------------------------------------------------
    */

    'job' => [
        'tries' => 5,
        'timeout' => 120,
        'lock_seconds' => 300,

        'backoff' => [
            15,
            60,
            180,
            600,
        ],
    ],
];
