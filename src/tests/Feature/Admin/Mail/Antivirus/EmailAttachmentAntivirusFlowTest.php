<?php

namespace Tests\Feature\Admin\Mail\Antivirus;

use App\Contracts\Admin\Mail\Antivirus\AttachmentScanDriver;
use App\Data\Admin\Mail\AttachmentScanResultData;
use App\Data\Admin\Mail\MailAttachmentData;
use App\Enums\Admin\Mail\EmailAttachmentScanStatus;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Exceptions\Admin\Mail\AttachmentScanException;
use App\Jobs\Admin\Mail\ScanEmailAttachmentJob;
use App\Jobs\Admin\Mail\SendOutgoingEmailJob;
use App\Models\Admin\Mail\EmailAttachment;
use App\Models\User\User;
use App\Services\Admin\Mail\Antivirus\AttachmentScanDispatcher;
use App\Services\Admin\Mail\Antivirus\AttachmentScanRecoveryService;
use App\Services\Admin\Mail\Antivirus\EmailAttachmentScanService;
use App\Services\Admin\Mail\Ticketing\TicketReplyEmailService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Fakes\Admin\Mail\FakeAttachmentScanDriver;
use Tests\Support\CreatesMailTestData;
use Tests\TestCase;

class EmailAttachmentAntivirusFlowTest extends TestCase
{
    use CreatesMailTestData;
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Queue::fake();
        Cache::flush();

        config()->set(
            'simpledesk-mail-antivirus.enabled',
            true
        );

        config()->set(
            'simpledesk-mail-antivirus.queue.name',
            'mail-antivirus'
        );

        config()->set(
            'simpledesk-mail-antivirus.queue.connection',
            null
        );

        config()->set(
            'simpledesk-mail-antivirus.queue.dispatch_lock_seconds',
            300
        );

        config()->set(
            'simpledesk-mail-antivirus.processing_lock_seconds',
            300
        );

        config()->set(
            'simpledesk-mail-antivirus.verify_checksums',
            true
        );

        config()->set(
            'simpledesk-mail-antivirus.recovery.grace_seconds',
            0
        );

        config()->set(
            'simpledesk-mail-antivirus.recovery.stuck_timeout_seconds',
            60
        );

        config()->set(
            'simpledesk-mail-antivirus.recovery.batch_size',
            100
        );

        config()->set(
            'simpledesk-mail.storage.disk',
            'local'
        );

        config()->set(
            'simpledesk-mail.storage.attachments_path',
            'mail/attachments'
        );

        config()->set(
            'simpledesk-mail.queues.outgoing',
            'mail-outgoing'
        );

        config()->set(
            'simpledesk-mail.outgoing.allowed_attachment_mime_types',
            [
                'text/plain',
            ]
        );

        config()->set(
            'simpledesk-mail-ticketing.outgoing_replies.enabled',
            true
        );
    }

    public function test_outgoing_email_waits_for_clean_scan_before_sending(): void
    {
        $driver = new FakeAttachmentScanDriver();

        $driver->pushResult(
            AttachmentScanResultData::clean(
                driver: $driver->name(),
                rawResponse: 'fake: OK',
                scannedBytes: strlen(
                    'clean contents'
                ),
            )
        );

        $this->app->instance(
            AttachmentScanDriver::class,
            $driver
        );

        $replyId = $this->createAgentReply();

        $message = app(
            TicketReplyEmailService::class
        )->queue(
            ticketReplyId: $replyId,
            dispatch: true,
            attachments: [
                new MailAttachmentData(
                    fileName: 'clean.txt',
                    mimeType: 'text/plain',
                    size: strlen(
                        'clean contents'
                    ),
                    content: 'clean contents',
                ),
            ],
        );

        $attachment = EmailAttachment::query()->sole();

        $this->assertSame(
            EmailMessageStatus::Preparing,
            $message->status
        );

        $this->assertSame(
            EmailAttachmentScanStatus::Pending,
            $attachment->scan_status
        );

        Queue::assertPushed(
            ScanEmailAttachmentJob::class,
            fn (
                ScanEmailAttachmentJob $job
            ): bool =>
                $job->emailAttachmentId
                === $attachment->id
                && $job->queue
                === 'mail-antivirus'
                && $job->afterCommit
                === true
        );

        Queue::assertNotPushed(
            SendOutgoingEmailJob::class
        );

        $job = new ScanEmailAttachmentJob(
            $attachment->id
        );

        $job->handle(
            scanner: app(
                EmailAttachmentScanService::class
            ),
            dispatcher: app(
                AttachmentScanDispatcher::class
            ),
        );

        $attachment->refresh();
        $message->refresh();

        $this->assertSame(
            EmailAttachmentScanStatus::Clean,
            $attachment->scan_status
        );

        $this->assertNotNull(
            $attachment->scanned_at
        );

        $this->assertNull(
            $attachment->quarantined_at
        );

        $this->assertSame(
            EmailMessageStatus::Queued,
            $message->status
        );

        Queue::assertPushed(
            SendOutgoingEmailJob::class,
            fn (
                SendOutgoingEmailJob $queuedJob
            ): bool =>
                $queuedJob->emailMessageId
                === $message->id
                && $queuedJob->queue
                === 'mail-outgoing'
        );

        $this->assertSame(
            'clean contents',
            $driver->scans[0]['contents']
        );
    }

    public function test_infected_attachment_is_quarantined_and_email_is_blocked(): void
    {
        $driver = new FakeAttachmentScanDriver();

        $driver->pushResult(
            AttachmentScanResultData::infected(
                signature: 'Eicar-Signature',
                driver: $driver->name(),
                rawResponse:
                'fake: Eicar-Signature FOUND',
                scannedBytes: strlen(
                    'infected contents'
                ),
            )
        );

        $this->app->instance(
            AttachmentScanDriver::class,
            $driver
        );

        $replyId = $this->createAgentReply();

        $message = app(
            TicketReplyEmailService::class
        )->queue(
            ticketReplyId: $replyId,
            dispatch: true,
            attachments: [
                new MailAttachmentData(
                    fileName: 'infected.txt',
                    mimeType: 'text/plain',
                    size: strlen(
                        'infected contents'
                    ),
                    content: 'infected contents',
                ),
            ],
        );

        $attachment = EmailAttachment::query()->sole();

        $job = new ScanEmailAttachmentJob(
            $attachment->id
        );

        $job->handle(
            scanner: app(
                EmailAttachmentScanService::class
            ),
            dispatcher: app(
                AttachmentScanDispatcher::class
            ),
        );

        $attachment->refresh();
        $message->refresh();

        $this->assertSame(
            EmailAttachmentScanStatus::Infected,
            $attachment->scan_status
        );

        $this->assertNotNull(
            $attachment->quarantined_at
        );

        $this->assertSame(
            'Eicar-Signature',
            $attachment->scan_result['signature']
        );

        $this->assertSame(
            EmailMessageStatus::Failed,
            $message->status
        );

        $this->assertSame(
            'attachment_infected',
            $message->failure_code
        );

        Queue::assertNotPushed(
            SendOutgoingEmailJob::class
        );
    }

    public function test_retryable_scan_error_keeps_attachment_pending(): void
    {
        $driver = new FakeAttachmentScanDriver();

        $driver->pushException(
            new AttachmentScanException(
                message:
                'ClamAV is temporarily unavailable.',
                errorCode:
                'clamav_connection_failed',
                retryable: true,
            )
        );

        $this->app->instance(
            AttachmentScanDriver::class,
            $driver
        );

        $replyId = $this->createAgentReply();

        $message = app(
            TicketReplyEmailService::class
        )->queue(
            ticketReplyId: $replyId,
            dispatch: true,
            attachments: [
                new MailAttachmentData(
                    fileName: 'retry.txt',
                    mimeType: 'text/plain',
                    size: strlen(
                        'retry contents'
                    ),
                    content: 'retry contents',
                ),
            ],
        );

        $attachment = EmailAttachment::query()->sole();

        $job = new ScanEmailAttachmentJob(
            $attachment->id
        );

        try {
            $job->handle(
                scanner: app(
                    EmailAttachmentScanService::class
                ),
                dispatcher: app(
                    AttachmentScanDispatcher::class
                ),
            );

            $this->fail(
                'Retryable antivirus failure was not rethrown.'
            );
        } catch (AttachmentScanException $exception) {
            $this->assertSame(
                'clamav_connection_failed',
                $exception->errorCode()
            );
        }

        $attachment->refresh();
        $message->refresh();

        $this->assertSame(
            EmailAttachmentScanStatus::Pending,
            $attachment->scan_status
        );

        $this->assertNull(
            $attachment->scan_started_at
        );

        $this->assertSame(
            'clamav_connection_failed',
            $attachment->scan_failure_code
        );

        $this->assertSame(
            EmailMessageStatus::Preparing,
            $message->status
        );

        Queue::assertNotPushed(
            SendOutgoingEmailJob::class
        );
    }

    public function test_recovery_resets_and_dispatches_stuck_attachment_scan(): void
    {
        $requester = User::factory()->create();

        [$attachment] = $this->createStoredAttachment(
            requester: $requester,
            scanStatus: EmailAttachmentScanStatus::Pending,
        );

        $attachment->forceFill([
            'scan_started_at' =>
                now()->subMinutes(10),
            'scan_attempts' => 1,
            'scanned_at' => null,
        ])->save();

        Cache::flush();

        $result = app(
            AttachmentScanRecoveryService::class
        )->recover(10);

        $this->assertSame(
            1,
            $result->stuckScansReset
        );

        $this->assertSame(
            0,
            $result->pendingScansDispatched
        );

        $attachment->refresh();

        $this->assertNull(
            $attachment->scan_started_at
        );

        Queue::assertPushed(
            ScanEmailAttachmentJob::class,
            fn (
                ScanEmailAttachmentJob $job
            ): bool =>
                $job->emailAttachmentId
                === $attachment->id
        );
    }

    private function createAgentReply(): int
    {
        $agent = $this->createSuperAdmin();

        $requester = User::factory()->create();

        $ticket = $this->createTicket(
            requester: $requester,
            agent: $agent,
        );

        $reply = $ticket
            ->replies()
            ->create([
                'user_id' => $agent->id,
                'message' =>
                    'Please review the attached file.',
                'is_internal' => false,
            ]);

        return $reply->id;
    }
}
