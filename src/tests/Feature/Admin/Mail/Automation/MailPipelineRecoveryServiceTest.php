<?php

namespace Tests\Feature\Admin\Mail\Automation;

use App\Enums\Admin\Mail\EmailAttachmentScanStatus;
use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Jobs\Admin\Mail\SendOutgoingEmailJob;
use App\Models\Admin\Mail\EmailMessage;
use App\Services\Admin\Mail\Automation\MailPipelineRecoveryService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesMailTestData;
use Tests\TestCase;

class MailPipelineRecoveryServiceTest extends TestCase
{
    use CreatesMailTestData;
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Storage::fake('local');
        Cache::flush();

        config()->set(
            'simpledesk-mail-automation.recovery.grace_seconds',
            0
        );

        config()->set(
            'simpledesk-mail-automation.recovery.dispatch_lock_seconds',
            300
        );

        config()->set(
            'simpledesk-mail-automation.recovery.outgoing_queue',
            'mail-outgoing'
        );

        config()->set(
            'simpledesk-mail-automation.recovery.queue_connection',
            null
        );
    }

    public function test_recovery_dispatches_queued_outgoing_message_with_attachment(): void
    {
        $mailbox =
            $this->createMailbox();

        $emailMessage =
            EmailMessage::query()->create([
                'mailbox_id' =>
                    $mailbox->id,

                'mailbox_channel_id' =>
                    null,

                'ticket_id' =>
                    null,

                'ticket_reply_id' =>
                    null,

                'direction' =>
                    EmailMessageDirection::Outgoing,

                'driver' =>
                    null,

                'status' =>
                    EmailMessageStatus::Queued,

                'idempotency_key' =>
                    'recovery-outgoing-with-attachment',

                'external_message_id' =>
                    null,

                'internet_message_id' =>
                    'recovery-message@simpledesk.test',

                'in_reply_to_message_id' =>
                    null,

                'reference_message_ids' =>
                    [],

                'sender_address' =>
                    $mailbox->email_address,

                'sender_name' =>
                    $mailbox->display_name,

                'to_recipients' => [
                    [
                        'address' =>
                            'customer@example.test',

                        'name' =>
                            'Customer',
                    ],
                ],

                'cc_recipients' => [],
                'bcc_recipients' => [],
                'reply_to_recipients' => [],

                'subject' =>
                    'Recovery test',

                'text_body' =>
                    'Message body',

                'html_body' =>
                    '<p>Message body</p>',

                'headers' => [],
                'metadata' => [],

                'queued_at' =>
                    now()->subMinute(),
            ]);

        $contents =
            'Recovery attachment contents.';

        $path = implode(
            '/',
            [
                'mail',
                'attachments',
                (string) $emailMessage->id,
                'recovery.txt',
            ]
        );

        Storage::disk('local')->put(
            $path,
            $contents
        );

        $attachment = $emailMessage
            ->attachments()
            ->create([
                'position' => 0,

                'external_id' => null,

                'deduplication_key' => hash(
                    'sha256',
                    'recovery:'
                    . $emailMessage->id
                ),

                'file_name' =>
                    'recovery.txt',

                'mime_type' =>
                    'text/plain',

                'size' =>
                    strlen($contents),

                'disk' =>
                    'local',

                'path' =>
                    $path,

                'checksum_sha256' => hash(
                    'sha256',
                    $contents
                ),

                'content_id' => null,
                'is_inline' => false,

                'scan_status' =>
                    EmailAttachmentScanStatus::Clean,

                'scanned_at' =>
                    now(),

                'quarantined_at' =>
                    null,

                'scan_result' =>
                    null,

                'metadata' =>
                    [],
            ]);

        $result = app(
            MailPipelineRecoveryService::class
        )->recover(
            10
        );

        $this->assertSame(
            0,
            $result->incomingStuckReset
        );

        $this->assertSame(
            0,
            $result->incomingReceivedDispatched
        );

        $this->assertSame(
            0,
            $result->outgoingStuckReset
        );

        $this->assertSame(
            1,
            $result->outgoingQueuedDispatched
        );

        $this->assertSame(
            1,
            $result->totalActions()
        );

        Queue::assertPushed(
            SendOutgoingEmailJob::class,
            function (
                SendOutgoingEmailJob $job
            ) use ($emailMessage): bool {
                return $job->emailMessageId
                    === $emailMessage->id
                    && $job->queue
                    === 'mail-outgoing'
                    && $job->afterCommit
                    === true;
            }
        );

        $this->assertDatabaseHas(
            'email_attachments',
            [
                'id' =>
                    $attachment->id,

                'email_message_id' =>
                    $emailMessage->id,

                'path' =>
                    $path,
            ]
        );

        Storage::disk('local')->assertExists(
            $path
        );

        $this->assertSame(
            $contents,
            Storage::disk('local')->get(
                $path
            )
        );
    }
}
