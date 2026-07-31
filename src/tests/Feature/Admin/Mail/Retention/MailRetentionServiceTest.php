<?php

namespace Tests\Feature\Admin\Mail\Retention;

use App\Enums\Admin\Mail\EmailAttachmentScanStatus;
use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Models\Admin\Mail\EmailAttachment;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\Mailbox;
use App\Services\Admin\Mail\Retention\MailRetentionService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class MailRetentionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        config()->set(
            'simpledesk-mail-automation.retention.categories.raw_messages.enabled',
            true
        );

        config()->set(
            'simpledesk-mail-automation.retention.categories.clean_attachments.enabled',
            true
        );

        config()->set(
            'simpledesk-mail-automation.retention.categories.quarantined_attachments.enabled',
            true
        );

        config()->set(
            'simpledesk-mail-automation.retention.categories.attempts.enabled',
            true
        );

        config()->set(
            'simpledesk-mail-automation.retention.categories.quarantines.enabled',
            true
        );

        config()->set(
            'simpledesk-mail-automation.retention.categories.messages.enabled',
            true
        );
    }

    public function test_raw_message_dry_run_reports_candidate_without_deleting_file_or_metadata(): void
    {
        $mailbox = $this->createMailbox();

        $message = $this->createMessage(
            mailbox: $mailbox,
            status: EmailMessageStatus::Processed,
            createdAt: now()->subDays(100),
        );

        $rawContents =
            "Message-ID: <retention-dry-run@example.test>\r\n"
            . "Subject: Retention dry-run\r\n"
            . "\r\n"
            . "Dry-run raw message body.";

        $rawPath = $this->attachRawMessage(
            message: $message,
            contents: $rawContents,
        );

        $result = $this
            ->service()
            ->prune(
                categories: [
                    MailRetentionService::CATEGORY_RAW_MESSAGES,
                ],
                dryRun: true,
                limit: 100,
                before: CarbonImmutable::now()->subDays(30),
            );

        $stats = $result->categories[
        MailRetentionService::CATEGORY_RAW_MESSAGES
        ];

        $this->assertTrue(
            $result->dryRun
        );

        $this->assertSame(
            1,
            $stats['candidates']
        );

        $this->assertSame(
            0,
            $stats['records_pruned']
        );

        $this->assertSame(
            0,
            $stats['files_pruned']
        );

        $this->assertSame(
            0,
            $stats['missing_files']
        );

        $this->assertSame(
            strlen($rawContents),
            $stats['bytes']
        );

        $this->assertSame(
            0,
            $stats['errors']
        );

        $this->assertSame(
            1,
            $result->totalCandidates()
        );

        $this->assertSame(
            0,
            $result->totalRecordsPruned()
        );

        $this->assertSame(
            strlen($rawContents),
            $result->totalBytes()
        );

        $message->refresh();

        $this->assertSame(
            'local',
            $message->raw_message_disk
        );

        $this->assertSame(
            $rawPath,
            $message->raw_message_path
        );

        $this->assertSame(
            strlen($rawContents),
            $message->raw_message_size
        );

        Storage::disk('local')->assertExists(
            $rawPath
        );
    }

    public function test_old_raw_message_file_is_deleted_and_storage_metadata_is_cleared(): void
    {
        $mailbox = $this->createMailbox();

        $message = $this->createMessage(
            mailbox: $mailbox,
            status: EmailMessageStatus::Processed,
            createdAt: now()->subDays(100),
        );

        $rawContents =
            "Message-ID: <retention-delete@example.test>\r\n"
            . "Subject: Retention delete\r\n"
            . "\r\n"
            . "Raw message that should be deleted.";

        $rawPath = $this->attachRawMessage(
            message: $message,
            contents: $rawContents,
        );

        $result = $this
            ->service()
            ->prune(
                categories: [
                    MailRetentionService::CATEGORY_RAW_MESSAGES,
                ],
                dryRun: false,
                limit: 100,
                before: CarbonImmutable::now()->subDays(30),
            );

        $stats = $result->categories[
        MailRetentionService::CATEGORY_RAW_MESSAGES
        ];

        $this->assertFalse(
            $result->dryRun
        );

        $this->assertSame(
            1,
            $stats['candidates']
        );

        $this->assertSame(
            1,
            $stats['records_pruned']
        );

        $this->assertSame(
            1,
            $stats['files_pruned']
        );

        $this->assertSame(
            0,
            $stats['missing_files']
        );

        $this->assertSame(
            strlen($rawContents),
            $stats['bytes']
        );

        $this->assertSame(
            0,
            $stats['errors']
        );

        Storage::disk('local')->assertMissing(
            $rawPath
        );

        $message->refresh();

        $this->assertNull(
            $message->raw_message_disk
        );

        $this->assertNull(
            $message->raw_message_path
        );

        $this->assertNull(
            $message->raw_message_size
        );

        $this->assertNull(
            $message->raw_message_checksum
        );

        $this->assertDatabaseHas(
            'email_messages',
            [
                'id' => $message->id,
            ]
        );
    }

    public function test_old_clean_attachment_is_deleted_but_email_message_remains(): void
    {
        $mailbox = $this->createMailbox();

        $message = $this->createMessage(
            mailbox: $mailbox,
            status: EmailMessageStatus::Processed,
            createdAt: now()->subDays(200),
        );

        $attachmentContents =
            'Clean attachment selected by retention.';

        $attachment = $this->createAttachment(
            message: $message,
            contents: $attachmentContents,
            scanStatus: EmailAttachmentScanStatus::Clean,
            createdAt: now()->subDays(200),
        );

        $result = $this
            ->service()
            ->prune(
                categories: [
                    MailRetentionService::CATEGORY_CLEAN_ATTACHMENTS,
                ],
                dryRun: false,
                limit: 100,
                before: CarbonImmutable::now()->subDays(30),
            );

        $stats = $result->categories[
        MailRetentionService::CATEGORY_CLEAN_ATTACHMENTS
        ];

        $this->assertSame(
            1,
            $stats['candidates']
        );

        $this->assertSame(
            1,
            $stats['records_pruned']
        );

        $this->assertSame(
            1,
            $stats['files_pruned']
        );

        $this->assertSame(
            0,
            $stats['missing_files']
        );

        $this->assertSame(
            strlen($attachmentContents),
            $stats['bytes']
        );

        $this->assertSame(
            0,
            $stats['errors']
        );

        Storage::disk(
            $attachment->disk
        )->assertMissing(
            $attachment->path
        );

        $this->assertDatabaseMissing(
            'email_attachments',
            [
                'id' => $attachment->id,
            ]
        );

        $this->assertDatabaseHas(
            'email_messages',
            [
                'id' => $message->id,
            ]
        );
    }

    public function test_old_unlinked_terminal_message_is_deleted_with_raw_file_and_attachments(): void
    {
        $mailbox = $this->createMailbox();

        $message = $this->createMessage(
            mailbox: $mailbox,
            status: EmailMessageStatus::Processed,
            createdAt: now()->subDays(500),
        );

        $rawContents =
            "Message-ID: <full-retention@example.test>\r\n"
            . "Subject: Full retention\r\n"
            . "\r\n"
            . "This complete message should be deleted.";

        $rawPath = $this->attachRawMessage(
            message: $message,
            contents: $rawContents,
        );

        $attachmentContents =
            'Attachment belonging to deleted message.';

        $attachment = $this->createAttachment(
            message: $message,
            contents: $attachmentContents,
            scanStatus: EmailAttachmentScanStatus::Clean,
            createdAt: now()->subDays(500),
        );

        $result = $this
            ->service()
            ->prune(
                categories: [
                    MailRetentionService::CATEGORY_MESSAGES,
                ],
                dryRun: false,
                limit: 100,
                before: CarbonImmutable::now()->subDays(30),
            );

        $stats = $result->categories[
        MailRetentionService::CATEGORY_MESSAGES
        ];

        $expectedBytes =
            strlen($rawContents)
            + strlen($attachmentContents);

        $this->assertSame(
            1,
            $stats['candidates']
        );

        $this->assertSame(
            1,
            $stats['records_pruned']
        );

        $this->assertSame(
            2,
            $stats['files_pruned']
        );

        $this->assertSame(
            0,
            $stats['missing_files']
        );

        $this->assertSame(
            $expectedBytes,
            $stats['bytes']
        );

        $this->assertSame(
            0,
            $stats['errors']
        );

        Storage::disk('local')->assertMissing(
            $rawPath
        );

        Storage::disk(
            $attachment->disk
        )->assertMissing(
            $attachment->path
        );

        $this->assertDatabaseMissing(
            'email_messages',
            [
                'id' => $message->id,
            ]
        );

        $this->assertDatabaseMissing(
            'email_attachments',
            [
                'id' => $attachment->id,
            ]
        );

        $this->assertSame(
            1,
            $result->totalCandidates()
        );

        $this->assertSame(
            1,
            $result->totalRecordsPruned()
        );

        $this->assertSame(
            2,
            $result->totalFilesPruned()
        );

        $this->assertSame(
            $expectedBytes,
            $result->totalBytes()
        );
    }

    public function test_retention_limit_and_safety_filters_only_process_first_eligible_record(): void
    {
        $mailbox = $this->createMailbox();

        $firstEligible = $this->createMessage(
            mailbox: $mailbox,
            status: EmailMessageStatus::Processed,
            createdAt: now()->subDays(100),
        );

        $firstPath = $this->attachRawMessage(
            message: $firstEligible,
            contents: 'First eligible raw message.',
        );

        $secondEligible = $this->createMessage(
            mailbox: $mailbox,
            status: EmailMessageStatus::Processed,
            createdAt: now()->subDays(100),
        );

        $secondPath = $this->attachRawMessage(
            message: $secondEligible,
            contents: 'Second eligible raw message.',
        );

        $freshTerminal = $this->createMessage(
            mailbox: $mailbox,
            status: EmailMessageStatus::Processed,
            createdAt: now()->subDay(),
        );

        $freshPath = $this->attachRawMessage(
            message: $freshTerminal,
            contents: 'Fresh terminal raw message.',
        );

        $oldNonTerminal = $this->createMessage(
            mailbox: $mailbox,
            status: EmailMessageStatus::Received,
            createdAt: now()->subDays(100),
        );

        $nonTerminalPath = $this->attachRawMessage(
            message: $oldNonTerminal,
            contents: 'Old but non-terminal raw message.',
        );

        $result = $this
            ->service()
            ->prune(
                categories: [
                    MailRetentionService::CATEGORY_RAW_MESSAGES,
                ],
                dryRun: false,
                limit: 1,
                before: CarbonImmutable::now()->subDays(30),
            );

        $stats = $result->categories[
        MailRetentionService::CATEGORY_RAW_MESSAGES
        ];

        $this->assertSame(
            1,
            $stats['candidates']
        );

        $this->assertSame(
            1,
            $stats['records_pruned']
        );

        $this->assertSame(
            1,
            $stats['files_pruned']
        );

        $this->assertSame(
            0,
            $stats['errors']
        );

        $firstEligible->refresh();
        $secondEligible->refresh();
        $freshTerminal->refresh();
        $oldNonTerminal->refresh();

        $this->assertNull(
            $firstEligible->raw_message_path
        );

        Storage::disk('local')->assertMissing(
            $firstPath
        );

        $this->assertSame(
            $secondPath,
            $secondEligible->raw_message_path
        );

        Storage::disk('local')->assertExists(
            $secondPath
        );

        $this->assertSame(
            $freshPath,
            $freshTerminal->raw_message_path
        );

        Storage::disk('local')->assertExists(
            $freshPath
        );

        $this->assertSame(
            $nonTerminalPath,
            $oldNonTerminal->raw_message_path
        );

        Storage::disk('local')->assertExists(
            $nonTerminalPath
        );
    }

    private function service(): MailRetentionService
    {
        return new MailRetentionService(
            filesystem: $this->app->make(
                FilesystemFactory::class
            ),
        );
    }

    private function createMailbox(): Mailbox
    {
        $token = strtolower(
            (string) Str::ulid()
        );

        return Mailbox::query()->create([
            'name' =>
                "Retention Mailbox {$token}",

            'email_address' =>
                "retention-{$token}@example.test",

            'display_name' =>
                'Retention Mailbox',

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

    private function createMessage(
        Mailbox $mailbox,
        EmailMessageStatus $status,
        mixed $createdAt,
    ): EmailMessage {
        $token = strtolower(
            (string) Str::ulid()
        );

        $message = EmailMessage::query()->create([
            'mailbox_id' =>
                $mailbox->id,

            'mailbox_channel_id' =>
                null,

            'ticket_id' =>
                null,

            'ticket_reply_id' =>
                null,

            'direction' =>
                EmailMessageDirection::Incoming,

            'driver' =>
                null,

            'status' =>
                $status,

            'idempotency_key' =>
                "retention-test-{$token}",

            'external_message_id' =>
                "retention-external-{$token}",

            'internet_message_id' =>
                "<retention-{$token}@example.test>",

            'in_reply_to_message_id' =>
                null,

            'reference_message_ids' =>
                [],

            'sender_address' =>
                'customer@example.test',

            'sender_name' =>
                'Retention Customer',

            'to_recipients' => [
                [
                    'address' =>
                        $mailbox->email_address,

                    'name' =>
                        $mailbox->display_name,
                ],
            ],

            'cc_recipients' =>
                [],

            'bcc_recipients' =>
                [],

            'reply_to_recipients' =>
                [],

            'subject' =>
                'Mail retention test',

            'text_body' =>
                'Mail retention test body.',

            'html_body' =>
                null,

            'headers' =>
                [],

            'metadata' => [
                'test' => true,
            ],

            'received_at' =>
                $createdAt,

            'processed_at' =>
                in_array(
                    $status,
                    [
                        EmailMessageStatus::Processed,
                        EmailMessageStatus::Sent,
                        EmailMessageStatus::Delivered,
                        EmailMessageStatus::Failed,
                        EmailMessageStatus::Rejected,
                        EmailMessageStatus::Bounced,
                        EmailMessageStatus::Complained,
                    ],
                    true
                )
                    ? $createdAt
                    : null,
        ]);

        $message->timestamps = false;

        $message->forceFill([
            'created_at' =>
                $createdAt,

            'updated_at' =>
                $createdAt,
        ])->save();

        $message->timestamps = true;

        return $message->fresh();
    }

    private function attachRawMessage(
        EmailMessage $message,
        string $contents,
    ): string {
        $path = sprintf(
            'testing/retention/raw/%d/%s.eml',
            $message->id,
            strtolower((string) Str::ulid())
        );

        Storage::disk('local')->put(
            $path,
            $contents
        );

        $message->forceFill([
            'raw_message_disk' =>
                'local',

            'raw_message_path' =>
                $path,

            'raw_message_size' =>
                strlen($contents),

            'raw_message_checksum' =>
                hash('sha256', $contents),
        ])->save();

        return $path;
    }

    private function createAttachment(
        EmailMessage $message,
        string $contents,
        EmailAttachmentScanStatus $scanStatus,
        mixed $createdAt,
    ): EmailAttachment {
        $token = strtolower(
            (string) Str::ulid()
        );

        $path = sprintf(
            'testing/retention/attachments/%d/%s.txt',
            $message->id,
            $token
        );

        Storage::disk('local')->put(
            $path,
            $contents
        );

        $attachment = EmailAttachment::query()->create([
            'email_message_id' =>
                $message->id,

            'position' =>
                0,

            'external_id' =>
                "retention-attachment-{$token}",

            'deduplication_key' =>
                hash(
                    'sha256',
                    $message->id
                    . '|'
                    . $token
                ),

            'file_name' =>
                'retention-attachment.txt',

            'mime_type' =>
                'text/plain',

            'size' =>
                strlen($contents),

            'disk' =>
                'local',

            'path' =>
                $path,

            'checksum_sha256' =>
                hash('sha256', $contents),

            'content_id' =>
                null,

            'is_inline' =>
                false,

            'scan_status' =>
                $scanStatus,

            'scan_started_at' =>
                null,

            'scan_attempts' =>
                0,

            'scanned_at' =>
                $scanStatus
                === EmailAttachmentScanStatus::Clean
                    ? $createdAt
                    : null,

            'scan_failure_code' =>
                null,

            'scan_failure_message' =>
                null,

            'quarantined_at' =>
                null,

            'scan_result' =>
                null,

            'metadata' => [
                'test' => true,
            ],
        ]);

        $attachment->timestamps = false;

        $attachment->forceFill([
            'created_at' =>
                $createdAt,

            'updated_at' =>
                $createdAt,
        ])->save();

        $attachment->timestamps = true;

        return $attachment->fresh();
    }
}
