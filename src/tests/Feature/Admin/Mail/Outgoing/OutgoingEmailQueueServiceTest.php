<?php

namespace Tests\Feature\Admin\Mail\Outgoing;

use App\Data\Admin\Mail\MailAddressData;
use App\Data\Admin\Mail\MailAttachmentData;
use App\Data\Admin\Mail\OutgoingEmailMessageData;
use App\Enums\Admin\Mail\EmailAttachmentScanStatus;
use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Jobs\Admin\Mail\SendOutgoingEmailJob;
use App\Models\Admin\Mail\EmailAttachment;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\Mailbox;
use App\Services\Admin\Mail\MailAttachmentStorageService;
use App\Services\Admin\Mail\MailInternetMessageIdFactory;
use App\Services\Admin\Mail\OutgoingEmailQueueService;
use App\Services\Admin\Mail\OutgoingMailAttachmentValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class OutgoingEmailQueueServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        config()->set(
            'simpledesk-mail.queues.outgoing',
            'mail-outgoing'
        );
    }

    public function test_message_without_attachments_is_queued_and_dispatched(): void
    {
        config()->set(
            'simpledesk-mail-antivirus.enabled',
            true
        );

        $mailbox = $this->createMailbox();

        $payload = $this->payload(
            idempotencyKey: 'queue-test-no-attachments',
            attachments: [],
        );

        $message = $this
            ->service()
            ->queue(
                mailbox: $mailbox,
                message: $payload,
                dispatch: true,
            );

        $this->assertSame(
            EmailMessageStatus::Queued,
            $message->status
        );

        $this->assertNotNull(
            $message->queued_at
        );

        $this->assertNull(
            $message->failed_at
        );

        $this->assertSame(
            true,
            data_get(
                $message->metadata,
                'mail_pipeline.dispatch_after_attachment_scan'
            )
        );

        $this->assertDatabaseCount(
            'email_messages',
            1
        );

        $this->assertDatabaseCount(
            'email_attachments',
            0
        );

        Queue::assertPushed(
            SendOutgoingEmailJob::class,
            function (
                SendOutgoingEmailJob $job
            ) use ($message): bool {
                return $job->emailMessageId
                    === $message->id
                    && $job->queue
                    === 'mail-outgoing';
            }
        );
    }

    public function test_not_scanned_attachment_is_queued_when_antivirus_is_disabled(): void
    {
        config()->set(
            'simpledesk-mail-antivirus.enabled',
            false
        );

        $mailbox = $this->createMailbox();

        $payload = $this->payload(
            idempotencyKey: 'queue-test-not-scanned-disabled',

            attachments: [
                $this->attachmentPayload(),
            ],
        );

        $message = $this
            ->service(
                scanStatus: EmailAttachmentScanStatus::NotScanned
            )
            ->queue(
                mailbox: $mailbox,
                message: $payload,
                dispatch: true,
            );

        $this->assertSame(
            EmailMessageStatus::Queued,
            $message->status
        );

        $this->assertNotNull(
            $message->queued_at
        );

        $this->assertNull(
            $message->failure_code
        );

        $this->assertDatabaseCount(
            'email_attachments',
            1
        );

        Queue::assertPushed(
            SendOutgoingEmailJob::class,
            fn (
                SendOutgoingEmailJob $job
            ): bool => $job->emailMessageId
                === $message->id
        );
    }

    public function test_not_scanned_attachment_waits_when_antivirus_is_enabled(): void
    {
        config()->set(
            'simpledesk-mail-antivirus.enabled',
            true
        );

        $mailbox = $this->createMailbox();

        $payload = $this->payload(
            idempotencyKey: 'queue-test-not-scanned-enabled',

            attachments: [
                $this->attachmentPayload(),
            ],
        );

        $message = $this
            ->service(
                scanStatus: EmailAttachmentScanStatus::NotScanned
            )
            ->queue(
                mailbox: $mailbox,
                message: $payload,
                dispatch: true,
            );

        $this->assertSame(
            EmailMessageStatus::Preparing,
            $message->status
        );

        $this->assertNull(
            $message->queued_at
        );

        $this->assertNull(
            $message->failed_at
        );

        $this->assertSame(
            true,
            data_get(
                $message->metadata,
                'mail_pipeline.dispatch_after_attachment_scan'
            )
        );

        Queue::assertNotPushed(
            SendOutgoingEmailJob::class
        );
    }

    public function test_pending_attachment_keeps_message_in_preparing_state(): void
    {
        config()->set(
            'simpledesk-mail-antivirus.enabled',
            true
        );

        $mailbox = $this->createMailbox();

        $payload = $this->payload(
            idempotencyKey: 'queue-test-pending',

            attachments: [
                $this->attachmentPayload(),
            ],
        );

        $message = $this
            ->service(
                scanStatus: EmailAttachmentScanStatus::Pending
            )
            ->queue(
                mailbox: $mailbox,
                message: $payload,
                dispatch: true,
            );

        $this->assertSame(
            EmailMessageStatus::Preparing,
            $message->status
        );

        $this->assertNull(
            $message->queued_at
        );

        $this->assertNull(
            $message->processing_started_at
        );

        $this->assertNull(
            $message->failure_code
        );

        Queue::assertNotPushed(
            SendOutgoingEmailJob::class
        );
    }

    public function test_infected_attachment_marks_message_failed(): void
    {
        config()->set(
            'simpledesk-mail-antivirus.enabled',
            true
        );

        $mailbox = $this->createMailbox();

        $payload = $this->payload(
            idempotencyKey: 'queue-test-infected',

            attachments: [
                $this->attachmentPayload(),
            ],
        );

        $message = $this
            ->service(
                scanStatus: EmailAttachmentScanStatus::Infected
            )
            ->queue(
                mailbox: $mailbox,
                message: $payload,
                dispatch: true,
            );

        $this->assertSame(
            EmailMessageStatus::Failed,
            $message->status
        );

        $this->assertSame(
            'attachment_infected',
            $message->failure_code
        );

        $this->assertSame(
            'Outgoing email contains an infected attachment.',
            $message->failure_message
        );

        $this->assertNotNull(
            $message->failed_at
        );

        $this->assertNull(
            $message->queued_at
        );

        Queue::assertNotPushed(
            SendOutgoingEmailJob::class
        );
    }

    public function test_failed_attachment_scan_marks_message_failed(): void
    {
        config()->set(
            'simpledesk-mail-antivirus.enabled',
            true
        );

        $mailbox = $this->createMailbox();

        $payload = $this->payload(
            idempotencyKey: 'queue-test-scan-failed',

            attachments: [
                $this->attachmentPayload(),
            ],
        );

        $message = $this
            ->service(
                scanStatus: EmailAttachmentScanStatus::Failed
            )
            ->queue(
                mailbox: $mailbox,
                message: $payload,
                dispatch: true,
            );

        $this->assertSame(
            EmailMessageStatus::Failed,
            $message->status
        );

        $this->assertSame(
            'attachment_scan_failed',
            $message->failure_code
        );

        $this->assertSame(
            'Antivirus scanning failed for an outgoing attachment.',
            $message->failure_message
        );

        $this->assertNotNull(
            $message->failed_at
        );

        Queue::assertNotPushed(
            SendOutgoingEmailJob::class
        );
    }

    public function test_repeated_queueing_remembers_dispatch_intent_while_waiting_for_scan(): void
    {
        config()->set(
            'simpledesk-mail-antivirus.enabled',
            true
        );

        $mailbox = $this->createMailbox();

        $payload = $this->payload(
            idempotencyKey: 'queue-test-dispatch-intent',

            attachments: [
                $this->attachmentPayload(),
            ],
        );

        $service = $this->service(
            scanStatus: EmailAttachmentScanStatus::NotScanned,

            calls: 2
        );

        $firstMessage = $service->queue(
            mailbox: $mailbox,
            message: $payload,
            dispatch: false,
        );

        $this->assertSame(
            false,
            data_get(
                $firstMessage->metadata,
                'mail_pipeline.dispatch_after_attachment_scan'
            )
        );

        $secondMessage = $service->queue(
            mailbox: $mailbox,
            message: $payload,
            dispatch: true,
        );

        $this->assertSame(
            $firstMessage->id,
            $secondMessage->id
        );

        $this->assertSame(
            EmailMessageStatus::Preparing,
            $secondMessage->status
        );

        $this->assertSame(
            true,
            data_get(
                $secondMessage->metadata,
                'mail_pipeline.dispatch_after_attachment_scan'
            )
        );

        $this->assertDatabaseCount(
            'email_messages',
            1
        );

        $this->assertDatabaseCount(
            'email_attachments',
            1
        );

        Queue::assertNotPushed(
            SendOutgoingEmailJob::class
        );
    }

    public function test_sent_message_is_immutable_during_repeated_queueing(): void
    {
        config()->set(
            'simpledesk-mail-antivirus.enabled',
            false
        );

        $mailbox = $this->createMailbox();

        $idempotencyKey =
            'queue-test-immutable-sent';

        $existingMessage =
            $this->createSentMessage(
                mailbox: $mailbox,
                idempotencyKey: $idempotencyKey,
            );

        $payload = $this->payload(
            idempotencyKey: $idempotencyKey,

            attachments: [],
        );

        $result = $this
            ->service()
            ->queue(
                mailbox: $mailbox,
                message: $payload,
                dispatch: true,
            );

        $existingMessage->refresh();

        $this->assertSame(
            $existingMessage->id,
            $result->id
        );

        $this->assertSame(
            EmailMessageStatus::Sent,
            $existingMessage->status
        );

        $this->assertSame(
            'provider-existing-message',
            $existingMessage->external_message_id
        );

        $this->assertSame(
            '<existing-sent@example.test>',
            $existingMessage->internet_message_id
        );

        $this->assertSame(
            true,
            data_get(
                $existingMessage->metadata,
                'immutable'
            )
        );

        $this->assertNull(
            data_get(
                $existingMessage->metadata,
                'mail_pipeline.dispatch_after_attachment_scan'
            )
        );

        $this->assertDatabaseCount(
            'email_messages',
            1
        );

        Queue::assertNotPushed(
            SendOutgoingEmailJob::class
        );
    }

    private function service(
        ?EmailAttachmentScanStatus $scanStatus = null,
        int $calls = 1,
    ): OutgoingEmailQueueService {
        $attachmentStorage = Mockery::mock(
            MailAttachmentStorageService::class
        );

        if ($scanStatus === null) {
            $attachmentStorage->shouldNotReceive(
                'store'
            );
        } else {
            $attachmentStorage
                ->shouldReceive('store')
                ->times($calls)
                ->andReturnUsing(
                    function (
                        EmailMessage $emailMessage,
                        MailAttachmentData $attachment,
                        int $position,
                    ) use (
                        $scanStatus
                    ): EmailAttachment {
                        return $this->storeAttachment(
                            emailMessage: $emailMessage,

                            attachment: $attachment,

                            position: $position,

                            scanStatus: $scanStatus,
                        );
                    }
                );
        }

        $attachmentValidator = Mockery::mock(
            OutgoingMailAttachmentValidator::class
        );

        $attachmentValidator
            ->shouldReceive('validate')
            ->times($calls)
            ->andReturnNull();

        $messageIds = Mockery::mock(
            MailInternetMessageIdFactory::class
        );

        $messageIds
            ->shouldReceive('make')
            ->times($calls)
            ->andReturnUsing(
                static function (
                    Mailbox $mailbox,
                    string $idempotencyKey,
                ): string {
                    return '<'
                        .hash(
                            'sha256',
                            $mailbox->id
                            .'|'
                            .$idempotencyKey
                        )
                        .'@simpledesk.test>';
                }
            );

        return new OutgoingEmailQueueService(
            attachmentStorage: $attachmentStorage,

            attachmentValidator: $attachmentValidator,

            messageIds: $messageIds,
        );
    }

    private function storeAttachment(
        EmailMessage $emailMessage,
        MailAttachmentData $attachment,
        int $position,
        EmailAttachmentScanStatus $scanStatus,
    ): EmailAttachment {
        $deduplicationKey = hash(
            'sha256',
            $emailMessage->id
            .'|'
            .$position
            .'|'
            .$attachment->fileName
        );

        return EmailAttachment::query()
            ->firstOrCreate(
                [
                    'deduplication_key' => $deduplicationKey,
                ],
                [
                    'email_message_id' => $emailMessage->id,

                    'position' => $position,

                    'external_id' => $attachment->externalId,

                    'file_name' => $attachment->fileName,

                    'mime_type' => $attachment->mimeType,

                    'size' => $attachment->size,

                    'disk' => 'local',

                    'path' => 'testing/outgoing-queue/'
                        .$emailMessage->id
                        .'/'
                        .$position
                        .'-'
                        .$attachment->fileName,

                    'checksum_sha256' => hash(
                        'sha256',
                        $attachment->content
                    ),

                    'content_id' => $attachment->contentId,

                    'is_inline' => $attachment->inline,

                    'scan_status' => $scanStatus,

                    'scanned_at' => in_array(
                        $scanStatus,
                        [
                            EmailAttachmentScanStatus::Clean,
                            EmailAttachmentScanStatus::Infected,
                            EmailAttachmentScanStatus::Failed,
                        ],
                        true
                    )
                            ? now()
                            : null,

                    'quarantined_at' => $scanStatus
                        === EmailAttachmentScanStatus::Infected
                            ? now()
                            : null,

                    'scan_result' => null,

                    'metadata' => $attachment->metadata,
                ]
            );
    }

    private function payload(
        string $idempotencyKey,
        array $attachments,
    ): OutgoingEmailMessageData {
        return new OutgoingEmailMessageData(
            idempotencyKey: $idempotencyKey,

            from: null,

            to: [
                new MailAddressData(
                    address: 'customer@example.test',

                    name: 'Test Customer',
                ),
            ],

            cc: [],

            bcc: [],

            replyTo: [],

            subject: 'Outgoing queue service test',

            textBody: 'Outgoing queue service test body.',

            htmlBody: '<p>Outgoing queue service test body.</p>',

            headers: [
                'X-SimpleDesk-Test' => 'outgoing-queue',
            ],

            attachments: $attachments,

            internetMessageId: null,

            inReplyToMessageId: null,

            references: [],

            metadata: [
                'test' => true,
            ],
        );
    }

    private function attachmentPayload(): MailAttachmentData
    {
        $contents =
            'Outgoing queue attachment contents.';

        return new MailAttachmentData(
            fileName: 'diagnostic.txt',

            mimeType: 'text/plain',

            size: strlen($contents),

            content: $contents,

            externalId: 'test-external-attachment',

            contentId: null,

            inline: false,

            metadata: [
                'source' => 'test',
            ],
        );
    }

    private function createMailbox(): Mailbox
    {
        $token = strtolower(
            (string) Str::ulid()
        );

        return Mailbox::query()->create([
            'name' => "Queue Mailbox {$token}",

            'email_address' => "queue-{$token}@example.test",

            'display_name' => 'Queue Mailbox',

            'department_id' => null,

            'is_active' => true,

            'is_default_outgoing' => false,

            'internal_notes' => null,
        ]);
    }

    private function createSentMessage(
        Mailbox $mailbox,
        string $idempotencyKey,
    ): EmailMessage {
        return EmailMessage::query()->create([
            'mailbox_id' => $mailbox->id,

            'mailbox_channel_id' => null,

            'ticket_id' => null,

            'ticket_reply_id' => null,

            'direction' => EmailMessageDirection::Outgoing,

            'driver' => null,

            'status' => EmailMessageStatus::Sent,

            'idempotency_key' => $idempotencyKey,

            'external_message_id' => 'provider-existing-message',

            'internet_message_id' => '<existing-sent@example.test>',

            'in_reply_to_message_id' => null,

            'reference_message_ids' => [],

            'sender_address' => $mailbox->email_address,

            'sender_name' => $mailbox->display_name,

            'to_recipients' => [
                [
                    'address' => 'customer@example.test',

                    'name' => 'Test Customer',
                ],
            ],

            'cc_recipients' => [],

            'bcc_recipients' => [],

            'reply_to_recipients' => [],

            'subject' => 'Existing sent message',

            'text_body' => 'Existing sent body.',

            'html_body' => null,

            'headers' => [],

            'metadata' => [
                'immutable' => true,
            ],

            'queued_at' => now()->subMinutes(2),

            'processing_started_at' => now()->subMinute(),

            'sent_at' => now(),

            'processed_at' => now(),

            'failed_at' => null,

            'failure_code' => null,

            'failure_message' => null,
        ]);
    }
}
