<?php

namespace App\Console\Commands\Admin\Mail;

use App\Data\Admin\Mail\MailAddressData;
use App\Data\Admin\Mail\MailAttachmentData;
use App\Data\Admin\Mail\OutgoingEmailMessageData;
use App\Enums\Admin\Mail\EmailAttachmentScanStatus;
use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Enums\Admin\Mail\MailboxChannelDirection;
use App\Enums\Admin\Mail\MailboxDriver;
use App\Jobs\Admin\Mail\ProcessInboundEmailJob;
use App\Jobs\Admin\Mail\ScanEmailAttachmentJob;
use App\Jobs\Admin\Mail\SendOutgoingEmailJob;
use App\Models\Admin\Mail\EmailAttachment;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\Mailbox;
use App\Models\Admin\Mail\MailboxChannel;
use App\Services\Admin\Mail\Antivirus\AttachmentScanDispatcher;
use App\Services\Admin\Mail\Antivirus\EmailAttachmentScanService;
use App\Services\Admin\Mail\Automation\MailPipelineRecoveryService;
use App\Services\Admin\Mail\IncomingMailboxSyncService;
use App\Services\Admin\Mail\MailDriverRegistry;
use App\Services\Admin\Mail\MailInternetMessageIdFactory;
use App\Services\Admin\Mail\OutgoingEmailQueueService;
use App\Services\Admin\Mail\OutgoingMailFailoverService;
use App\Services\Admin\Mail\Ticketing\InboundEmailTicketProcessor;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class VerifyMailRecoveryCommand extends Command
{
    protected $signature = 'simpledesk:mail:verify-recovery
        {mailbox : Mailbox used for outgoing messages and incoming ticket creation}
        {senderMailbox : Mailbox used to inject incoming messages}
        {senderChannel : SMTP channel used to inject incoming messages}
        {recipient : Recipient for recovered outgoing messages}
        {--timeout=60 : Seconds to wait for IMAP fixtures}
        {--limit=100 : Recovery batch size}';

    protected $description = 'Verify stuck and undispatched mail pipeline recovery';

    private const ATTACHMENT_RECOVERY_COMMAND = 'simpledesk:mail:recover-attachment-scans';

    public function handle(
        MailPipelineRecoveryService $recovery,
        OutgoingEmailQueueService $queue,
        OutgoingMailFailoverService $sender,
        IncomingMailboxSyncService $synchronizer,
        InboundEmailTicketProcessor $processor,
        MailDriverRegistry $drivers,
        MailInternetMessageIdFactory $messageIds,
        AttachmentScanDispatcher $scanDispatcher,
        EmailAttachmentScanService $scanner,
    ): int {
        $ticketingEnabled = (bool) config(
            'simpledesk-mail-ticketing.enabled',
            true
        );

        try {
            $mailbox = $this->mailbox(
                'mailbox',
                'Recovery'
            );

            $senderMailbox = $this->mailbox(
                'senderMailbox',
                'Sender'
            );

            $senderChannel = $this->senderChannel(
                $senderMailbox
            );

            $recipient = $this->recipient();

            $timeout = max(
                5,
                min(
                    300,
                    (int) $this->option('timeout')
                )
            );

            $limit = max(
                5,
                min(
                    1000,
                    (int) $this->option('limit')
                )
            );

            $old = now()->subHours(2);
            $token = (string) Str::uuid();

            Queue::fake();

            $this->components->info(
                'Preparing deterministic recovery fixtures.'
            );

            $outgoingSending = $this->queueTextMessage(
                queue: $queue,
                mailbox: $mailbox,
                recipient: $recipient,
                token: $token,
                phase: 'outgoing-sending-stuck',
            );

            $outgoingQueued = $this->queueTextMessage(
                queue: $queue,
                mailbox: $mailbox,
                recipient: $recipient,
                token: $token,
                phase: 'outgoing-queued-undispatched',
            );

            $outgoingAttachment = $this->queueAttachmentMessage(
                queue: $queue,
                mailbox: $mailbox,
                recipient: $recipient,
                token: $token,
            );

            $attachment = $outgoingAttachment
                ->attachments()
                ->first();

            if ($attachment === null) {
                throw new RuntimeException(
                    "Message [{$outgoingAttachment->id}] has no recovery attachment."
                );
            }

            $scanDispatcher->releaseClaim(
                $attachment->id
            );

            $this->ageOutgoingFixtures(
                sending: $outgoingSending,
                queued: $outgoingQueued,
                old: $old,
            );

            $this->ageAttachment(
                attachment: $attachment,
                old: $old,
            );

            config()->set(
                'simpledesk-mail-ticketing.enabled',
                false
            );

            $processingSubject = $this->injectIncoming(
                phase: 'incoming-processing-stuck',
                token: $token,
                targetMailbox: $mailbox,
                senderMailbox: $senderMailbox,
                senderChannel: $senderChannel,
                drivers: $drivers,
                messageIds: $messageIds,
            );

            $receivedSubject = $this->injectIncoming(
                phase: 'incoming-received-undispatched',
                token: $token,
                targetMailbox: $mailbox,
                senderMailbox: $senderMailbox,
                senderChannel: $senderChannel,
                drivers: $drivers,
                messageIds: $messageIds,
            );

            $incoming = $this->waitForIncoming(
                synchronizer: $synchronizer,
                mailbox: $mailbox,
                subjects: [
                    $processingSubject,
                    $receivedSubject,
                ],
                timeout: $timeout,
            );

            $incomingProcessing = $incoming[
            $processingSubject
            ];

            $incomingReceived = $incoming[
            $receivedSubject
            ];

            $this->ageIncomingFixtures(
                processing: $incomingProcessing,
                received: $incomingReceived,
                old: $old,
            );

            config()->set(
                'simpledesk-mail-ticketing.enabled',
                $ticketingEnabled
            );

            Queue::fake();

            $this->components->info(
                'Running mail pipeline recovery.'
            );

            $result = $recovery->recover(
                $limit
            );

            foreach (
                [
                    'incomingStuckReset' => 1,
                    'incomingReceivedDispatched' => 1,
                    'outgoingStuckReset' => 1,
                    'outgoingQueuedDispatched' => 1,
                ] as $property => $minimum
            ) {
                if (
                    ! property_exists(
                        $result,
                        $property
                    )
                    || (int) $result->{$property}
                    < $minimum
                ) {
                    throw new RuntimeException(
                        "Recovery counter [{$property}] is lower than {$minimum}."
                    );
                }
            }

            $this->assertPushed(
                jobClass: SendOutgoingEmailJob::class,
                property: 'emailMessageId',
                id: $outgoingSending->id,
            );

            $this->assertPushed(
                jobClass: SendOutgoingEmailJob::class,
                property: 'emailMessageId',
                id: $outgoingQueued->id,
            );

            $this->assertPushed(
                jobClass: ProcessInboundEmailJob::class,
                property: 'emailMessageId',
                id: $incomingProcessing->id,
            );

            $this->assertPushed(
                jobClass: ProcessInboundEmailJob::class,
                property: 'emailMessageId',
                id: $incomingReceived->id,
            );

            $this->assertReset(
                message: $outgoingSending,
                status: EmailMessageStatus::Queued,
                action: 'outgoing_sending_reset',
            );

            $this->assertReset(
                message: $incomingProcessing,
                status: EmailMessageStatus::Received,
                action: 'incoming_processing_reset',
            );

            $this->components->info(
                'Running attachment scan recovery.'
            );

            $this->runAttachmentRecovery();

            $this->assertPushed(
                jobClass: ScanEmailAttachmentJob::class,
                property: 'emailAttachmentId',
                id: $attachment->id,
            );

            $this->components->info(
                'Executing recovered work synchronously.'
            );

            $scanResult = $scanner->scan(
                $attachment->id
            );

            if (
                $scanResult === null
                || ! $scanResult->clean
            ) {
                throw new RuntimeException(
                    "Attachment [{$attachment->id}] was not recovered as clean."
                );
            }

            $outgoingAttachment->refresh();

            foreach (
                [
                    $outgoingSending,
                    $outgoingQueued,
                    $outgoingAttachment,
                ] as $message
            ) {
                $message->refresh();

                if (
                    $message->status
                    !== EmailMessageStatus::Queued
                ) {
                    throw new RuntimeException(
                        "Outgoing message [{$message->id}] is [{$message->status->value}], expected [queued]."
                    );
                }

                $sender->send(
                    $message
                );

                $message->refresh();

                if (
                    ! in_array(
                        $message->status,
                        [
                            EmailMessageStatus::Sent,
                            EmailMessageStatus::Delivered,
                        ],
                        true
                    )
                ) {
                    throw new RuntimeException(
                        "Recovered outgoing message [{$message->id}] was not sent."
                    );
                }
            }

            foreach (
                [
                    $incomingProcessing,
                    $incomingReceived,
                ] as $message
            ) {
                $processed = $processor->process(
                    $message->id
                );

                if (
                    $processed->status
                    !== EmailMessageStatus::Processed
                    || $processed->ticket_id === null
                ) {
                    throw new RuntimeException(
                        "Recovered incoming message [{$message->id}] did not create a ticket."
                    );
                }
            }

            $attachment->refresh();

            if (
                $attachment->scan_status
                !== EmailAttachmentScanStatus::Clean
            ) {
                throw new RuntimeException(
                    "Attachment [{$attachment->id}] is not clean after recovery."
                );
            }

            Queue::fake();

            $recovery->recover(
                $limit
            );

            $this->assertNotPushed(
                jobClass: SendOutgoingEmailJob::class,
                property: 'emailMessageId',
                ids: [
                    $outgoingSending->id,
                    $outgoingQueued->id,
                    $outgoingAttachment->id,
                ],
            );

            $this->assertNotPushed(
                jobClass: ProcessInboundEmailJob::class,
                property: 'emailMessageId',
                ids: [
                    $incomingProcessing->id,
                    $incomingReceived->id,
                ],
            );

            $this->newLine();

            $this->components->info(
                'Mail recovery verification passed.'
            );

            $this->printResult(
                result: $result,
                outgoingSending: $outgoingSending->fresh(),
                outgoingQueued: $outgoingQueued->fresh(),
                outgoingAttachment: $outgoingAttachment->fresh(),
                attachment: $attachment->fresh(),
                incomingProcessing: $incomingProcessing->fresh(),
                incomingReceived: $incomingReceived->fresh(),
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->newLine();

            $this->components->error(
                $exception->getMessage()
            );

            return self::FAILURE;
        } finally {
            config()->set(
                'simpledesk-mail-ticketing.enabled',
                $ticketingEnabled
            );
        }
    }

    private function queueTextMessage(
        OutgoingEmailQueueService $queue,
        Mailbox $mailbox,
        string $recipient,
        string $token,
        string $phase,
    ): EmailMessage {
        return $queue->queue(
            mailbox: $mailbox,
            message: new OutgoingEmailMessageData(
                idempotencyKey: "mail-recovery:{$phase}:{$token}",

                from: null,

                to: [
                    new MailAddressData(
                        address: $recipient
                    ),
                ],

                cc: [],
                bcc: [],
                replyTo: [],

                subject: "[SimpleDesk recovery {$phase}] {$token}",

                textBody: "SimpleDesk recovery verification.\n"
                ."Phase: {$phase}\n"
                ."Token: {$token}",

                htmlBody: null,

                headers: [
                    'X-SimpleDesk-Integration-Test' => $token,

                    'X-SimpleDesk-Recovery-Phase' => $phase,
                ],

                metadata: [
                    'source' => 'mail_recovery_verification',

                    'phase' => $phase,

                    'verification_token' => $token,
                ],
            ),

            dispatch: false,
        );
    }

    private function queueAttachmentMessage(
        OutgoingEmailQueueService $queue,
        Mailbox $mailbox,
        string $recipient,
        string $token,
    ): EmailMessage {
        $content =
            "SimpleDesk attachment recovery verification.\n"
            ."Token: {$token}\n";

        return $queue->queue(
            mailbox: $mailbox,
            message: new OutgoingEmailMessageData(
                idempotencyKey: "mail-recovery:attachment:{$token}",

                from: null,

                to: [
                    new MailAddressData(
                        address: $recipient
                    ),
                ],

                cc: [],
                bcc: [],
                replyTo: [],

                subject: "[SimpleDesk recovery attachment] {$token}",

                textBody: $content,

                htmlBody: null,

                headers: [
                    'X-SimpleDesk-Integration-Test' => $token,

                    'X-SimpleDesk-Recovery-Phase' => 'attachment-scan',
                ],

                attachments: [
                    new MailAttachmentData(
                        fileName: "simpledesk-recovery-{$token}.txt",

                        mimeType: 'text/plain',

                        size: strlen($content),

                        content: $content,

                        metadata: [
                            'integration_test' => true,
                            'recovery_test' => true,
                        ],
                    ),
                ],

                metadata: [
                    'source' => 'mail_recovery_verification',

                    'phase' => 'attachment-scan',

                    'verification_token' => $token,
                ],
            ),

            dispatch: false,
        );
    }

    private function injectIncoming(
        string $phase,
        string $token,
        Mailbox $targetMailbox,
        Mailbox $senderMailbox,
        MailboxChannel $senderChannel,
        MailDriverRegistry $drivers,
        MailInternetMessageIdFactory $messageIds,
    ): string {
        $messageToken = (string) Str::uuid();

        $idempotencyKey =
            'mail-recovery-incoming:'
            ."{$phase}:{$messageToken}";

        $subject =
            "[SimpleDesk recovery {$phase}] "
            .$messageToken;

        $message = new OutgoingEmailMessageData(
            idempotencyKey: $idempotencyKey,

            from: new MailAddressData(
                address: $senderMailbox->email_address,

                name: $senderMailbox->display_name
                ?? $senderMailbox->name,
            ),

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

            textBody: "SimpleDesk incoming recovery verification.\n"
            ."Phase: {$phase}\n"
            ."Token: {$token}",

            htmlBody: null,

            headers: [
                'X-SimpleDesk-Integration-Test' => $token,

                'X-SimpleDesk-Recovery-Phase' => $phase,
            ],

            attachments: [],

            internetMessageId: $messageIds->make(
                mailbox: $senderMailbox,
                idempotencyKey: $idempotencyKey,
            ),

            inReplyToMessageId: null,

            references: [],

            metadata: [
                'source' => 'mail_recovery_verification',

                'phase' => $phase,

                'verification_token' => $token,
            ],
        );

        $result = $drivers
            ->outgoing(
                $senderChannel->driver
            )
            ->send(
                channel: $senderChannel,
                message: $message,
            );

        if ($result->acceptedRecipients === []) {
            throw new RuntimeException(
                "SMTP did not accept fixture [{$phase}]."
            );
        }

        return $subject;
    }

    private function waitForIncoming(
        IncomingMailboxSyncService $synchronizer,
        Mailbox $mailbox,
        array $subjects,
        int $timeout,
    ): array {
        $deadline = microtime(true) + $timeout;

        do {
            $synchronizer->synchronize(
                $mailbox->fresh()
            );

            $messages = EmailMessage::query()
                ->where(
                    'mailbox_id',
                    $mailbox->id
                )
                ->where(
                    'direction',
                    EmailMessageDirection::Incoming->value
                )
                ->whereIn(
                    'subject',
                    $subjects
                )
                ->get()
                ->keyBy(
                    'subject'
                );

            if (
                $messages->count()
                === count($subjects)
            ) {
                return $messages->all();
            }

            usleep(
                1_000_000
            );
        } while (
            microtime(true)
            < $deadline
        );

        throw new RuntimeException(
            "IMAP did not store all fixtures within {$timeout} seconds."
        );
    }

    private function ageOutgoingFixtures(
        EmailMessage $sending,
        EmailMessage $queued,
        CarbonInterface $old,
    ): void {
        EmailMessage::query()
            ->whereKey(
                $sending->id
            )
            ->update([
                'status' => EmailMessageStatus::Sending->value,

                'processing_started_at' => $old,

                'failed_at' => null,
                'failure_code' => null,
                'failure_message' => null,
            ]);

        EmailMessage::query()
            ->whereKey(
                $queued->id
            )
            ->update([
                'status' => EmailMessageStatus::Queued->value,

                'queued_at' => $old,

                'created_at' => $old,

                'processing_started_at' => null,

                'failed_at' => null,
                'failure_code' => null,
                'failure_message' => null,
            ]);
    }

    private function ageIncomingFixtures(
        EmailMessage $processing,
        EmailMessage $received,
        CarbonInterface $old,
    ): void {
        EmailMessage::query()
            ->whereKey(
                $processing->id
            )
            ->update([
                'status' => EmailMessageStatus::Processing->value,

                'processing_started_at' => $old,

                'failed_at' => null,
                'failure_code' => null,
                'failure_message' => null,
            ]);

        EmailMessage::query()
            ->whereKey(
                $received->id
            )
            ->update([
                'status' => EmailMessageStatus::Received->value,

                'created_at' => $old,

                'processing_started_at' => null,

                'failed_at' => null,
                'failure_code' => null,
                'failure_message' => null,
            ]);
    }

    private function ageAttachment(
        EmailAttachment $attachment,
        CarbonInterface $old,
    ): void {
        EmailAttachment::query()
            ->whereKey(
                $attachment->id
            )
            ->update([
                'scan_status' => EmailAttachmentScanStatus::Pending->value,

                'scan_started_at' => $old,

                'scan_attempts' => 1,

                'scanned_at' => null,

                'scan_failure_code' => null,

                'scan_failure_message' => null,

                'quarantined_at' => null,

                'scan_result' => null,
            ]);
    }

    private function assertReset(
        EmailMessage $message,
        EmailMessageStatus $status,
        string $action,
    ): void {
        $message->refresh();

        $actualAction =
            $message
                ->metadata['recovery']['last_action']
            ?? null;

        if (
            $message->status !== $status
            || $actualAction !== $action
        ) {
            throw new RuntimeException(
                "Message [{$message->id}] recovery state "
                .'is invalid: status='
                .$message->status->value
                .', action='
                .($actualAction ?? 'null')
                .'.'
            );
        }
    }

    private function runAttachmentRecovery(): void
    {
        $application =
            $this->getApplication();

        if (
            $application === null
            || ! $application->has(
                self::ATTACHMENT_RECOVERY_COMMAND
            )
        ) {
            throw new RuntimeException(
                'Command ['
                .self::ATTACHMENT_RECOVERY_COMMAND
                .'] is not registered.'
            );
        }

        $exitCode = $this->callSilent(
            self::ATTACHMENT_RECOVERY_COMMAND
        );

        if ($exitCode !== self::SUCCESS) {
            throw new RuntimeException(
                'Attachment recovery command failed '
                ."with exit code {$exitCode}."
            );
        }
    }

    private function assertPushed(
        string $jobClass,
        string $property,
        int $id,
    ): void {
        $queue =
            Queue::getFacadeRoot();

        $jobs = method_exists(
            $queue,
            'pushed'
        )
            ? $queue->pushed(
                $jobClass,
                fn (object $job): bool => isset(
                    $job->{$property}
                )
                    && (int) $job->{$property}
                    === $id,
            )
            : collect();

        if ($jobs->isEmpty()) {
            throw new RuntimeException(
                'Recovery did not dispatch '
                ."[{$jobClass}] for ID [{$id}]."
            );
        }
    }

    private function assertNotPushed(
        string $jobClass,
        string $property,
        array $ids,
    ): void {
        $queue =
            Queue::getFacadeRoot();

        $jobs = method_exists(
            $queue,
            'pushed'
        )
            ? $queue->pushed(
                $jobClass,
                fn (object $job): bool => isset(
                    $job->{$property}
                )
                    && in_array(
                        (int) $job->{$property},
                        $ids,
                        true
                    ),
            )
            : collect();

        if ($jobs->isNotEmpty()) {
            throw new RuntimeException(
                'Repeated recovery redispatched '
                ."completed [{$jobClass}] fixtures."
            );
        }
    }

    private function printResult(
        object $result,
        EmailMessage $outgoingSending,
        EmailMessage $outgoingQueued,
        EmailMessage $outgoingAttachment,
        EmailAttachment $attachment,
        EmailMessage $incomingProcessing,
        EmailMessage $incomingReceived,
    ): void {
        $this->table(
            [
                'Check',
                'ID',
                'Final state',
                'Recovery action',
            ],
            [
                [
                    'Outgoing stuck during send',
                    $outgoingSending->id,
                    $outgoingSending->status->value,
                    $outgoingSending
                        ->metadata['recovery']['last_action']
                    ?? '-',
                ],
                [
                    'Outgoing job never started',
                    $outgoingQueued->id,
                    $outgoingQueued->status->value,
                    'redispatched',
                ],
                [
                    'Attachment scan stuck',
                    $attachment->id,
                    $attachment->scan_status->value,
                    "message {$outgoingAttachment->id}",
                ],
                [
                    'Incoming stuck during processing',
                    $incomingProcessing->id,
                    $incomingProcessing->status->value,
                    $incomingProcessing
                        ->metadata['recovery']['last_action']
                    ?? '-',
                ],
                [
                    'Incoming job never started',
                    $incomingReceived->id,
                    $incomingReceived->status->value,
                    'redispatched',
                ],
            ]
        );

        $this->newLine();

        $this->table(
            [
                'Recovery counter',
                'Value',
            ],
            [
                [
                    'Incoming stuck reset',
                    $result->incomingStuckReset,
                ],
                [
                    'Incoming received dispatched',
                    $result->incomingReceivedDispatched,
                ],
                [
                    'Outgoing stuck reset',
                    $result->outgoingStuckReset,
                ],
                [
                    'Outgoing queued dispatched',
                    $result->outgoingQueuedDispatched,
                ],
            ]
        );
    }

    private function mailbox(
        string $argument,
        string $role,
    ): Mailbox {
        $id = $this->positiveInt(
            $argument
        );

        $mailbox = Mailbox::query()->find(
            $id
        );

        if (
            $mailbox === null
            || ! $mailbox->is_active
        ) {
            throw new RuntimeException(
                "{$role} mailbox [{$id}] "
                .'was not found or is disabled.'
            );
        }

        if (
            filter_var(
                $mailbox->email_address,
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            throw new RuntimeException(
                "{$role} mailbox [{$id}] "
                .'has an invalid email address.'
            );
        }

        return $mailbox;
    }

    private function senderChannel(
        Mailbox $mailbox
    ): MailboxChannel {
        $id = $this->positiveInt(
            'senderChannel'
        );

        $channel = MailboxChannel::query()
            ->with(
                'providerConnection'
            )
            ->find(
                $id
            );

        if (
            $channel === null
            || $channel->mailbox_id
            !== $mailbox->id
        ) {
            throw new RuntimeException(
                "Sender channel [{$id}] "
                ."was not found for mailbox [{$mailbox->id}]."
            );
        }

        if (
            $channel->direction
            !== MailboxChannelDirection::Outgoing
            || $channel->driver
            !== MailboxDriver::Smtp
            || ! $channel->is_enabled
        ) {
            throw new RuntimeException(
                "Sender channel [{$id}] "
                .'must be enabled outgoing/smtp.'
            );
        }

        if (
            $channel->provider_connection_id !== null
            && (
                $channel->providerConnection === null
                || ! $channel
                    ->providerConnection
                    ->is_active
            )
        ) {
            throw new RuntimeException(
                "Sender channel [{$id}] "
                .'has an inactive provider connection.'
            );
        }

        return $channel;
    }

    private function recipient(): string
    {
        $recipient = trim(
            (string) $this->argument(
                'recipient'
            )
        );

        if (
            filter_var(
                $recipient,
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            throw new RuntimeException(
                'Recipient must be a valid email address.'
            );
        }

        return $recipient;
    }

    private function positiveInt(
        string $argument
    ): int {
        $value = filter_var(
            $this->argument($argument),
            FILTER_VALIDATE_INT
        );

        if (
            $value === false
            || (int) $value < 1
        ) {
            throw new RuntimeException(
                "Argument [{$argument}] "
                .'must be a positive integer.'
            );
        }

        return (int) $value;
    }
}
