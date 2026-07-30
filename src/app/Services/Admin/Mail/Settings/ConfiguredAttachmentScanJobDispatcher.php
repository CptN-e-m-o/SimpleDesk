<?php

namespace App\Services\Admin\Mail\Settings;

use App\Exceptions\Admin\Mail\MailAdminActionException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Throwable;

class ConfiguredAttachmentScanJobDispatcher
{
    public function dispatch(
        int $attachmentId
    ): void {
        $jobClass = config(
            'simpledesk-mail-admin.attachment_rescan.job'
        );

        if (
            !is_string($jobClass)
            || trim($jobClass) === ''
        ) {
            throw new MailAdminActionException(
                message: 'The attachment scan job is not configured.',
                errorCode: 'attachment_scan_job_not_configured',
                field: 'attachment',
            );
        }

        $jobClass = trim($jobClass);

        if (!class_exists($jobClass)) {
            throw new MailAdminActionException(
                message: "The configured attachment scan job [{$jobClass}] does not exist.",
                errorCode: 'attachment_scan_job_missing',
                field: 'attachment',
            );
        }

        try {
            $job = new $jobClass($attachmentId);
        } catch (Throwable $exception) {
            throw new MailAdminActionException(
                message: "The configured attachment scan job [{$jobClass}] could not be created.",
                errorCode: 'attachment_scan_job_invalid',
                field: 'attachment',
                previous: $exception,
            );
        }

        if (!$job instanceof ShouldQueue) {
            throw new MailAdminActionException(
                message: "The configured attachment scan job [{$jobClass}] must implement ShouldQueue.",
                errorCode: 'attachment_scan_job_not_queueable',
                field: 'attachment',
            );
        }

        $pendingDispatch = dispatch($job);

        $connection = config(
            'simpledesk-mail-antivirus.queue_connection'
        );

        if (
            is_string($connection)
            && trim($connection) !== ''
        ) {
            $pendingDispatch->onConnection(
                trim($connection)
            );
        }

        $queue = trim((string) config(
            'simpledesk-mail-antivirus.queue',
            'mail-antivirus',
        ));

        if ($queue !== '') {
            $pendingDispatch->onQueue($queue);
        }

        $pendingDispatch->afterCommit();
    }
}
