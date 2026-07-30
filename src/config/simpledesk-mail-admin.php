<?php

return [
    'actions' => [
        'dispatch_lock_seconds' => env(
            'MAIL_ADMIN_ACTION_DISPATCH_LOCK_SECONDS',
            300,
        ),
    ],

    'attachment_rescan' => [
        'job' => env(
            'MAIL_ADMIN_ATTACHMENT_SCAN_JOB',
            App\Jobs\Admin\Mail\ScanEmailAttachmentJob::class,
        ),
    ],
];
