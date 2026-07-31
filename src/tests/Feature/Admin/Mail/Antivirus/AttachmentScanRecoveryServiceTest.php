<?php

namespace Tests\Feature\Admin\Mail\Antivirus;

use App\Enums\Admin\Mail\EmailAttachmentScanStatus;
use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Models\Admin\Mail\EmailAttachment;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\Mailbox;
use App\Services\Admin\Mail\Antivirus\AttachmentScanDispatcher;
use App\Services\Admin\Mail\Antivirus\AttachmentScanRecoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class AttachmentScanRecoveryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'simpledesk-mail-antivirus.enabled',
            true
        );

        config()->set(
            'simpledesk-mail-antivirus.recovery.batch_size',
            100
        );

        config()->set(
            'simpledesk-mail-antivirus.recovery.stuck_timeout_seconds',
            300
        );

        config()->set(
            'simpledesk-mail-antivirus.recovery.grace_seconds',
            120
        );
    }

    public function test_recovery_does_nothing_when_antivirus_is_disabled(): void
    {
        config()->set(
            'simpledesk-mail-antivirus.enabled',
            false
        );

        $mailbox = $this->createMailbox();

        $message = $this->createMessage(
            $mailbox
        );

        $this->createAttachment(
            emailMessage: $message,
            scanStatus: EmailAttachmentScanStatus::Pending,
            attributes: [
                'scan_started_at' =>
                    now()->subMinutes(20),

                'created_at' =>
                    now()->subMinutes(30),

                'updated_at' =>
                    now()->subMinutes(20),
            ],
        );

        $dispatcher = Mockery::mock(
            AttachmentScanDispatcher::class
        );

        $dispatcher->shouldNotReceive(
            'releaseClaim'
        );

        $dispatcher->shouldNotReceive(
            'dispatch'
        );

        $result = $this
            ->service($dispatcher)
            ->recover();

        $this->assertSame(
            0,
            $result->stuckScansReset
        );

        $this->assertSame(
            0,
            $result->pendingScansDispatched
        );

        $this->assertSame(
            0,
            $result->totalActions()
        );
    }

    public function test_stuck_pending_scan_is_reset_and_dispatched(): void
    {
        /*
         * Большой grace period не позволяет этому же вложению
         * повторно попасть во вторую фазу recovery.
         */
        config()->set(
            'simpledesk-mail-antivirus.recovery.grace_seconds',
            3600
        );

        $mailbox = $this->createMailbox();

        $message = $this->createMessage(
            $mailbox
        );

        $attachment = $this->createAttachment(
            emailMessage: $message,
            scanStatus: EmailAttachmentScanStatus::Pending,
            attributes: [
                'scan_started_at' =>
                    now()->subMinutes(10),

                'created_at' =>
                    now()->subMinutes(20),

                'updated_at' =>
                    now()->subMinutes(10),

                'scan_failure_code' =>
                    'previous_failure',

                'scan_failure_message' =>
                    'Previous antivirus failure.',
            ],
        );

        $dispatcher = Mockery::mock(
            AttachmentScanDispatcher::class
        );

        $dispatcher
            ->shouldReceive('releaseClaim')
            ->once()
            ->with($attachment->id)
            ->andReturnNull();

        $dispatcher
            ->shouldReceive('dispatch')
            ->once()
            ->with($attachment->id)
            ->andReturnTrue();

        $result = $this
            ->service($dispatcher)
            ->recover();

        $this->assertSame(
            1,
            $result->stuckScansReset
        );

        $this->assertSame(
            0,
            $result->pendingScansDispatched
        );

        $this->assertSame(
            1,
            $result->totalActions()
        );

        $attachment->refresh();

        $this->assertSame(
            EmailAttachmentScanStatus::Pending,
            $attachment->scan_status
        );

        $this->assertNull(
            $attachment->scan_started_at
        );

        $this->assertSame(
            'stuck_scan_recovered',
            $attachment->scan_failure_code
        );

        $this->assertSame(
            'A stuck antivirus scan was reset by recovery.',
            $attachment->scan_failure_message
        );
    }

    public function test_old_not_scanned_attachment_is_converted_to_pending_and_dispatched(): void
    {
        $mailbox = $this->createMailbox();

        $message = $this->createMessage(
            $mailbox
        );

        $attachment = $this->createAttachment(
            emailMessage: $message,
            scanStatus: EmailAttachmentScanStatus::NotScanned,
            attributes: [
                'created_at' =>
                    now()->subMinutes(10),

                'updated_at' =>
                    now()->subMinutes(10),

                'scan_started_at' =>
                    null,

                'scanned_at' =>
                    now()->subMinutes(20),

                'scan_failure_code' =>
                    'legacy_failure',

                'scan_failure_message' =>
                    'Legacy failure information.',
            ],
        );

        $dispatcher = Mockery::mock(
            AttachmentScanDispatcher::class
        );

        $dispatcher->shouldNotReceive(
            'releaseClaim'
        );

        $dispatcher
            ->shouldReceive('dispatch')
            ->once()
            ->with($attachment->id)
            ->andReturnTrue();

        $result = $this
            ->service($dispatcher)
            ->recover();

        $this->assertSame(
            0,
            $result->stuckScansReset
        );

        $this->assertSame(
            1,
            $result->pendingScansDispatched
        );

        $this->assertSame(
            1,
            $result->totalActions()
        );

        $attachment->refresh();

        $this->assertSame(
            EmailAttachmentScanStatus::Pending,
            $attachment->scan_status
        );

        $this->assertNull(
            $attachment->scan_started_at
        );

        $this->assertNull(
            $attachment->scanned_at
        );

        $this->assertNull(
            $attachment->scan_failure_code
        );

        $this->assertNull(
            $attachment->scan_failure_message
        );
    }

    public function test_pending_dispatch_is_not_counted_when_dispatcher_does_not_acquire_claim(): void
    {
        $mailbox = $this->createMailbox();

        $message = $this->createMessage(
            $mailbox
        );

        $attachment = $this->createAttachment(
            emailMessage: $message,
            scanStatus: EmailAttachmentScanStatus::Pending,
            attributes: [
                'created_at' =>
                    now()->subMinutes(10),

                'updated_at' =>
                    now()->subMinutes(10),

                'scan_started_at' =>
                    null,
            ],
        );

        $dispatcher = Mockery::mock(
            AttachmentScanDispatcher::class
        );

        $dispatcher->shouldNotReceive(
            'releaseClaim'
        );

        $dispatcher
            ->shouldReceive('dispatch')
            ->once()
            ->with($attachment->id)
            ->andReturnFalse();

        $result = $this
            ->service($dispatcher)
            ->recover();

        $this->assertSame(
            0,
            $result->stuckScansReset
        );

        $this->assertSame(
            0,
            $result->pendingScansDispatched
        );

        $this->assertSame(
            0,
            $result->totalActions()
        );

        $attachment->refresh();

        $this->assertSame(
            EmailAttachmentScanStatus::Pending,
            $attachment->scan_status
        );

        $this->assertNull(
            $attachment->scan_started_at
        );
    }

    public function test_recovery_limit_is_applied_to_pending_scan_dispatch(): void
    {
        $mailbox = $this->createMailbox();

        $message = $this->createMessage(
            $mailbox
        );

        $firstAttachment = $this->createAttachment(
            emailMessage: $message,
            scanStatus: EmailAttachmentScanStatus::NotScanned,
            position: 0,
            attributes: [
                'created_at' =>
                    now()->subMinutes(20),

                'updated_at' =>
                    now()->subMinutes(20),
            ],
        );

        $secondAttachment = $this->createAttachment(
            emailMessage: $message,
            scanStatus: EmailAttachmentScanStatus::NotScanned,
            position: 1,
            attributes: [
                'created_at' =>
                    now()->subMinutes(20),

                'updated_at' =>
                    now()->subMinutes(20),
            ],
        );

        $dispatcher = Mockery::mock(
            AttachmentScanDispatcher::class
        );

        $dispatcher->shouldNotReceive(
            'releaseClaim'
        );

        $dispatcher
            ->shouldReceive('dispatch')
            ->once()
            ->with($firstAttachment->id)
            ->andReturnTrue();

        $dispatcher
            ->shouldNotReceive('dispatch')
            ->with($secondAttachment->id);

        $result = $this
            ->service($dispatcher)
            ->recover(1);

        $this->assertSame(
            0,
            $result->stuckScansReset
        );

        $this->assertSame(
            1,
            $result->pendingScansDispatched
        );

        $this->assertSame(
            1,
            $result->totalActions()
        );

        $firstAttachment->refresh();
        $secondAttachment->refresh();

        $this->assertSame(
            EmailAttachmentScanStatus::Pending,
            $firstAttachment->scan_status
        );

        $this->assertSame(
            EmailAttachmentScanStatus::NotScanned,
            $secondAttachment->scan_status
        );
    }

    private function service(
        AttachmentScanDispatcher $dispatcher
    ): AttachmentScanRecoveryService {
        return new AttachmentScanRecoveryService(
            dispatcher: $dispatcher
        );
    }

    private function createMailbox(): Mailbox
    {
        $token = strtolower(
            (string) Str::ulid()
        );

        return Mailbox::query()->create([
            'name' =>
                "Scan Recovery Mailbox {$token}",

            'email_address' =>
                "scan-recovery-{$token}@example.test",

            'display_name' =>
                'Scan Recovery Mailbox',

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
        Mailbox $mailbox
    ): EmailMessage {
        $token = strtolower(
            (string) Str::ulid()
        );

        return EmailMessage::query()->create([
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
                EmailMessageStatus::Received,

            'idempotency_key' =>
                "scan-recovery-message-{$token}",

            'external_message_id' =>
                "external-{$token}",

            'internet_message_id' =>
                "<scan-recovery-{$token}@example.test>",

            'in_reply_to_message_id' =>
                null,

            'reference_message_ids' =>
                [],

            'sender_address' =>
                'customer@example.test',

            'sender_name' =>
                'Test Customer',

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
                'Attachment scan recovery test',

            'text_body' =>
                'Attachment scan recovery test body.',

            'html_body' =>
                null,

            'headers' =>
                [],

            'metadata' => [
                'test' => true,
            ],

            'received_at' =>
                now(),
        ]);
    }

    private function createAttachment(
        EmailMessage $emailMessage,
        EmailAttachmentScanStatus $scanStatus,
        int $position = 0,
        array $attributes = [],
    ): EmailAttachment {
        $token = strtolower(
            (string) Str::ulid()
        );

        $attachment = new EmailAttachment();

        $attachment->forceFill(
            array_merge(
                [
                    'email_message_id' =>
                        $emailMessage->id,

                    'position' =>
                        $position,

                    'external_id' =>
                        "scan-recovery-external-{$token}",

                    'deduplication_key' =>
                        hash(
                            'sha256',
                            $emailMessage->id
                            . '|'
                            . $position
                            . '|'
                            . $token
                        ),

                    'file_name' =>
                        "scan-recovery-{$position}.txt",

                    'mime_type' =>
                        'text/plain',

                    'size' =>
                        20,

                    'disk' =>
                        'local',

                    'path' =>
                        "testing/scan-recovery/"
                        . $emailMessage->id
                        . "/{$token}.txt",

                    'checksum_sha256' =>
                        hash(
                            'sha256',
                            "scan-recovery-{$token}"
                        ),

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
                        null,

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
                ],
                $attributes
            )
        );

        $attachment->save();

        $timestampAttributes = [];

        foreach (
            [
                'created_at',
                'updated_at',
                'scan_started_at',
                'scanned_at',
            ] as $attribute
        ) {
            if (
                array_key_exists(
                    $attribute,
                    $attributes
                )
            ) {
                $timestampAttributes[$attribute] =
                    $attributes[$attribute];
            }
        }

        if ($timestampAttributes !== []) {
            $attachment->timestamps = false;

            $attachment
                ->forceFill($timestampAttributes)
                ->save();

            $attachment->timestamps = true;
        }

        return $attachment->fresh();
    }
}
