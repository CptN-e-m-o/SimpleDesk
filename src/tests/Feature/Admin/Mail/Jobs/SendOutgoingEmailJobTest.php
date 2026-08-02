<?php

namespace Tests\Feature\Admin\Mail\Jobs;

use App\Data\Admin\Mail\OutgoingSendResultData;
use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Jobs\Admin\Mail\SendOutgoingEmailJob;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\Mailbox;
use App\Services\Admin\Mail\OutgoingMailFailoverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Str;
use Mockery;
use ReflectionClass;
use RuntimeException;
use Tests\TestCase;

class SendOutgoingEmailJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_constructor_uses_configured_queue_settings(): void
    {
        config()->set([
            'simpledesk-mail.jobs.outgoing.tries' => 7,
            'simpledesk-mail.jobs.outgoing.timeout' => 180,
            'simpledesk-mail.jobs.outgoing.backoff' => [
                15,
                60,
                300,
            ],
        ]);

        $job = new SendOutgoingEmailJob(
            emailMessageId: 123
        );

        $this->assertSame(
            123,
            $job->emailMessageId
        );

        $this->assertSame(
            7,
            $job->tries
        );

        $this->assertSame(
            180,
            $job->timeout
        );

        $this->assertSame(
            [
                15,
                60,
                300,
            ],
            $job->backoff()
        );
    }

    public function test_job_uses_without_overlapping_middleware(): void
    {
        config()->set(
            'simpledesk-mail.jobs.outgoing.lock_seconds',
            900
        );

        $job = new SendOutgoingEmailJob(
            emailMessageId: 321
        );

        $middleware = $job->middleware();

        $this->assertCount(
            1,
            $middleware
        );

        $this->assertInstanceOf(
            WithoutOverlapping::class,
            $middleware[0]
        );
    }

    public function test_missing_email_message_is_ignored(): void
    {
        $sender = Mockery::mock(
            OutgoingMailFailoverService::class
        );

        $sender->shouldNotReceive(
            'send'
        );

        $job = new SendOutgoingEmailJob(
            emailMessageId: 999999
        );

        $job->handle(
            $sender
        );

        $this->assertDatabaseCount(
            'email_messages',
            0
        );
    }

    public function test_queued_email_message_is_passed_to_failover_service(): void
    {
        $message = $this->createOutgoingMessage(
            EmailMessageStatus::Queued
        );

        $sendResult = (
        new ReflectionClass(
            OutgoingSendResultData::class
        )
        )->newInstanceWithoutConstructor();

        $sender = Mockery::mock(
            OutgoingMailFailoverService::class
        );

        $sender
            ->shouldReceive('send')
            ->once()
            ->withArgs(
                function (
                    EmailMessage $argument
                ) use ($message): bool {
                    return $argument->id
                        === $message->id;
                }
            )
            ->andReturn(
                $sendResult
            );

        $job = new SendOutgoingEmailJob(
            emailMessageId: $message->id
        );

        $job->handle(
            $sender
        );

        $message->refresh();

        $this->assertSame(
            EmailMessageStatus::Queued,
            $message->status
        );
    }

    public function test_sent_and_delivered_messages_are_not_sent_again(): void
    {
        $sentMessage = $this->createOutgoingMessage(
            EmailMessageStatus::Sent
        );

        $deliveredMessage = $this->createOutgoingMessage(
            EmailMessageStatus::Delivered
        );

        $sender = Mockery::mock(
            OutgoingMailFailoverService::class
        );

        $sender->shouldNotReceive(
            'send'
        );

        $sentJob = new SendOutgoingEmailJob(
            emailMessageId: $sentMessage->id
        );

        $deliveredJob = new SendOutgoingEmailJob(
            emailMessageId: $deliveredMessage->id
        );

        $sentJob->handle(
            $sender
        );

        $deliveredJob->handle(
            $sender
        );

        $sentMessage->refresh();
        $deliveredMessage->refresh();

        $this->assertSame(
            EmailMessageStatus::Sent,
            $sentMessage->status
        );

        $this->assertSame(
            EmailMessageStatus::Delivered,
            $deliveredMessage->status
        );
    }

    public function test_failed_marks_message_as_failed_with_exception_details(): void
    {
        $message = $this->createOutgoingMessage(
            EmailMessageStatus::Sending,
            [
                'processing_started_at' => now()->subMinute(),
            ]
        );

        $job = new SendOutgoingEmailJob(
            emailMessageId: $message->id
        );

        $job->failed(
            new RuntimeException(
                'SMTP delivery permanently failed.'
            )
        );

        $message->refresh();

        $this->assertSame(
            EmailMessageStatus::Failed,
            $message->status
        );

        $this->assertNotNull(
            $message->failed_at
        );

        $this->assertSame(
            'queue_job_failed',
            $message->failure_code
        );

        $this->assertSame(
            'SMTP delivery permanently failed.',
            $message->failure_message
        );
    }

    public function test_failed_uses_default_message_when_exception_is_null(): void
    {
        $message = $this->createOutgoingMessage(
            EmailMessageStatus::Queued
        );

        $job = new SendOutgoingEmailJob(
            emailMessageId: $message->id
        );

        $job->failed(
            null
        );

        $message->refresh();

        $this->assertSame(
            EmailMessageStatus::Failed,
            $message->status
        );

        $this->assertNotNull(
            $message->failed_at
        );

        $this->assertSame(
            'queue_job_failed',
            $message->failure_code
        );

        $this->assertSame(
            'Outgoing email queue job failed.',
            $message->failure_message
        );
    }

    public function test_failed_does_not_modify_sent_or_delivered_messages(): void
    {
        $sentMessage = $this->createOutgoingMessage(
            EmailMessageStatus::Sent
        );

        $deliveredMessage = $this->createOutgoingMessage(
            EmailMessageStatus::Delivered
        );

        $exception = new RuntimeException(
            'Late queue failure.'
        );

        $sentJob = new SendOutgoingEmailJob(
            emailMessageId: $sentMessage->id
        );

        $deliveredJob = new SendOutgoingEmailJob(
            emailMessageId: $deliveredMessage->id
        );

        $sentJob->failed(
            $exception
        );

        $deliveredJob->failed(
            $exception
        );

        $sentMessage->refresh();
        $deliveredMessage->refresh();

        $this->assertSame(
            EmailMessageStatus::Sent,
            $sentMessage->status
        );

        $this->assertNull(
            $sentMessage->failed_at
        );

        $this->assertNull(
            $sentMessage->failure_code
        );

        $this->assertNull(
            $sentMessage->failure_message
        );

        $this->assertSame(
            EmailMessageStatus::Delivered,
            $deliveredMessage->status
        );

        $this->assertNull(
            $deliveredMessage->failed_at
        );

        $this->assertNull(
            $deliveredMessage->failure_code
        );

        $this->assertNull(
            $deliveredMessage->failure_message
        );
    }

    private function createOutgoingMessage(
        EmailMessageStatus $status,
        array $attributes = [],
    ): EmailMessage {
        $token = strtolower(
            (string) Str::ulid()
        );

        $mailbox = Mailbox::query()->create([
            'name' => "Outgoing Job Mailbox {$token}",

            'email_address' => "outgoing-job-{$token}@example.test",

            'display_name' => 'Outgoing Job Mailbox',

            'department_id' => null,

            'is_active' => true,

            'is_default_outgoing' => false,

            'internal_notes' => null,
        ]);

        return EmailMessage::query()->create(
            array_merge(
                [
                    'mailbox_id' => $mailbox->id,

                    'mailbox_channel_id' => null,

                    'ticket_id' => null,

                    'ticket_reply_id' => null,

                    'direction' => EmailMessageDirection::Outgoing,

                    'driver' => null,

                    'status' => $status,

                    'idempotency_key' => "outgoing-job-test-{$token}",

                    'external_message_id' => null,

                    'internet_message_id' => "<outgoing-job-{$token}@example.test>",

                    'in_reply_to_message_id' => null,

                    'reference_message_ids' => [],

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

                    'subject' => 'Outgoing job test',

                    'text_body' => 'Outgoing job test message.',

                    'html_body' => null,

                    'headers' => [],

                    'metadata' => [],

                    'queued_at' => $status === EmailMessageStatus::Queued
                            ? now()
                            : null,

                    'processing_started_at' => $status === EmailMessageStatus::Sending
                            ? now()
                            : null,

                    'sent_at' => in_array(
                        $status,
                        [
                            EmailMessageStatus::Sent,
                            EmailMessageStatus::Delivered,
                        ],
                        true
                    )
                            ? now()
                            : null,

                    'delivered_at' => $status === EmailMessageStatus::Delivered
                            ? now()
                            : null,

                    'failed_at' => null,

                    'failure_code' => null,

                    'failure_message' => null,
                ],
                $attributes
            )
        );
    }
}
