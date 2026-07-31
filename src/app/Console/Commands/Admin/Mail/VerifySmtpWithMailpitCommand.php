<?php

namespace App\Console\Commands\Admin\Mail;

use App\Data\Admin\Mail\MailAddressData;
use App\Data\Admin\Mail\MailAttachmentData;
use App\Data\Admin\Mail\OutgoingEmailMessageData;
use App\Enums\Admin\Mail\EmailMessageAttemptStatus;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\Mailbox;
use App\Services\Admin\Mail\Integration\MailpitApiClient;
use App\Services\Admin\Mail\OutgoingEmailQueueService;
use App\Services\Admin\Mail\OutgoingMailFailoverService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class VerifySmtpWithMailpitCommand extends Command
{
    protected $signature = 'simpledesk:mail:verify-smtp
        {mailbox : Mailbox ID}
        {recipient : Recipient email address}
        {--mode=direct : direct or queue}
        {--timeout= : Wait timeout in seconds}
        {--without-attachment : Do not include a verification attachment}
        {--delete : Delete the Mailpit message after success}';

    protected $description =
        'Send a real SMTP message through SimpleDesk and verify it via Mailpit API';

    public function handle(
        OutgoingEmailQueueService $queue,
        OutgoingMailFailoverService $sender,
        MailpitApiClient $mailpit,
    ): int {
        $emailMessage = null;

        try {
            $mailbox = $this->mailbox();
            $recipient = $this->recipient();
            $mode = $this->mode();
            $timeout = $this->timeoutSeconds();

            $info = $mailpit->info();

            $this->line(
                'Mailpit API is available'
                . (
                isset($info['Version'])
                    ? ": {$info['Version']}"
                    : '.'
                )
            );

            $verification = $this->verificationData(
                recipient: $recipient,
                includeAttachment:
                !$this->option('without-attachment'),
            );

            $emailMessage = $queue->queue(
                mailbox: $mailbox,
                message: $verification['message'],
                dispatch: $mode === 'queue',
            );

            $this->info(
                "Email message [{$emailMessage->id}] was prepared "
                . "with status [{$emailMessage->status->value}]."
            );

            if ($mode === 'direct') {
                $emailMessage = $this->waitUntilDirectSendIsPossible(
                    $emailMessage,
                    $timeout
                );

                if (
                    $emailMessage->status
                    === EmailMessageStatus::Queued
                ) {
                    $sender->send($emailMessage);
                }
            }

            $emailMessage = $this->waitUntilFinished(
                $emailMessage,
                $timeout
            );

            $this->assertDatabaseDeliverySucceeded(
                $emailMessage
            );

            $mailpitMessage = $mailpit->waitForSubject(
                $verification['subject'],
                $timeout
            );

            $this->assertMailpitMessage(
                mailpit: $mailpit,
                mailpitMessage: $mailpitMessage,
                mailbox: $mailbox,
                recipient: $recipient,
                emailMessage: $emailMessage,
                subject: $verification['subject'],
                token: $verification['token'],
                attachmentName:
                $verification['attachment_name'],
                attachmentContent:
                $verification['attachment_content'],
            );

            $this->printResult(
                emailMessage: $emailMessage,
                mailpitMessage: $mailpitMessage,
                mode: $mode,
            );

            if ((bool) $this->option('delete')) {
                $mailpit->deleteMessage(
                    (string) $mailpitMessage['ID']
                );

                $this->line(
                    'Captured Mailpit message was deleted.'
                );
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->newLine();
            $this->error($exception->getMessage());

            if ($emailMessage instanceof EmailMessage) {
                $this->printFailureState(
                    $emailMessage
                );
            }

            return self::FAILURE;
        }
    }

    private function mailbox(): Mailbox
    {
        $mailbox = Mailbox::query()->find(
            $this->argument('mailbox')
        );

        if ($mailbox === null) {
            throw new RuntimeException(
                'Mailbox was not found.'
            );
        }

        if (!$mailbox->is_active) {
            throw new RuntimeException(
                "Mailbox [{$mailbox->id}] is disabled."
            );
        }

        return $mailbox;
    }

    private function recipient(): string
    {
        $recipient = trim(
            (string) $this->argument('recipient')
        );

        if (
            filter_var(
                $recipient,
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            throw new RuntimeException(
                "Recipient [{$recipient}] "
                . 'is not a valid email address.'
            );
        }

        return $recipient;
    }

    private function mode(): string
    {
        $mode = strtolower(
            trim((string) $this->option('mode'))
        );

        if (
            !in_array(
                $mode,
                [
                    'direct',
                    'queue',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'The --mode option must be direct or queue.'
            );
        }

        return $mode;
    }

    private function timeoutSeconds(): int
    {
        $option = $this->option('timeout');

        if ($option !== null && $option !== '') {
            return max(1, (int) $option);
        }

        return max(
            1,
            (int) config(
                'simpledesk-mail-integration.mailpit.delivery_timeout_seconds',
                30
            )
        );
    }

    private function verificationData(
        string $recipient,
        bool $includeAttachment,
    ): array {
        $token = (string) Str::uuid();

        $subject =
            "[SimpleDesk SMTP verification] {$token}";

        $text =
            "SimpleDesk SMTP verification token: {$token}";

        $html =
            '<p><strong>SimpleDesk SMTP verification</strong></p>'
            . "<p>Token: {$token}</p>";

        $attachmentName = null;
        $attachmentContent = null;
        $attachments = [];

        if ($includeAttachment) {
            $attachmentName =
                "simpledesk-smtp-{$token}.txt";

            $attachmentContent =
                "SimpleDesk attachment token: {$token}\n";

            $attachments[] = new MailAttachmentData(
                fileName: $attachmentName,
                mimeType: 'text/plain',
                size: strlen($attachmentContent),
                content: $attachmentContent,
            );
        }

        return [
            'token' => $token,
            'subject' => $subject,
            'attachment_name' => $attachmentName,
            'attachment_content' => $attachmentContent,

            'message' => new OutgoingEmailMessageData(
                idempotencyKey:
                "smtp-verification:{$token}",

                from: null,

                to: [
                    new MailAddressData(
                        $recipient
                    ),
                ],

                cc: [],
                bcc: [],
                replyTo: [],

                subject: $subject,
                textBody: $text,
                htmlBody: $html,

                headers: [
                    'X-SimpleDesk-Verification' =>
                        $token,
                ],

                attachments: $attachments,

                metadata: [
                    'source' =>
                        'smtp_mailpit_verification',

                    'verification_token' =>
                        $token,
                ],
            ),
        ];
    }

    private function waitUntilDirectSendIsPossible(
        EmailMessage $emailMessage,
        int $timeoutSeconds,
    ): EmailMessage {
        $deadline =
            microtime(true) + $timeoutSeconds;

        do {
            $emailMessage->refresh();

            if (
                in_array(
                    $emailMessage->status,
                    [
                        EmailMessageStatus::Queued,
                        EmailMessageStatus::Sent,
                        EmailMessageStatus::Delivered,
                    ],
                    true
                )
            ) {
                return $emailMessage;
            }

            $this->throwForTerminalFailure(
                $emailMessage
            );

            usleep(250000);
        } while (
            microtime(true) < $deadline
        );

        throw new RuntimeException(
            "Email message [{$emailMessage->id}] "
            . 'did not become ready for direct sending '
            . "within {$timeoutSeconds} seconds. "
            . 'Current status: '
            . "{$emailMessage->status->value}."
        );
    }

    private function waitUntilFinished(
        EmailMessage $emailMessage,
        int $timeoutSeconds,
    ): EmailMessage {
        $deadline =
            microtime(true) + $timeoutSeconds;

        do {
            $emailMessage->refresh();

            if (
                in_array(
                    $emailMessage->status,
                    [
                        EmailMessageStatus::Sent,
                        EmailMessageStatus::Delivered,
                    ],
                    true
                )
            ) {
                return $emailMessage;
            }

            $this->throwForTerminalFailure(
                $emailMessage
            );

            usleep(250000);
        } while (
            microtime(true) < $deadline
        );

        throw new RuntimeException(
            "Email message [{$emailMessage->id}] "
            . "was not sent within {$timeoutSeconds} seconds. "
            . 'Current status: '
            . "{$emailMessage->status->value}."
        );
    }

    private function throwForTerminalFailure(
        EmailMessage $emailMessage
    ): void {
        if (
            !in_array(
                $emailMessage->status,
                [
                    EmailMessageStatus::Failed,
                    EmailMessageStatus::Rejected,
                    EmailMessageStatus::Bounced,
                    EmailMessageStatus::Complained,
                ],
                true
            )
        ) {
            return;
        }

        throw new RuntimeException(
            "Email message [{$emailMessage->id}] "
            . 'ended with status '
            . "[{$emailMessage->status->value}]: "
            . (
                $emailMessage->failure_message
                ?? 'no failure message'
            )
        );
    }

    private function assertDatabaseDeliverySucceeded(
        EmailMessage $emailMessage
    ): void {
        $emailMessage->load([
            'mailboxChannel',
            'attempts',
            'attachments',
        ]);

        if (
            !in_array(
                $emailMessage->status,
                [
                    EmailMessageStatus::Sent,
                    EmailMessageStatus::Delivered,
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Database message status is not sent or delivered.'
            );
        }

        if (
            $emailMessage->mailbox_channel_id
            === null
        ) {
            throw new RuntimeException(
                'Sent message has no selected mailbox channel.'
            );
        }

        if ($emailMessage->sent_at === null) {
            throw new RuntimeException(
                'Sent message has no sent_at timestamp.'
            );
        }

        $successfulAttempt =
            $emailMessage->attempts->first(
                fn ($attempt): bool =>
                    $attempt->status
                    === EmailMessageAttemptStatus::Succeeded
            );

        if ($successfulAttempt === null) {
            throw new RuntimeException(
                'No successful delivery attempt was stored.'
            );
        }
    }

    private function assertMailpitMessage(
        MailpitApiClient $mailpit,
        array $mailpitMessage,
        Mailbox $mailbox,
        string $recipient,
        EmailMessage $emailMessage,
        string $subject,
        string $token,
        ?string $attachmentName,
        ?string $attachmentContent,
    ): void {
        $this->assertSame(
            $subject,
            $mailpitMessage['Subject'] ?? null,
            'Mailpit subject does not match.'
        );

        $this->assertSame(
            strtolower($mailbox->email_address),
            strtolower(
                (string) data_get(
                    $mailpitMessage,
                    'From.Address'
                )
            ),
            'Mailpit sender does not match mailbox address.'
        );

        $toAddresses = collect(
            $mailpitMessage['To'] ?? []
        )
            ->map(
                fn (array $address): string =>
                strtolower(
                    (string) (
                        $address['Address']
                        ?? ''
                    )
                )
            )
            ->all();

        if (
            !in_array(
                strtolower($recipient),
                $toAddresses,
                true
            )
        ) {
            throw new RuntimeException(
                'Mailpit recipient list does not contain '
                . 'the expected recipient.'
            );
        }

        if (
            !str_contains(
                (string) (
                    $mailpitMessage['Text']
                    ?? ''
                ),
                $token
            )
        ) {
            throw new RuntimeException(
                'Mailpit plain-text body does not '
                . 'contain the verification token.'
            );
        }

        if (
            !str_contains(
                (string) (
                    $mailpitMessage['HTML']
                    ?? ''
                ),
                $token
            )
        ) {
            throw new RuntimeException(
                'Mailpit HTML body does not contain '
                . 'the verification token.'
            );
        }

        $this->assertSame(
            $this->normalizeMessageId(
                $emailMessage->internet_message_id
            ),
            $this->normalizeMessageId(
                is_string(
                    $mailpitMessage['MessageID']
                    ?? null
                )
                    ? $mailpitMessage['MessageID']
                    : null
            ),
            'Mailpit Message-ID does not match '
            . 'the database value.'
        );

        if (
            $attachmentName === null
            || $attachmentContent === null
        ) {
            return;
        }

        $attachment = collect(
            $mailpitMessage['Attachments'] ?? []
        )->first(
            fn (array $attachment): bool =>
                ($attachment['FileName'] ?? null)
                === $attachmentName
        );

        if (!is_array($attachment)) {
            throw new RuntimeException(
                "Mailpit attachment [{$attachmentName}] "
                . 'was not found.'
            );
        }

        $partId = $attachment['PartID'] ?? null;
        $messageId = $mailpitMessage['ID'] ?? null;

        if (
            !is_string($partId)
            || !is_string($messageId)
        ) {
            throw new RuntimeException(
                'Mailpit attachment identifiers are missing.'
            );
        }

        $capturedContent =
            $mailpit->attachmentContent(
                $messageId,
                $partId
            );

        $this->assertSame(
            $attachmentContent,
            $capturedContent,
            'Mailpit attachment content does not match.'
        );

        $mailpitChecksum = data_get(
            $attachment,
            'Checksums.SHA256'
        );

        if (
            is_string($mailpitChecksum)
            && $mailpitChecksum !== ''
        ) {
            $this->assertSame(
                hash(
                    'sha256',
                    $attachmentContent
                ),
                strtolower($mailpitChecksum),
                'Mailpit attachment SHA-256 does not match.'
            );
        }
    }

    private function printResult(
        EmailMessage $emailMessage,
        array $mailpitMessage,
        string $mode,
    ): void {
        $emailMessage->loadMissing(
            'mailboxChannel'
        );

        $this->newLine();
        $this->info(
            'SMTP verification passed.'
        );

        $this->table(
            [
                'Check',
                'Value',
            ],
            [
                [
                    'Mode',
                    $mode,
                ],
                [
                    'Database message ID',
                    (string) $emailMessage->id,
                ],
                [
                    'Database status',
                    $emailMessage->status->value,
                ],
                [
                    'Channel ID',
                    (string) $emailMessage
                        ->mailbox_channel_id,
                ],
                [
                    'Driver',
                    $emailMessage->driver?->value
                    ?? 'null',
                ],
                [
                    'Mailpit message ID',
                    (string) (
                        $mailpitMessage['ID']
                        ?? ''
                    ),
                ],
                [
                    'Internet Message-ID',
                    (string) $emailMessage
                        ->internet_message_id,
                ],
                [
                    'Subject',
                    (string) $emailMessage->subject,
                ],
                [
                    'Attachments',
                    (string) $emailMessage
                        ->attachments()
                        ->count(),
                ],
            ]
        );
    }

    private function printFailureState(
        EmailMessage $emailMessage
    ): void {
        $emailMessage->refresh();
        $emailMessage->load('attempts');

        $this->table(
            [
                'Field',
                'Value',
            ],
            [
                [
                    'Message ID',
                    (string) $emailMessage->id,
                ],
                [
                    'Status',
                    $emailMessage->status->value,
                ],
                [
                    'Failure code',
                    $emailMessage->failure_code
                    ?? 'null',
                ],
                [
                    'Failure message',
                    $emailMessage->failure_message
                    ?? 'null',
                ],
            ]
        );

        foreach (
            $emailMessage->attempts
            as $attempt
        ) {
            $this->line(
                "Attempt #{$attempt->attempt_number}: "
                . "{$attempt->status->value}; "
                . (
                    $attempt->error_code
                    ?? 'no error code'
                )
                . '; '
                . (
                    $attempt->error_message
                    ?? 'no error message'
                )
            );
        }
    }

    private function assertSame(
        mixed $expected,
        mixed $actual,
        string $message,
    ): void {
        if ($expected !== $actual) {
            throw new RuntimeException(
                $message
                . ' Expected: '
                . var_export($expected, true)
                . '; actual: '
                . var_export($actual, true)
            );
        }
    }

    private function normalizeMessageId(
        ?string $messageId
    ): ?string {
        if ($messageId === null) {
            return null;
        }

        $messageId = trim(
            trim($messageId),
            '<>'
        );

        return $messageId !== ''
            ? $messageId
            : null;
    }
}
