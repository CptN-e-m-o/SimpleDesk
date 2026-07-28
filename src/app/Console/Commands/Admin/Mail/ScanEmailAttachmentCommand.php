<?php

namespace App\Console\Commands\Admin\Mail;

use App\Enums\Admin\Mail\EmailAttachmentScanStatus;
use App\Models\Admin\Mail\EmailAttachment;
use App\Services\Admin\Mail\Antivirus\AttachmentScanDispatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ScanEmailAttachmentCommand extends Command
{
    protected $signature =
        'simpledesk:mail:scan-attachment
        {attachment : Email attachment ID}
        {--force : Rescan clean, infected, or failed attachment}';

    protected $description =
        'Queue antivirus scanning for an email attachment';

    public function handle(
        AttachmentScanDispatcher $dispatcher
    ): int {
        if (
            !(bool) config(
                'simpledesk-mail-antivirus.enabled',
                false
            )
        ) {
            $this->error(
                'Attachment antivirus scanning is disabled.'
            );

            return self::FAILURE;
        }

        $attachmentId = (int) $this->argument(
            'attachment'
        );

        $attachment = EmailAttachment::query()->find(
            $attachmentId
        );

        if ($attachment === null) {
            $this->error(
                "Email attachment [{$attachmentId}] was not found."
            );

            return self::FAILURE;
        }

        $force = (bool) $this->option(
            'force'
        );

        if (
            !$force
            && in_array(
                $attachment->scan_status,
                [
                    EmailAttachmentScanStatus::Clean,
                    EmailAttachmentScanStatus::Infected,
                    EmailAttachmentScanStatus::Failed,
                ],
                true,
            )
        ) {
            $this->warn(
                "Attachment [{$attachment->id}] has status "
                . "[{$attachment->scan_status->value}]. "
                . 'Use --force to rescan it.'
            );

            return self::FAILURE;
        }

        DB::transaction(
            function () use (
                $attachmentId
            ): void {
                $attachment = EmailAttachment::query()
                    ->lockForUpdate()
                    ->findOrFail($attachmentId);

                $attachment->forceFill([
                    'scan_status' => EmailAttachmentScanStatus::Pending,
                    'scan_started_at' => null,
                    'scanned_at' => null,
                    'scan_failure_code' => null,
                    'scan_failure_message' => null,
                ])->save();
            },
            3,
        );

        $dispatcher->releaseClaim(
            $attachmentId
        );

        if (
            !$dispatcher->dispatch(
                $attachmentId
            )
        ) {
            $this->warn(
                "Attachment [{$attachmentId}] scan is already queued."
            );

            return self::SUCCESS;
        }

        $this->info(
            "Attachment [{$attachmentId}] was queued for antivirus scanning."
        );

        return self::SUCCESS;
    }
}
