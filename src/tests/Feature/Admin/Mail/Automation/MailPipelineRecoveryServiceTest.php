<?php

namespace Tests\Feature\Admin\Mail\Automation;

use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Jobs\Admin\Mail\ProcessInboundEmailJob;
use App\Jobs\Admin\Mail\SendOutgoingEmailJob;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\Mailbox;
use App\Services\Admin\Mail\Automation\MailPipelineRecoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class MailPipelineRecoveryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        config()->set(
            'cache.default',
            'array'
        );

        Cache::flush();

        config()->set(
            'simpledesk-mail-automation.recovery.batch_size',
            100
        );

        config()->set(
            'simpledesk-mail-automation.recovery.incoming_processing_timeout_seconds',
            300
        );

        config()->set(
            'simpledesk-mail-automation.recovery.outgoing_sending_timeout_seconds',
            300
        );

        config()->set(
            'simpledesk-mail-automation.recovery.grace_seconds',
            120
        );

        config()->set(
            'simpledesk-mail-automation.recovery.dispatch_lock_seconds',
            300
        );

        config()->set(
            'simpledesk-mail-automation.recovery.queue_connection',
            null
        );

        config()->set(
            'simpledesk-mail-automation.recovery.incoming_queue',
            'mail-incoming'
        );

        config()->set(
            'simpledesk-mail-automation.recovery.outgoing_queue',
            'mail-outgoing'
        );
    }

    public function test_stuck_incoming_message_is_reset_and_dispatched(): void
    {
        $mailbox = $this->createMailbox();

        $message = $this->createMessage(
            mailbox: $mailbox,
            direction: EmailMessageDirection::Incoming,
            status: EmailMessageStatus::Processing,
            attributes: [
                'processing_started_at' => now()->subMinutes(10),

                'failure_code' => 'previous_processing_failure',

                'failure_message' => 'Previous inbound processing failure.',

                'failed_at' => now()->subMinutes(9),

                'metadata' => [
                    'source' => 'test',
                ],
            ],
        );

        $result = $this
            ->service()
            ->recover();

        $this->assertSame(
            1,
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
            0,
            $result->outgoingQueuedDispatched
        );

        $this->assertSame(
            1,
            $result->totalActions()
        );

        $message->refresh();

        $this->assertSame(
            EmailMessageStatus::Received,
            $message->status
        );

        $this->assertNull(
            $message->processing_started_at
        );

        $this->assertNull(
            $message->failed_at
        );

        $this->assertNull(
            $message->failure_code
        );

        $this->assertNull(
            $message->failure_message
        );

        $this->assertSame(
            'incoming_processing_reset',
            data_get(
                $message->metadata,
                'recovery.last_action'
            )
        );

        $this->assertNotNull(
            data_get(
                $message->metadata,
                'recovery.last_recovered_at'
            )
        );

        $this->assertSame(
            'incoming_processing_reset',
            data_get(
                $message->metadata,
                'recovery.events.0.action'
            )
        );

        Queue::assertPushed(
            ProcessInboundEmailJob::class,
            function (
                ProcessInboundEmailJob $job
            ) use ($message): bool {
                return $job->emailMessageId
                    === $message->id
                    && $job->queue
                    === 'mail-incoming';
            }
        );

        Queue::assertPushedTimes(
            ProcessInboundEmailJob::class,
            1
        );

        Queue::assertNotPushed(
            SendOutgoingEmailJob::class
        );
    }

    public function test_stuck_outgoing_message_is_reset_and_dispatched(): void
    {
        $mailbox = $this->createMailbox();

        $message = $this->createMessage(
            mailbox: $mailbox,
            direction: EmailMessageDirection::Outgoing,
            status: EmailMessageStatus::Sending,
            attributes: [
                'processing_started_at' => now()->subMinutes(10),

                'queued_at' => now()->subMinutes(15),

                'failure_code' => 'previous_sending_failure',

                'failure_message' => 'Previous outgoing sending failure.',

                'failed_at' => now()->subMinutes(9),

                'metadata' => [
                    'source' => 'test',
                ],
            ],
        );

        $result = $this
            ->service()
            ->recover();

        $this->assertSame(
            0,
            $result->incomingStuckReset
        );

        $this->assertSame(
            0,
            $result->incomingReceivedDispatched
        );

        $this->assertSame(
            1,
            $result->outgoingStuckReset
        );

        $this->assertSame(
            0,
            $result->outgoingQueuedDispatched
        );

        $this->assertSame(
            1,
            $result->totalActions()
        );

        $message->refresh();

        $this->assertSame(
            EmailMessageStatus::Queued,
            $message->status
        );

        $this->assertNull(
            $message->processing_started_at
        );

        $this->assertNull(
            $message->failed_at
        );

        $this->assertNull(
            $message->failure_code
        );

        $this->assertNull(
            $message->failure_message
        );

        $this->assertSame(
            'outgoing_sending_reset',
            data_get(
                $message->metadata,
                'recovery.last_action'
            )
        );

        $this->assertNotNull(
            data_get(
                $message->metadata,
                'recovery.last_recovered_at'
            )
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

        Queue::assertPushedTimes(
            SendOutgoingEmailJob::class,
            1
        );

        Queue::assertNotPushed(
            ProcessInboundEmailJob::class
        );
    }

    public function test_old_received_and_queued_messages_are_dispatched_without_status_change(): void
    {
        $mailbox = $this->createMailbox();

        $incoming = $this->createMessage(
            mailbox: $mailbox,
            direction: EmailMessageDirection::Incoming,
            status: EmailMessageStatus::Received,
            attributes: [
                'created_at' => now()->subMinutes(10),

                'updated_at' => now()->subMinutes(10),

                'received_at' => now()->subMinutes(10),
            ],
        );

        $outgoing = $this->createMessage(
            mailbox: $mailbox,
            direction: EmailMessageDirection::Outgoing,
            status: EmailMessageStatus::Queued,
            attributes: [
                'created_at' => now()->subMinutes(10),

                'updated_at' => now()->subMinutes(10),

                'queued_at' => now()->subMinutes(10),
            ],
        );

        $result = $this
            ->service()
            ->recover();

        $this->assertSame(
            0,
            $result->incomingStuckReset
        );

        $this->assertSame(
            1,
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
            2,
            $result->totalActions()
        );

        $incoming->refresh();
        $outgoing->refresh();

        $this->assertSame(
            EmailMessageStatus::Received,
            $incoming->status
        );

        $this->assertSame(
            EmailMessageStatus::Queued,
            $outgoing->status
        );

        $this->assertNull(
            data_get(
                $incoming->metadata,
                'recovery.last_action'
            )
        );

        $this->assertNull(
            data_get(
                $outgoing->metadata,
                'recovery.last_action'
            )
        );

        Queue::assertPushed(
            ProcessInboundEmailJob::class,
            fn (
                ProcessInboundEmailJob $job
            ): bool => $job->emailMessageId
                === $incoming->id
                && $job->queue
                === 'mail-incoming'
        );

        Queue::assertPushed(
            SendOutgoingEmailJob::class,
            fn (
                SendOutgoingEmailJob $job
            ): bool => $job->emailMessageId
                === $outgoing->id
                && $job->queue
                === 'mail-outgoing'
        );
    }

    public function test_fresh_messages_are_not_recovered_or_dispatched(): void
    {
        $mailbox = $this->createMailbox();

        $this->createMessage(
            mailbox: $mailbox,
            direction: EmailMessageDirection::Incoming,
            status: EmailMessageStatus::Processing,
            attributes: [
                'processing_started_at' => now()->subSeconds(30),
            ],
        );

        $this->createMessage(
            mailbox: $mailbox,
            direction: EmailMessageDirection::Incoming,
            status: EmailMessageStatus::Received,
            attributes: [
                'created_at' => now()->subSeconds(30),

                'updated_at' => now()->subSeconds(30),

                'received_at' => now()->subSeconds(30),
            ],
        );

        $this->createMessage(
            mailbox: $mailbox,
            direction: EmailMessageDirection::Outgoing,
            status: EmailMessageStatus::Sending,
            attributes: [
                'processing_started_at' => now()->subSeconds(30),

                'queued_at' => now()->subMinute(),
            ],
        );

        $this->createMessage(
            mailbox: $mailbox,
            direction: EmailMessageDirection::Outgoing,
            status: EmailMessageStatus::Queued,
            attributes: [
                'created_at' => now()->subSeconds(30),

                'updated_at' => now()->subSeconds(30),

                'queued_at' => now()->subSeconds(30),
            ],
        );

        $result = $this
            ->service()
            ->recover();

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
            0,
            $result->outgoingQueuedDispatched
        );

        $this->assertSame(
            0,
            $result->totalActions()
        );

        Queue::assertNothingPushed();
    }

    public function test_repeated_recovery_does_not_dispatch_same_messages_twice_while_lock_exists(): void
    {
        $mailbox = $this->createMailbox();

        $incoming = $this->createMessage(
            mailbox: $mailbox,
            direction: EmailMessageDirection::Incoming,
            status: EmailMessageStatus::Received,
            attributes: [
                'created_at' => now()->subMinutes(10),

                'updated_at' => now()->subMinutes(10),

                'received_at' => now()->subMinutes(10),
            ],
        );

        $outgoing = $this->createMessage(
            mailbox: $mailbox,
            direction: EmailMessageDirection::Outgoing,
            status: EmailMessageStatus::Queued,
            attributes: [
                'created_at' => now()->subMinutes(10),

                'updated_at' => now()->subMinutes(10),

                'queued_at' => now()->subMinutes(10),
            ],
        );

        $service = $this->service();

        $firstResult = $service->recover();
        $secondResult = $service->recover();

        $this->assertSame(
            1,
            $firstResult->incomingReceivedDispatched
        );

        $this->assertSame(
            1,
            $firstResult->outgoingQueuedDispatched
        );

        $this->assertSame(
            2,
            $firstResult->totalActions()
        );

        $this->assertSame(
            0,
            $secondResult->incomingStuckReset
        );

        $this->assertSame(
            0,
            $secondResult->incomingReceivedDispatched
        );

        $this->assertSame(
            0,
            $secondResult->outgoingStuckReset
        );

        $this->assertSame(
            0,
            $secondResult->outgoingQueuedDispatched
        );

        $this->assertSame(
            0,
            $secondResult->totalActions()
        );

        Queue::assertPushed(
            ProcessInboundEmailJob::class,
            fn (
                ProcessInboundEmailJob $job
            ): bool => $job->emailMessageId
                === $incoming->id
        );

        Queue::assertPushed(
            SendOutgoingEmailJob::class,
            fn (
                SendOutgoingEmailJob $job
            ): bool => $job->emailMessageId
                === $outgoing->id
        );

        Queue::assertPushedTimes(
            ProcessInboundEmailJob::class,
            1
        );

        Queue::assertPushedTimes(
            SendOutgoingEmailJob::class,
            1
        );
    }

    private function service(): MailPipelineRecoveryService
    {
        return new MailPipelineRecoveryService;
    }

    private function createMailbox(): Mailbox
    {
        $token = strtolower(
            (string) Str::ulid()
        );

        return Mailbox::query()->create([
            'name' => "Recovery Mailbox {$token}",

            'email_address' => "recovery-{$token}@example.test",

            'display_name' => 'Recovery Mailbox',

            'department_id' => null,

            'is_active' => true,

            'is_default_outgoing' => false,

            'internal_notes' => null,
        ]);
    }

    private function createMessage(
        Mailbox $mailbox,
        EmailMessageDirection $direction,
        EmailMessageStatus $status,
        array $attributes = [],
    ): EmailMessage {
        $token = strtolower(
            (string) Str::ulid()
        );

        $message = new EmailMessage;

        $message->forceFill(
            array_merge(
                [
                    'mailbox_id' => $mailbox->id,

                    'mailbox_channel_id' => null,

                    'ticket_id' => null,

                    'ticket_reply_id' => null,

                    'direction' => $direction,

                    'driver' => null,

                    'status' => $status,

                    'idempotency_key' => "recovery-test-{$token}",

                    'external_message_id' => null,

                    'internet_message_id' => "<recovery-{$token}@example.test>",

                    'in_reply_to_message_id' => null,

                    'reference_message_ids' => [],

                    'sender_address' => $direction
                        === EmailMessageDirection::Incoming
                            ? 'customer@example.test'
                            : $mailbox->email_address,

                    'sender_name' => $direction
                        === EmailMessageDirection::Incoming
                            ? 'Test Customer'
                            : $mailbox->display_name,

                    'to_recipients' => [
                        [
                            'address' => $direction
                                === EmailMessageDirection::Incoming
                                    ? $mailbox->email_address
                                    : 'customer@example.test',

                            'name' => $direction
                                === EmailMessageDirection::Incoming
                                    ? $mailbox->display_name
                                    : 'Test Customer',
                        ],
                    ],

                    'cc_recipients' => [],

                    'bcc_recipients' => [],

                    'reply_to_recipients' => [],

                    'subject' => 'Mail recovery test',

                    'text_body' => 'Mail recovery test body.',

                    'html_body' => null,

                    'headers' => [],

                    'metadata' => [
                        'test' => true,
                    ],

                    'received_at' => $direction
                        === EmailMessageDirection::Incoming
                            ? now()
                            : null,

                    'queued_at' => $direction
                        === EmailMessageDirection::Outgoing
                            ? now()
                            : null,

                    'processing_started_at' => in_array(
                        $status,
                        [
                            EmailMessageStatus::Processing,
                            EmailMessageStatus::Sending,
                        ],
                        true
                    )
                            ? now()
                            : null,

                    'processed_at' => null,

                    'sent_at' => null,

                    'failed_at' => null,

                    'failure_code' => null,

                    'failure_message' => null,
                ],
                $attributes
            )
        );

        $message->save();

        $timestampAttributes = [];

        foreach (
            [
                'created_at',
                'updated_at',
                'received_at',
                'queued_at',
                'processing_started_at',
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
            $message->timestamps = false;

            $message->forceFill(
                $timestampAttributes
            )->save();

            $message->timestamps = true;
        }

        return $message->fresh();
    }
}
