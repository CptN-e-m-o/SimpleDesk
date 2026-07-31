<?php

namespace Tests\Feature\Admin\Mail\Incoming;

use App\Data\Admin\Mail\MailAddressData;
use App\Data\Admin\Mail\MailAttachmentData;
use App\Data\Admin\Mail\NormalizedInboundMessageData;
use App\Enums\Admin\Mail\EmailAttachmentScanStatus;
use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Enums\Admin\Mail\MailboxChannelDirection;
use App\Enums\Admin\Mail\MailboxDriver;
use App\Enums\Admin\Mail\MailboxHealthStatus;
use App\Events\Admin\Mail\InboundEmailStored;
use App\Exceptions\Admin\Mail\InboundMessageAlreadyProcessingException;
use App\Exceptions\Admin\Mail\InboundMessagePersistenceException;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\Mailbox;
use App\Models\Admin\Mail\MailboxChannel;
use App\Services\Admin\Mail\IncomingEmailMessagePersister;
use App\Services\Admin\Mail\MailAttachmentStorageService;
use App\Services\Admin\Mail\MailMessageIdempotencyKeyFactory;
use App\Services\Admin\Mail\RawEmailStorageService;
use App\Services\Admin\Mail\RejectedEmailAttachmentPersister;
use DateTimeImmutable;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class IncomingEmailMessagePersisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        Event::fake([
            InboundEmailStored::class,
        ]);
    }

    public function test_new_message_is_persisted_with_raw_message_and_attachment(): void
    {
        $mailbox = $this->createMailbox();

        $channel = $this->createChannel(
            mailbox: $mailbox,
        );

        $rawMessage =
            "Message-ID: <incoming-100@example.test>\r\n"
            . "From: customer@example.test\r\n"
            . "To: support@example.test\r\n"
            . "Subject: Test incoming message\r\n"
            . "\r\n"
            . "Incoming raw message body.";

        $attachmentContents =
            'Incoming attachment contents.';

        $message = $this->normalizedMessage(
            externalMessageId:
            'imap:INBOX:1001:100',

            internetMessageId:
            '<incoming-100@example.test>',

            rawMessage:
            $rawMessage,

            attachments: [
                new MailAttachmentData(
                    fileName:
                    'diagnostic.txt',

                    mimeType:
                    'text/plain',

                    size:
                    strlen($attachmentContents),

                    content:
                    $attachmentContents,

                    externalId:
                    'imap-part-1',

                    contentId:
                    null,

                    inline:
                    false,

                    metadata: [
                        'imap_part_number' => 1,
                    ],
                ),
            ],
        );

        $result = $this
            ->service()
            ->persist(
                channel: $channel,
                message: $message,
            );

        $this->assertTrue(
            $result->created
        );

        $this->assertFalse(
            $result->duplicate
        );

        $emailMessage = $result->emailMessage;

        $this->assertSame(
            EmailMessageStatus::Received,
            $emailMessage->status
        );

        $this->assertSame(
            EmailMessageDirection::Incoming,
            $emailMessage->direction
        );

        $this->assertSame(
            MailboxDriver::Imap,
            $emailMessage->driver
        );

        $this->assertSame(
            $mailbox->id,
            $emailMessage->mailbox_id
        );

        $this->assertSame(
            $channel->id,
            $emailMessage->mailbox_channel_id
        );

        $this->assertSame(
            'imap:INBOX:1001:100',
            $emailMessage->external_message_id
        );

        $this->assertSame(
            '<incoming-100@example.test>',
            $emailMessage->internet_message_id
        );

        $this->assertSame(
            'customer@example.test',
            $emailMessage->sender_address
        );

        $this->assertSame(
            'Test Customer',
            $emailMessage->sender_name
        );

        $this->assertSame(
            'Test incoming message',
            $emailMessage->subject
        );

        $this->assertNull(
            $emailMessage->processing_started_at
        );

        $this->assertNull(
            $emailMessage->failed_at
        );

        $this->assertNull(
            $emailMessage->failure_code
        );

        $this->assertNotNull(
            $emailMessage->raw_message_path
        );

        $this->assertSame(
            'local',
            $emailMessage->raw_message_disk
        );

        $this->assertSame(
            strlen($rawMessage),
            $emailMessage->raw_message_size
        );

        $this->assertSame(
            hash('sha256', $rawMessage),
            $emailMessage->raw_message_checksum
        );

        Storage::disk(
            $emailMessage->raw_message_disk
        )->assertExists(
            $emailMessage->raw_message_path
        );

        $this->assertSame(
            $rawMessage,
            Storage::disk(
                $emailMessage->raw_message_disk
            )->get(
                $emailMessage->raw_message_path
            )
        );

        $this->assertCount(
            1,
            $emailMessage->attachments
        );

        $attachment = $emailMessage
            ->attachments
            ->first();

        $this->assertNotNull(
            $attachment
        );

        $this->assertSame(
            'diagnostic.txt',
            $attachment->file_name
        );

        $this->assertSame(
            'text/plain',
            $attachment->mime_type
        );

        $this->assertSame(
            strlen($attachmentContents),
            $attachment->size
        );

        $this->assertSame(
            hash('sha256', $attachmentContents),
            $attachment->checksum_sha256
        );

        $this->assertSame(
            EmailAttachmentScanStatus::NotScanned,
            $attachment->scan_status
        );

        Storage::disk(
            $attachment->disk
        )->assertExists(
            $attachment->path
        );

        $this->assertSame(
            $attachmentContents,
            Storage::disk(
                $attachment->disk
            )->get(
                $attachment->path
            )
        );

        $attachmentProcessing = data_get(
            $emailMessage->metadata,
            'attachment_processing'
        );

        $this->assertSame(
            1,
            $attachmentProcessing['stored_count']
            ?? null
        );

        $this->assertSame(
            0,
            $attachmentProcessing['rejected_count']
            ?? null
        );

        $this->assertNotNull(
            $attachmentProcessing['completed_at']
            ?? null
        );

        $this->assertSame(
            100,
            data_get(
                $emailMessage->metadata,
                'imap_uid'
            )
        );

        Event::assertDispatched(
            InboundEmailStored::class,
            function (
                InboundEmailStored $event
            ) use ($emailMessage): bool {
                return $event->emailMessageId
                    === $emailMessage->id;
            }
        );
    }

    public function test_same_message_id_is_duplicate_across_channels_of_same_mailbox(): void
    {
        $mailbox = $this->createMailbox();

        $primary = $this->createChannel(
            mailbox: $mailbox,
            name: 'Primary IMAP',
            primary: true,
            failoverOrder: 0,
        );

        $fallback = $this->createChannel(
            mailbox: $mailbox,
            name: 'Fallback IMAP',
            primary: false,
            failoverOrder: 10,
        );

        $firstMessage = $this->normalizedMessage(
            externalMessageId:
            'imap:INBOX:1001:101',

            internetMessageId:
            '<shared-message@example.test>',
        );

        $secondMessage = $this->normalizedMessage(
            externalMessageId:
            'imap:INBOX:2002:500',

            internetMessageId:
            '<shared-message@example.test>',
        );

        $service = $this->service();

        $firstResult = $service->persist(
            channel: $primary,
            message: $firstMessage,
        );

        $secondResult = $service->persist(
            channel: $fallback,
            message: $secondMessage,
        );

        $this->assertTrue(
            $firstResult->created
        );

        $this->assertFalse(
            $firstResult->duplicate
        );

        $this->assertFalse(
            $secondResult->created
        );

        $this->assertTrue(
            $secondResult->duplicate
        );

        $this->assertSame(
            $firstResult->emailMessage->id,
            $secondResult->emailMessage->id
        );

        $this->assertDatabaseCount(
            'email_messages',
            1
        );

        $storedMessage = EmailMessage::query()
            ->sole();

        $this->assertSame(
            $primary->id,
            $storedMessage->mailbox_channel_id
        );

        $this->assertSame(
            'imap:INBOX:1001:101',
            $storedMessage->external_message_id
        );

        Event::assertDispatchedTimes(
            InboundEmailStored::class,
            1
        );
    }

    public function test_external_message_id_is_scoped_to_channel_when_message_id_is_missing(): void
    {
        $mailbox = $this->createMailbox();

        $primary = $this->createChannel(
            mailbox: $mailbox,
            name: 'Primary IMAP',
            primary: true,
            failoverOrder: 0,
        );

        $fallback = $this->createChannel(
            mailbox: $mailbox,
            name: 'Fallback IMAP',
            primary: false,
            failoverOrder: 10,
        );

        $firstMessage = $this->normalizedMessage(
            externalMessageId:
            'imap:INBOX:1001:102',

            internetMessageId:
            null,
        );

        $secondMessage = $this->normalizedMessage(
            externalMessageId:
            'imap:INBOX:1001:102',

            internetMessageId:
            null,
        );

        $service = $this->service();

        $firstResult = $service->persist(
            channel: $primary,
            message: $firstMessage,
        );

        $secondResult = $service->persist(
            channel: $fallback,
            message: $secondMessage,
        );

        $this->assertTrue(
            $firstResult->created
        );

        $this->assertFalse(
            $firstResult->duplicate
        );

        $this->assertTrue(
            $secondResult->created
        );

        $this->assertFalse(
            $secondResult->duplicate
        );

        $this->assertNotSame(
            $firstResult->emailMessage->id,
            $secondResult->emailMessage->id
        );

        $this->assertNotSame(
            $firstResult->emailMessage->idempotency_key,
            $secondResult->emailMessage->idempotency_key
        );

        $this->assertDatabaseCount(
            'email_messages',
            2
        );

        Event::assertDispatchedTimes(
            InboundEmailStored::class,
            2
        );
    }

    public function test_recent_processing_message_cannot_be_processed_in_parallel(): void
    {
        $mailbox = $this->createMailbox();

        $channel = $this->createChannel(
            mailbox: $mailbox,
        );

        $message = $this->normalizedMessage(
            externalMessageId:
            'imap:INBOX:1001:103',

            internetMessageId:
            '<processing-lock@example.test>',
        );

        $service = $this->service(
            processingLockSeconds: 600
        );

        $firstResult = $service->persist(
            channel: $channel,
            message: $message,
        );

        $emailMessage = $firstResult->emailMessage;

        $emailMessage->forceFill([
            'status' =>
                EmailMessageStatus::Processing,

            'processing_started_at' =>
                now()->subSeconds(30),

            'processed_at' =>
                null,
        ])->save();

        try {
            $service->persist(
                channel: $channel,
                message: $message,
            );

            $this->fail(
                'Expected InboundMessageAlreadyProcessingException was not thrown.'
            );
        } catch (
        InboundMessageAlreadyProcessingException $exception
        ) {
            $this->assertNotSame(
                '',
                trim($exception->getMessage())
            );
        }

        $emailMessage->refresh();

        $this->assertSame(
            EmailMessageStatus::Processing,
            $emailMessage->status
        );

        $this->assertNotNull(
            $emailMessage->processing_started_at
        );

        $this->assertDatabaseCount(
            'email_messages',
            1
        );

        Event::assertDispatchedTimes(
            InboundEmailStored::class,
            1
        );
    }

    public function test_stale_processing_message_is_reused_and_processed_again(): void
    {
        $mailbox = $this->createMailbox();

        $channel = $this->createChannel(
            mailbox: $mailbox,
        );

        $originalMessage = $this->normalizedMessage(
            externalMessageId:
            'imap:INBOX:1001:104',

            internetMessageId:
            '<stale-processing@example.test>',

            subject:
            'Original subject',
        );

        $service = $this->service(
            processingLockSeconds: 300
        );

        $firstResult = $service->persist(
            channel: $channel,
            message: $originalMessage,
        );

        $emailMessage = $firstResult->emailMessage;

        $emailMessage->forceFill([
            'status' =>
                EmailMessageStatus::Processing,

            'processing_started_at' =>
                now()->subSeconds(600),

            'processed_at' =>
                null,

            'failed_at' =>
                now()->subMinutes(10),

            'failure_code' =>
                'previous_failure',

            'failure_message' =>
                'Previous processing failed.',
        ])->save();

        $updatedMessage = $this->normalizedMessage(
            externalMessageId:
            'imap:INBOX:1001:104',

            internetMessageId:
            '<stale-processing@example.test>',

            subject:
            'Updated subject after retry',

            textBody:
            'Updated text after retry.',
        );

        $secondResult = $service->persist(
            channel: $channel,
            message: $updatedMessage,
        );

        $this->assertFalse(
            $secondResult->created
        );

        $this->assertFalse(
            $secondResult->duplicate
        );

        $this->assertSame(
            $firstResult->emailMessage->id,
            $secondResult->emailMessage->id
        );

        $emailMessage->refresh();

        $this->assertSame(
            EmailMessageStatus::Received,
            $emailMessage->status
        );

        $this->assertSame(
            'Updated subject after retry',
            $emailMessage->subject
        );

        $this->assertSame(
            'Updated text after retry.',
            $emailMessage->text_body
        );

        $this->assertNull(
            $emailMessage->processing_started_at
        );

        $this->assertNull(
            $emailMessage->failed_at
        );

        $this->assertNull(
            $emailMessage->failure_code
        );

        $this->assertNull(
            $emailMessage->failure_message
        );

        $this->assertDatabaseCount(
            'email_messages',
            1
        );

        Event::assertDispatchedTimes(
            InboundEmailStored::class,
            2
        );
    }

    public function test_raw_storage_failure_marks_message_failed_and_wraps_exception(): void
    {
        $mailbox = $this->createMailbox();

        $channel = $this->createChannel(
            mailbox: $mailbox,
        );

        $message = $this->normalizedMessage(
            externalMessageId:
            'imap:INBOX:1001:105',

            internetMessageId:
            '<raw-storage-failure@example.test>',

            rawMessage:
            'Raw message that cannot be stored.',
        );

        $rawStorage = Mockery::mock(
            RawEmailStorageService::class
        );

        $rawStorage
            ->shouldReceive('store')
            ->once()
            ->andThrow(
                new RuntimeException(
                    'Test raw storage failure.'
                )
            );

        $attachmentStorage = Mockery::mock(
            MailAttachmentStorageService::class
        );

        $attachmentStorage->shouldNotReceive(
            'store'
        );

        $rejectedAttachments = Mockery::mock(
            RejectedEmailAttachmentPersister::class
        );

        $rejectedAttachments->shouldNotReceive(
            'persist'
        );

        $service = $this->service(
            rawStorage: $rawStorage,
            attachmentStorage: $attachmentStorage,
            rejectedAttachments: $rejectedAttachments,
        );

        try {
            $service->persist(
                channel: $channel,
                message: $message,
            );

            $this->fail(
                'Expected InboundMessagePersistenceException was not thrown.'
            );
        } catch (
        InboundMessagePersistenceException $exception
        ) {
            $this->assertStringContainsString(
                'Test raw storage failure.',
                $exception->getMessage()
            );
        }

        $emailMessage = EmailMessage::query()
            ->sole();

        $this->assertSame(
            EmailMessageStatus::Failed,
            $emailMessage->status
        );

        $this->assertNull(
            $emailMessage->processing_started_at
        );

        $this->assertNotNull(
            $emailMessage->failed_at
        );

        $this->assertSame(
            'raw_message_storage_failed',
            $emailMessage->failure_code
        );

        $this->assertSame(
            'Test raw storage failure.',
            $emailMessage->failure_message
        );

        $this->assertDatabaseCount(
            'email_attachments',
            0
        );

        Event::assertNotDispatched(
            InboundEmailStored::class
        );
    }

    private function service(
        ?RawEmailStorageService $rawStorage = null,
        ?MailAttachmentStorageService $attachmentStorage = null,
        ?RejectedEmailAttachmentPersister $rejectedAttachments = null,
        int $processingLockSeconds = 600,
    ): IncomingEmailMessagePersister {
        $filesystem = $this->app->make(
            FilesystemFactory::class
        );

        $rawStorage ??= new RawEmailStorageService(
            filesystem:
            $filesystem,

            disk:
            'local',

            rootPath:
            'testing/mail/raw',
        );

        $attachmentStorage ??=
            new MailAttachmentStorageService(
                filesystem:
                $filesystem,

                disk:
                'local',

                rootPath:
                'testing/mail/attachments',

                scanDispatcher:
                null,

                antivirusEnabled:
                false,
            );

        $rejectedAttachments ??=
            new RejectedEmailAttachmentPersister();

        return new IncomingEmailMessagePersister(
            keys:
            new MailMessageIdempotencyKeyFactory(),

            rawStorage:
            $rawStorage,

            attachmentStorage:
            $attachmentStorage,

            rejectedAttachments:
            $rejectedAttachments,

            processingLockSeconds:
            $processingLockSeconds,
        );
    }

    private function normalizedMessage(
        string $externalMessageId,
        ?string $internetMessageId,
        string $subject = 'Test incoming message',
        string $textBody = 'Incoming test message body.',
        ?string $rawMessage = null,
        array $attachments = [],
    ): NormalizedInboundMessageData {
        return new NormalizedInboundMessageData(
            externalMessageId:
            $externalMessageId,

            internetMessageId:
            $internetMessageId,

            from:
            new MailAddressData(
                address:
                'customer@example.test',

                name:
                'Test Customer',
            ),

            to: [
                new MailAddressData(
                    address:
                    'support@example.test',

                    name:
                    'SimpleDesk Support',
                ),
            ],

            cc:
            [],

            bcc:
            [],

            replyTo:
            [],

            subject:
            $subject,

            textBody:
            $textBody,

            htmlBody:
            '<p>'
            . htmlspecialchars(
                $textBody,
                ENT_QUOTES
                | ENT_SUBSTITUTE,
                'UTF-8'
            )
            . '</p>',

            headers: [
                'message-id' => [
                    $internetMessageId,
                ],

                'x-simpledesk-test' => [
                    'incoming-persister',
                ],
            ],

            attachments:
            $attachments,

            rejectedAttachments:
            [],

            receivedAt:
            new DateTimeImmutable(
                '2026-07-31 12:00:00'
            ),

            inReplyToMessageId:
            null,

            references:
            [],

            metadata: [
                'imap_uid' =>
                    100,

                'imap_uidvalidity' =>
                    1001,

                'imap_folder' =>
                    'INBOX',
            ],

            rawMessage:
            $rawMessage,
        );
    }

    private function createMailbox(): Mailbox
    {
        $token = strtolower(
            (string) Str::ulid()
        );

        return Mailbox::query()->create([
            'name' =>
                "Persister Mailbox {$token}",

            'email_address' =>
                "persister-{$token}@example.test",

            'display_name' =>
                'Persister Mailbox',

            'department_id' =>
                null,

            'is_active' =>
                true,

            'is_default_outgoing' =>
                false,

            'internal_notes' =>
                null,
        ]);
    }

    private function createChannel(
        Mailbox $mailbox,
        string $name = 'Incoming IMAP',
        bool $primary = true,
        int $failoverOrder = 0,
    ): MailboxChannel {
        $token = strtolower(
            (string) Str::ulid()
        );

        $channel = new MailboxChannel();

        $channel->forceFill([
            'mailbox_id' =>
                $mailbox->id,

            'provider_connection_id' =>
                null,

            'name' =>
                "{$name} {$token}",

            'direction' =>
                MailboxChannelDirection::Incoming,

            'driver' =>
                MailboxDriver::Imap,

            'is_primary' =>
                $primary,

            'failover_order' =>
                $failoverOrder,

            'is_enabled' =>
                true,

            'configuration' => [
                'folder' =>
                    'INBOX',
            ],

            'health_status' =>
                MailboxHealthStatus::Unknown,
        ])->save();

        return $channel->fresh();
    }
}
