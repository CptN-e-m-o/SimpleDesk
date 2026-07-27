<?php

namespace App\Console\Commands\Mail;

use App\Data\Admin\Mail\MailAddressData;
use App\Data\Admin\Mail\MailAttachmentData;
use App\Data\Admin\Mail\OutgoingEmailMessageData;
use App\Models\Admin\Mail\Mailbox;
use App\Services\Admin\Mail\OutgoingEmailQueueService;
use App\Services\Admin\Mail\OutgoingMailFailoverService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class QueueTestEmailCommand extends Command
{
    protected $signature = 'simpledesk:mail:send-test
        {mailbox : Mailbox ID}
        {recipient : Recipient email address}
        {--subject=SimpleDesk SMTP test : Email subject}
        {--text=This is a test email sent by SimpleDesk. : Plain-text body}
        {--html= : Optional HTML body}
        {--attach=* : Local file path to attach}
        {--now : Send immediately instead of dispatching a queue job}';

    protected $description =
        'Create and send a test email through the SimpleDesk mail pipeline';

    public function handle(
        OutgoingEmailQueueService $queue,
        OutgoingMailFailoverService $sender,
    ): int {
        $mailbox = Mailbox::query()->find(
            $this->argument('mailbox')
        );

        if ($mailbox === null) {
            $this->error(
                'Mailbox was not found.'
            );

            return self::FAILURE;
        }

        try {
            $message = new OutgoingEmailMessageData(
                idempotencyKey:
                'smtp-test:' . Str::uuid(),
                from: null,
                to: [
                    new MailAddressData(
                        address: (string) $this
                            ->argument('recipient')
                    ),
                ],
                cc: [],
                bcc: [],
                replyTo: [],
                subject: (string) $this
                    ->option('subject'),
                textBody: (string) $this
                    ->option('text'),
                htmlBody:
                $this->nullableOption('html'),
                attachments:
                $this->attachments(),
                metadata: [
                    'source' =>
                        'artisan_test_command',
                ],
            );

            $sendNow = (bool) $this
                ->option('now');

            $emailMessage = $queue->queue(
                mailbox: $mailbox,
                message: $message,
                dispatch: !$sendNow,
            );

            $this->info(
                "Email message [{$emailMessage->id}] "
                . 'was prepared.'
            );

            if (!$sendNow) {
                $this->line(
                    'The message was queued. '
                    . 'Run a worker for the configured '
                    . 'outgoing queue.'
                );

                return self::SUCCESS;
            }

            $result = $sender->send(
                $emailMessage
            );

            $this->info(
                'Test email was sent successfully.'
            );

            $this->line(
                'Internet Message-ID: '
                . (
                    $result->internetMessageId
                    ?? 'not available'
                )
            );

            $this->line(
                'Transport message ID: '
                . (
                    $result->externalMessageId
                    ?? 'not available'
                )
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error(
                $exception->getMessage()
            );

            return self::FAILURE;
        }
    }

    /**
     * @return array<int, MailAttachmentData>
     */
    private function attachments(): array
    {
        $attachments = [];

        foreach (
            (array) $this->option('attach')
            as $path
        ) {
            $path = (string) $path;

            if (
                !is_file($path)
                || !is_readable($path)
            ) {
                throw new RuntimeException(
                    "Attachment [{$path}] "
                    . 'is not a readable file.'
                );
            }

            $mimeType = mime_content_type(
                $path
            );

            $size = filesize($path);

            if ($size === false) {
                throw new RuntimeException(
                    'Unable to determine size '
                    . "of attachment [{$path}]."
                );
            }

            $attachments[] =
                new MailAttachmentData(
                    fileName: basename($path),
                    mimeType:
                    is_string($mimeType)
                        ? $mimeType
                        : 'application/octet-stream',
                    size: $size,
                    temporaryPath: $path,
                );
        }

        return $attachments;
    }

    private function nullableOption(
        string $name
    ): ?string {
        $value = $this->option($name);

        if (!is_string($value)) {
            return null;
        }

        return $value !== ''
            ? $value
            : null;
    }
}
