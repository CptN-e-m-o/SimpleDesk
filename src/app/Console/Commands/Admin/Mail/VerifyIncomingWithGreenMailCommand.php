<?php

namespace App\Console\Commands\Admin\Mail;

use App\Data\Admin\Mail\MailAddressData;
use App\Data\Admin\Mail\OutgoingEmailMessageData;
use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Enums\Admin\Mail\MailboxDriver;
use App\Jobs\Admin\Mail\SyncIncomingMailboxJob;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\Mailbox;
use App\Models\Ticket;
use App\Services\Admin\Mail\IncomingMailboxSyncService;
use App\Services\Admin\Mail\OutgoingEmailQueueService;
use App\Services\Admin\Mail\OutgoingMailFailoverService;
use App\Services\Admin\Mail\Ticketing\InboundEmailTicketProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class VerifyIncomingWithGreenMailCommand extends Command
{
    protected $signature = 'simpledesk:mail:verify-incoming
        {senderMailbox : Mailbox ID used as the simulated customer}
        {targetMailbox : Mailbox ID synchronized through IMAP}
        {--mode=queue : direct or queue}
        {--timeout=60 : Wait timeout in seconds}
        {--skip-duplicate-check : Do not run the second IMAP synchronization}';

    protected $description =
        'Send a real email through GreenMail and verify IMAP ingestion and ticket creation';

    public function handle(
        OutgoingEmailQueueService $outgoingQueue,
        OutgoingMailFailoverService $outgoingSender,
        IncomingMailboxSyncService $incomingSynchronizer,
        InboundEmailTicketProcessor $ticketProcessor,
    ): int {
        $outgoingMessage = null;
        $incomingMessage = null;

        try {
            $senderMailbox = $this->mailbox(
                argument: 'senderMailbox',
                role: 'Sender',
            );

            $targetMailbox = $this->mailbox(
                argument: 'targetMailbox',
                role: 'Target',
            );

            $this->assertMailboxesCanBeUsed(
                senderMailbox: $senderMailbox,
                targetMailbox: $targetMailbox,
            );

            $mode = $this->mode();
            $timeoutSeconds = $this->timeoutSeconds();

            $verification = $this->verificationData(
                senderMailbox: $senderMailbox,
                targetMailbox: $targetMailbox,
            );

            $this->components->info(
                'Preparing a real GreenMail inbound verification message.'
            );

            $outgoingMessage = $outgoingQueue->queue(
                mailbox: $senderMailbox,
                message: $verification['message'],
                dispatch: $mode === 'queue',
            );

            if ($mode === 'direct') {
                $outgoingMessage->refresh();

                if (! in_array(
                    $outgoingMessage->status,
                    [
                        EmailMessageStatus::Sent,
                        EmailMessageStatus::Delivered,
                    ],
                    true,
                )) {
                    $outgoingSender->send(
                        $outgoingMessage
                    );
                }
            }

            $outgoingMessage = $this->waitForOutgoingDelivery(
                emailMessage: $outgoingMessage,
                timeoutSeconds: $timeoutSeconds,
            );

            $this->components->info(
                "Outgoing message [{$outgoingMessage->id}] was accepted by SMTP."
            );

            $incomingMessage = $this->waitForIncomingMessageWithSynchronization(
                targetMailbox: $targetMailbox,
                senderMailbox: $senderMailbox,
                subject: $verification['subject'],
                timeoutSeconds: $timeoutSeconds,
                mode: $mode,
                incomingSynchronizer: $incomingSynchronizer,
            );

            if ($mode === 'direct') {
                $incomingMessage->refresh();

                if ($this->canProcessDirectly($incomingMessage)) {
                    $ticketProcessor->process(
                        $incomingMessage->id
                    );
                }
            }

            $incomingMessage = $this->waitForTicketProcessing(
                emailMessage: $incomingMessage,
                timeoutSeconds: $timeoutSeconds,
            );

            $this->assertInboundResult(
                outgoingMessage: $outgoingMessage,
                incomingMessage: $incomingMessage,
                senderMailbox: $senderMailbox,
                targetMailbox: $targetMailbox,
                subject: $verification['subject'],
                token: $verification['token'],
            );

            if (! (bool) $this->option('skip-duplicate-check')) {
                $syncResult = $incomingSynchronizer->synchronize(
                    $targetMailbox
                );

                $this->printSyncResult(
                    title: 'Repeated IMAP synchronization',
                    result: $syncResult,
                );

                $this->assertNoDuplicate(
                    targetMailbox: $targetMailbox,
                    senderMailbox: $senderMailbox,
                    subject: $verification['subject'],
                    expectedIncomingMessageId: $incomingMessage->id,
                    expectedTicketId: (int) $incomingMessage->ticket_id,
                );
            }

            $this->printSuccess(
                mode: $mode,
                outgoingMessage: $outgoingMessage,
                incomingMessage: $incomingMessage,
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->newLine();

            $this->components->error(
                $exception->getMessage()
            );

            $this->printMessageState(
                'Outgoing message',
                $outgoingMessage,
            );

            $this->printMessageState(
                'Incoming message',
                $incomingMessage,
            );

            return self::FAILURE;
        }
    }

    private function mailbox(
        string $argument,
        string $role,
    ): Mailbox {
        $mailboxId = $this->argument($argument);

        if (filter_var(
            $mailboxId,
            FILTER_VALIDATE_INT
        ) === false) {
            throw new RuntimeException(
                "{$role} mailbox ID must be an integer."
            );
        }

        $mailbox = Mailbox::query()->find(
            (int) $mailboxId
        );

        if ($mailbox === null) {
            throw new RuntimeException(
                "{$role} mailbox [{$mailboxId}] was not found."
            );
        }

        if (! $mailbox->is_active) {
            throw new RuntimeException(
                "{$role} mailbox [{$mailbox->id}] is disabled."
            );
        }

        return $mailbox;
    }

    private function assertMailboxesCanBeUsed(
        Mailbox $senderMailbox,
        Mailbox $targetMailbox,
    ): void {
        if ($senderMailbox->id === $targetMailbox->id) {
            throw new RuntimeException(
                'Sender and target mailboxes must be different.'
            );
        }

        foreach ([
            'sender' => $senderMailbox,
            'target' => $targetMailbox,
        ] as $role => $mailbox) {
            if (filter_var(
                $mailbox->email_address,
                FILTER_VALIDATE_EMAIL
            ) === false) {
                throw new RuntimeException(
                    ucfirst($role)
                    ." mailbox [{$mailbox->id}] has an invalid email address."
                );
            }
        }

        $hasOutgoingSmtp = $senderMailbox
            ->outgoingChannels()
            ->where('is_enabled', true)
            ->where(
                'driver',
                MailboxDriver::Smtp->value
            )
            ->exists();

        if (! $hasOutgoingSmtp) {
            throw new RuntimeException(
                "Sender mailbox [{$senderMailbox->id}] has no enabled SMTP channel."
            );
        }

        $hasIncomingImap = $targetMailbox
            ->incomingChannels()
            ->where('is_enabled', true)
            ->where(
                'driver',
                MailboxDriver::Imap->value
            )
            ->exists();

        if (! $hasIncomingImap) {
            throw new RuntimeException(
                "Target mailbox [{$targetMailbox->id}] has no enabled IMAP channel."
            );
        }

        if (! (bool) config(
            'simpledesk-mail-ticketing.enabled',
            true
        )) {
            throw new RuntimeException(
                'Inbound email ticketing is disabled.'
            );
        }
    }

    private function mode(): string
    {
        $mode = strtolower(
            trim((string) $this->option('mode'))
        );

        if (! in_array(
            $mode,
            [
                'direct',
                'queue',
            ],
            true,
        )) {
            throw new RuntimeException(
                'The --mode option must be direct or queue.'
            );
        }

        return $mode;
    }

    private function timeoutSeconds(): int
    {
        return max(
            1,
            min(
                600,
                (int) $this->option('timeout')
            )
        );
    }

    private function verificationData(
        Mailbox $senderMailbox,
        Mailbox $targetMailbox,
    ): array {
        $token = (string) Str::uuid();

        $subject =
            "[SimpleDesk inbound verification] {$token}";

        $textBody =
            "SimpleDesk inbound verification token: {$token}";

        $htmlBody =
            '<p><strong>SimpleDesk inbound verification</strong></p>'
            ."<p>Token: {$token}</p>";

        return [
            'token' => $token,
            'subject' => $subject,

            'message' => new OutgoingEmailMessageData(
                idempotencyKey: "greenmail-inbound-verification:{$token}",

                from: null,

                to: [
                    new MailAddressData(
                        address: $targetMailbox->email_address,

                        name: $targetMailbox->display_name
                        ?? $targetMailbox->name,
                    ),
                ],

                cc: [],
                bcc: [],
                replyTo: [],

                subject: $subject,
                textBody: $textBody,
                htmlBody: $htmlBody,

                headers: [
                    'X-SimpleDesk-Integration-Test' => $token,
                ],

                attachments: [],

                metadata: [
                    'source' => 'greenmail_inbound_verification',

                    'verification_token' => $token,

                    'sender_mailbox_id' => $senderMailbox->id,

                    'target_mailbox_id' => $targetMailbox->id,
                ],
            ),
        ];
    }

    private function waitForOutgoingDelivery(
        EmailMessage $emailMessage,
        int $timeoutSeconds,
    ): EmailMessage {
        $deadline =
            microtime(true) + $timeoutSeconds;

        do {
            $emailMessage->refresh();

            if (in_array(
                $emailMessage->status,
                [
                    EmailMessageStatus::Sent,
                    EmailMessageStatus::Delivered,
                ],
                true,
            )) {
                return $emailMessage;
            }

            if ($this->isTerminalFailure($emailMessage)) {
                throw new RuntimeException(
                    "Outgoing email message [{$emailMessage->id}] failed: "
                    .(
                        $emailMessage->failure_message
                        ?? 'unknown error'
                    )
                );
            }

            usleep(250000);
        } while (microtime(true) < $deadline);

        throw new RuntimeException(
            "Outgoing email message [{$emailMessage->id}] was not sent "
            ."within {$timeoutSeconds} seconds. Current status: "
            .$emailMessage->status->value
            .'.'
        );
    }

    private function dispatchIncomingSynchronization(
        Mailbox $mailbox
    ): void {
        SyncIncomingMailboxJob::dispatch(
            $mailbox->id
        )
            ->onQueue(
                (string) config(
                    'simpledesk-mail.queues.incoming',
                    'mail-incoming'
                )
            )
            ->afterCommit();
    }

    private function waitForIncomingMessageWithSynchronization(
        Mailbox $targetMailbox,
        Mailbox $senderMailbox,
        string $subject,
        int $timeoutSeconds,
        string $mode,
        IncomingMailboxSyncService $incomingSynchronizer,
    ): EmailMessage {
        $deadline = microtime(true) + $timeoutSeconds;
        $synchronizationAttempt = 0;

        do {
            $emailMessage = $this->findIncomingMessage(
                targetMailbox: $targetMailbox,
                senderMailbox: $senderMailbox,
                subject: $subject,
            );

            if ($emailMessage !== null) {
                return $emailMessage;
            }

            $synchronizationAttempt++;

            if ($mode === 'queue') {
                $this->dispatchIncomingSynchronization(
                    $targetMailbox
                );

                $this->line(
                    "IMAP synchronization attempt #{$synchronizationAttempt} was queued."
                );
            } else {
                $syncResult = $incomingSynchronizer->synchronize(
                    $targetMailbox
                );

                $this->printSyncResult(
                    title: "IMAP synchronization attempt #{$synchronizationAttempt}",
                    result: $syncResult,
                );
            }

            $emailMessage = $this->findIncomingMessage(
                targetMailbox: $targetMailbox,
                senderMailbox: $senderMailbox,
                subject: $subject,
            );

            if ($emailMessage !== null) {
                return $emailMessage;
            }

            usleep(1_000_000);
        } while (microtime(true) < $deadline);

        throw new RuntimeException(
            "IMAP did not store the verification email within {$timeoutSeconds} seconds."
        );
    }

    private function findIncomingMessage(
        Mailbox $targetMailbox,
        Mailbox $senderMailbox,
        string $subject,
    ): ?EmailMessage {
        return EmailMessage::query()
            ->where(
                'mailbox_id',
                $targetMailbox->id
            )
            ->where(
                'direction',
                EmailMessageDirection::Incoming->value
            )
            ->where(
                'subject',
                $subject
            )
            ->whereRaw(
                'LOWER(sender_address) = ?',
                [
                    strtolower(
                        $senderMailbox->email_address
                    ),
                ]
            )
            ->latest('id')
            ->first();
    }

    private function canProcessDirectly(
        EmailMessage $emailMessage
    ): bool {
        if (
            $emailMessage->status
            === EmailMessageStatus::Received
        ) {
            return true;
        }

        return $emailMessage->status
            === EmailMessageStatus::Failed
            && $emailMessage->failure_code
            === 'inbound_ticket_processing_failed';
    }

    private function waitForTicketProcessing(
        EmailMessage $emailMessage,
        int $timeoutSeconds,
    ): EmailMessage {
        $deadline =
            microtime(true) + $timeoutSeconds;

        do {
            $emailMessage->refresh();

            if (
                $emailMessage->status
                === EmailMessageStatus::Processed
            ) {
                return $emailMessage;
            }

            if ($this->isTerminalFailure($emailMessage)) {
                throw new RuntimeException(
                    "Inbound email message [{$emailMessage->id}] failed: "
                    .(
                        $emailMessage->failure_message
                        ?? 'unknown error'
                    )
                );
            }

            usleep(250000);
        } while (microtime(true) < $deadline);

        throw new RuntimeException(
            "Inbound email message [{$emailMessage->id}] was not processed "
            ."within {$timeoutSeconds} seconds. Current status: "
            .$emailMessage->status->value
            .'. Ensure the mail-incoming worker is running.'
        );
    }

    private function assertInboundResult(
        EmailMessage $outgoingMessage,
        EmailMessage $incomingMessage,
        Mailbox $senderMailbox,
        Mailbox $targetMailbox,
        string $subject,
        string $token,
    ): void {
        $incomingMessage->load([
            'ticket.requester',
            'ticketReply',
            'mailboxChannel',
            'attachments',
            'attachmentRejections',
        ]);

        if ($incomingMessage->ticket_id === null) {
            throw new RuntimeException(
                'Inbound email was processed without creating a ticket.'
            );
        }

        if ($incomingMessage->ticket_reply_id !== null) {
            throw new RuntimeException(
                'A new verification email unexpectedly created a ticket reply.'
            );
        }

        $ticket = $incomingMessage->ticket;

        if ($ticket === null) {
            throw new RuntimeException(
                'The created ticket cannot be loaded.'
            );
        }

        $this->assertSame(
            Ticket::SOURCE_EMAIL,
            $ticket->source,
            'Ticket source does not match.'
        );

        $this->assertSame(
            $targetMailbox->id,
            $ticket->mailbox_id,
            'Ticket mailbox does not match.'
        );

        $this->assertSame(
            $subject,
            $ticket->subject,
            'Ticket subject does not match.'
        );

        if (! str_contains(
            (string) $ticket->description,
            $token
        )) {
            throw new RuntimeException(
                'Ticket description does not contain the verification token.'
            );
        }

        $requesterEmail = strtolower(
            (string) $ticket->requester?->email
        );

        $this->assertSame(
            strtolower(
                $senderMailbox->email_address
            ),
            $requesterEmail,
            'Ticket requester does not match the sender mailbox.'
        );

        $this->assertSame(
            strtolower(
                $senderMailbox->email_address
            ),
            strtolower(
                (string) $incomingMessage->sender_address
            ),
            'Inbound sender address does not match.'
        );

        $this->assertSame(
            $this->normalizeMessageId(
                $outgoingMessage->internet_message_id
            ),
            $this->normalizeMessageId(
                $incomingMessage->internet_message_id
            ),
            'Outgoing and incoming Internet Message-ID values do not match.'
        );
    }

    private function assertNoDuplicate(
        Mailbox $targetMailbox,
        Mailbox $senderMailbox,
        string $subject,
        int $expectedIncomingMessageId,
        int $expectedTicketId,
    ): void {
        $messages = EmailMessage::query()
            ->where(
                'mailbox_id',
                $targetMailbox->id
            )
            ->where(
                'direction',
                EmailMessageDirection::Incoming->value
            )
            ->where(
                'subject',
                $subject
            )
            ->whereRaw(
                'LOWER(sender_address) = ?',
                [
                    strtolower(
                        $senderMailbox->email_address
                    ),
                ]
            )
            ->get([
                'id',
                'ticket_id',
            ]);

        if ($messages->count() !== 1) {
            throw new RuntimeException(
                'Repeated synchronization created duplicate inbound email records.'
            );
        }

        $message = $messages->first();

        $this->assertSame(
            $expectedIncomingMessageId,
            (int) $message->id,
            'Repeated synchronization changed the inbound message record.'
        );

        $this->assertSame(
            $expectedTicketId,
            (int) $message->ticket_id,
            'Repeated synchronization created or linked another ticket.'
        );
    }

    private function isTerminalFailure(
        EmailMessage $emailMessage
    ): bool {
        return in_array(
            $emailMessage->status,
            [
                EmailMessageStatus::Failed,
                EmailMessageStatus::Rejected,
                EmailMessageStatus::Bounced,
                EmailMessageStatus::Complained,
            ],
            true,
        );
    }

    private function printSyncResult(
        string $title,
        object $result,
    ): void {
        $this->newLine();
        $this->line($title.':');

        $this->table(
            [
                'Channel',
                'Fetched',
                'Stored',
                'Duplicates',
                'Acknowledged',
            ],
            [
                [
                    (string) $result->mailboxChannelId,
                    (string) $result->fetched,
                    (string) $result->stored,
                    (string) $result->duplicates,
                    (string) $result->acknowledged,
                ],
            ]
        );
    }

    private function printSuccess(
        string $mode,
        EmailMessage $outgoingMessage,
        EmailMessage $incomingMessage,
    ): void {
        $incomingMessage->loadMissing([
            'ticket.requester',
            'mailboxChannel',
        ]);

        $this->newLine();

        $this->components->info(
            'GreenMail inbound verification passed.'
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
                    'Outgoing message ID',
                    (string) $outgoingMessage->id,
                ],
                [
                    'Outgoing status',
                    $outgoingMessage->status->value,
                ],
                [
                    'Incoming message ID',
                    (string) $incomingMessage->id,
                ],
                [
                    'Incoming status',
                    $incomingMessage->status->value,
                ],
                [
                    'Incoming channel',
                    (string) $incomingMessage->mailbox_channel_id,
                ],
                [
                    'Ticket ID',
                    (string) $incomingMessage->ticket_id,
                ],
                [
                    'Ticket number',
                    (string) $incomingMessage
                        ->ticket
                        ?->ticket_number,
                ],
                [
                    'Requester',
                    (string) $incomingMessage
                        ->ticket
                        ?->requester
                        ?->email,
                ],
                [
                    'Internet Message-ID',
                    (string) $incomingMessage
                        ->internet_message_id,
                ],
            ]
        );
    }

    private function printMessageState(
        string $label,
        ?EmailMessage $emailMessage,
    ): void {
        if ($emailMessage === null) {
            return;
        }

        $emailMessage->refresh();

        $this->newLine();
        $this->line($label.':');

        $this->table(
            [
                'Field',
                'Value',
            ],
            [
                [
                    'ID',
                    (string) $emailMessage->id,
                ],
                [
                    'Status',
                    $emailMessage->status->value,
                ],
                [
                    'Ticket ID',
                    (string) (
                        $emailMessage->ticket_id
                        ?? 'null'
                    ),
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
    }

    private function assertSame(
        mixed $expected,
        mixed $actual,
        string $message,
    ): void {
        if ($expected !== $actual) {
            throw new RuntimeException(
                $message
                .' Expected: '
                .var_export($expected, true)
                .'; actual: '
                .var_export($actual, true)
            );
        }
    }

    private function normalizeMessageId(
        ?string $messageId
    ): ?string {
        if ($messageId === null) {
            return null;
        }

        $messageId = strtolower(
            trim(
                $messageId,
                " \t\n\r\0\x0B<>"
            )
        );

        return $messageId !== ''
            ? $messageId
            : null;
    }
}
