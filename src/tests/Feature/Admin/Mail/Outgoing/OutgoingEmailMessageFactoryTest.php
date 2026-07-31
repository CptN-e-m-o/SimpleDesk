<?php

namespace Tests\Feature\Admin\Mail\Outgoing;

use App\Enums\Admin\Mail\EmailAttachmentScanStatus;
use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Exceptions\Admin\Mail\MailStorageException;
use App\Models\Admin\Mail\EmailAttachment;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\Mailbox;
use App\Services\Admin\Mail\OutgoingEmailMessageFactory;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class OutgoingEmailMessageFactoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_message_without_attachments_is_created_successfully(): void
    {
        $message = $this->createOutgoingMessage();

        $result = $this->factory(
            antivirusEnabled: true
        )->make($message);

        $this->assertSame(
            $message->idempotency_key,
            $result->idempotencyKey
        );

        $this->assertSame(
            $message->sender_address,
            $result->from?->address
        );

        $this->assertSame(
            $message->subject,
            $result->subject
        );

        $this->assertSame(
            $message->text_body,
            $result->textBody
        );

        $this->assertSame(
            $message->html_body,
            $result->htmlBody
        );

        $this->assertCount(
            1,
            $result->to
        );

        $this->assertSame(
            $message->to_recipients[0]['address'],
            $result->to[0]->address
        );

        $this->assertSame(
            [],
            $result->attachments
        );
    }

    public function test_clean_attachment_is_loaded_from_storage(): void
    {
        $message = $this->createOutgoingMessage();

        $attachment = $this->createAttachment(
            message: $message,
            scanStatus: EmailAttachmentScanStatus::Clean,
            contents: 'Clean attachment contents.',
        );

        $result = $this->factory(
            antivirusEnabled: true
        )->make($message);

        $this->assertCount(
            1,
            $result->attachments
        );

        $resultAttachment = $result->attachments[0];

        $this->assertSame(
            $attachment->file_name,
            $resultAttachment->fileName
        );

        $this->assertSame(
            $attachment->mime_type,
            $resultAttachment->mimeType
        );

        $this->assertSame(
            $attachment->size,
            $resultAttachment->size
        );

        $this->assertSame(
            'Clean attachment contents.',
            $resultAttachment->content
        );

        $this->assertSame(
            $attachment->external_id,
            $resultAttachment->externalId
        );

        $this->assertSame(
            $attachment->content_id,
            $resultAttachment->contentId
        );

        $this->assertSame(
            $attachment->is_inline,
            $resultAttachment->inline
        );
    }

    public function test_not_scanned_attachment_is_allowed_when_antivirus_is_disabled(): void
    {
        $message = $this->createOutgoingMessage();

        $this->createAttachment(
            message: $message,
            scanStatus: EmailAttachmentScanStatus::NotScanned,
            contents: 'Not scanned attachment.',
        );

        $result = $this->factory(
            antivirusEnabled: false
        )->make($message);

        $this->assertCount(
            1,
            $result->attachments
        );

        $this->assertSame(
            'Not scanned attachment.',
            $result->attachments[0]->content
        );
    }

    public function test_not_scanned_attachment_is_rejected_when_antivirus_is_enabled(): void
    {
        $message = $this->createOutgoingMessage();

        $this->createAttachment(
            message: $message,
            scanStatus: EmailAttachmentScanStatus::NotScanned,
        );

        $this->expectException(
            MailStorageException::class
        );

        $this->expectExceptionMessage(
            'cannot be sent because of its scan status'
        );

        $this->factory(
            antivirusEnabled: true
        )->make($message);
    }

    public function test_pending_attachment_is_rejected(): void
    {
        $message = $this->createOutgoingMessage();

        $this->createAttachment(
            message: $message,
            scanStatus: EmailAttachmentScanStatus::Pending,
        );

        $this->expectException(
            MailStorageException::class
        );

        $this->expectExceptionMessage(
            'cannot be sent because of its scan status'
        );

        $this->factory(
            antivirusEnabled: true
        )->make($message);
    }

    public function test_infected_attachment_is_rejected(): void
    {
        $message = $this->createOutgoingMessage();

        $this->createAttachment(
            message: $message,
            scanStatus: EmailAttachmentScanStatus::Infected,
        );

        $this->expectException(
            MailStorageException::class
        );

        $this->expectExceptionMessage(
            'cannot be sent because of its scan status'
        );

        $this->factory(
            antivirusEnabled: true
        )->make($message);
    }

    public function test_failed_attachment_scan_is_rejected(): void
    {
        $message = $this->createOutgoingMessage();

        $this->createAttachment(
            message: $message,
            scanStatus: EmailAttachmentScanStatus::Failed,
        );

        $this->expectException(
            MailStorageException::class
        );

        $this->expectExceptionMessage(
            'cannot be sent because of its scan status'
        );

        $this->factory(
            antivirusEnabled: true
        )->make($message);
    }

    public function test_quarantined_attachment_is_rejected_even_when_scan_status_is_clean(): void
    {
        $message = $this->createOutgoingMessage();

        $this->createAttachment(
            message: $message,
            scanStatus: EmailAttachmentScanStatus::Clean,
            attributes: [
                'quarantined_at' => now(),
            ],
        );

        $this->expectException(
            MailStorageException::class
        );

        $this->expectExceptionMessage(
            'is quarantined'
        );

        $this->factory(
            antivirusEnabled: true
        )->make($message);
    }

    public function test_missing_attachment_file_is_rejected(): void
    {
        $message = $this->createOutgoingMessage();

        $attachment = $this->createAttachment(
            message: $message,
            scanStatus: EmailAttachmentScanStatus::Clean,
        );

        Storage::disk(
            $attachment->disk
        )->delete(
            $attachment->path
        );

        $this->expectException(
            MailStorageException::class
        );

        $this->expectExceptionMessage(
            'does not exist on disk'
        );

        $this->factory(
            antivirusEnabled: true
        )->make($message);
    }

    public function test_attachment_with_incorrect_stored_size_is_rejected(): void
    {
        $message = $this->createOutgoingMessage();

        $attachment = $this->createAttachment(
            message: $message,
            scanStatus: EmailAttachmentScanStatus::Clean,
            contents: 'Actual contents.',
        );

        $attachment->forceFill([
            'size' => 999,
        ])->save();

        $this->expectException(
            MailStorageException::class
        );

        $this->expectExceptionMessage(
            'size does not match the stored metadata'
        );

        $this->factory(
            antivirusEnabled: true,
            maxAttachmentBytes: 2000,
        )->make($message);
    }

    public function test_attachment_with_incorrect_checksum_is_rejected(): void
    {
        $message = $this->createOutgoingMessage();

        $attachment = $this->createAttachment(
            message: $message,
            scanStatus: EmailAttachmentScanStatus::Clean,
            contents: 'Checksum contents.',
        );

        $attachment->forceFill([
            'checksum_sha256' => str_repeat('0', 64),
        ])->save();

        $this->expectException(
            MailStorageException::class
        );

        $this->expectExceptionMessage(
            'checksum verification failed'
        );

        $this->factory(
            antivirusEnabled: true,
            verifyChecksums: true,
        )->make($message);
    }

    public function test_checksum_verification_can_be_disabled(): void
    {
        $message = $this->createOutgoingMessage();

        $attachment = $this->createAttachment(
            message: $message,
            scanStatus: EmailAttachmentScanStatus::Clean,
            contents: 'Checksum verification disabled.',
        );

        $attachment->forceFill([
            'checksum_sha256' => str_repeat('0', 64),
        ])->save();

        $result = $this->factory(
            antivirusEnabled: true,
            verifyChecksums: false,
        )->make($message);

        $this->assertCount(
            1,
            $result->attachments
        );

        $this->assertSame(
            'Checksum verification disabled.',
            $result->attachments[0]->content
        );
    }

    public function test_attachment_exceeding_individual_size_limit_is_rejected(): void
    {
        $message = $this->createOutgoingMessage();

        $this->createAttachment(
            message: $message,
            scanStatus: EmailAttachmentScanStatus::Clean,
            contents: str_repeat('A', 20),
        );

        $this->expectException(
            MailStorageException::class
        );

        $this->expectExceptionMessage(
            'exceeds the configured size limit'
        );

        $this->factory(
            antivirusEnabled: true,
            maxAttachmentBytes: 10,
            maxTotalAttachmentBytes: 100,
        )->make($message);
    }

    public function test_attachments_exceeding_total_size_limit_are_rejected(): void
    {
        $message = $this->createOutgoingMessage();

        $this->createAttachment(
            message: $message,
            scanStatus: EmailAttachmentScanStatus::Clean,
            contents: str_repeat('A', 10),
            position: 0,
        );

        $this->createAttachment(
            message: $message,
            scanStatus: EmailAttachmentScanStatus::Clean,
            contents: str_repeat('B', 10),
            position: 1,
        );

        $this->expectException(
            MailStorageException::class
        );

        $this->expectExceptionMessage(
            'attachments exceed the configured total size limit'
        );

        $this->factory(
            antivirusEnabled: true,
            maxAttachmentBytes: 100,
            maxTotalAttachmentBytes: 15,
        )->make($message);
    }

    private function factory(
        bool $antivirusEnabled,
        int $maxAttachmentBytes = 25_000_000,
        int $maxTotalAttachmentBytes = 40_000_000,
        bool $verifyChecksums = true,
    ): OutgoingEmailMessageFactory {
        return new OutgoingEmailMessageFactory(
            filesystem: $this->app->make(
                FilesystemFactory::class
            ),

            maxAttachmentBytes: $maxAttachmentBytes,

            maxTotalAttachmentBytes: $maxTotalAttachmentBytes,

            verifyChecksums: $verifyChecksums,

            antivirusEnabled: $antivirusEnabled,
        );
    }

    private function createOutgoingMessage(): EmailMessage
    {
        $token = strtolower(
            (string) Str::ulid()
        );

        $mailbox = Mailbox::query()->create([
            'name' => "Factory Mailbox {$token}",

            'email_address' => "factory-{$token}@example.test",

            'display_name' => 'Factory Mailbox',

            'department_id' => null,

            'is_active' => true,

            'is_default_outgoing' => false,

            'internal_notes' => null,
        ]);

        return EmailMessage::query()->create([
            'mailbox_id' => $mailbox->id,

            'mailbox_channel_id' => null,

            'ticket_id' => null,

            'ticket_reply_id' => null,

            'direction' => EmailMessageDirection::Outgoing,

            'driver' => null,

            'status' => EmailMessageStatus::Queued,

            'idempotency_key' => "factory-test-{$token}",

            'external_message_id' => null,

            'internet_message_id' => "<factory-{$token}@example.test>",

            'in_reply_to_message_id' => '<previous-message@example.test>',

            'reference_message_ids' => [
                '<first-reference@example.test>',
                '<previous-message@example.test>',
            ],

            'sender_address' => $mailbox->email_address,

            'sender_name' => $mailbox->display_name,

            'to_recipients' => [
                [
                    'address' => "customer-{$token}@example.test",

                    'name' => 'Test Customer',
                ],
            ],

            'cc_recipients' => [],

            'bcc_recipients' => [],

            'reply_to_recipients' => [],

            'subject' => 'Outgoing message factory test',

            'text_body' => 'Factory test text body.',

            'html_body' => '<p>Factory test HTML body.</p>',

            'headers' => [
                'X-SimpleDesk-Test' => 'factory',
            ],

            'metadata' => [
                'test' => true,
            ],

            'queued_at' => now(),
        ]);
    }

    private function createAttachment(
        EmailMessage $message,
        EmailAttachmentScanStatus $scanStatus,
        string $contents = 'Attachment contents.',
        int $position = 0,
        array $attributes = [],
    ): EmailAttachment {
        $token = strtolower(
            (string) Str::ulid()
        );

        $path =
            'testing/outgoing-factory/'
            ."{$message->id}/{$token}.txt";

        Storage::disk('local')->put(
            $path,
            $contents
        );

        return EmailAttachment::query()->create(
            array_merge(
                [
                    'email_message_id' => $message->id,

                    'position' => $position,

                    'external_id' => "external-attachment-{$token}",

                    'deduplication_key' => hash(
                        'sha256',
                        "{$message->id}|{$position}|{$token}"
                    ),

                    'file_name' => "attachment-{$position}.txt",

                    'mime_type' => 'text/plain',

                    'size' => strlen($contents),

                    'disk' => 'local',

                    'path' => $path,

                    'checksum_sha256' => hash(
                        'sha256',
                        $contents
                    ),

                    'content_id' => "content-{$token}",

                    'is_inline' => false,

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

                    'quarantined_at' => null,

                    'scan_result' => null,

                    'metadata' => [
                    'source' => 'test',
                    ],
                ],
                $attributes
            )
        );
    }
}
