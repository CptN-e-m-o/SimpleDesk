<?php

namespace Tests\Feature\Admin\Mail\Outgoing;

use App\Contracts\Admin\Mail\OutgoingMailDriver;
use App\Data\Admin\Mail\MailAddressData;
use App\Data\Admin\Mail\OutgoingEmailMessageData;
use App\Data\Admin\Mail\OutgoingSendResultData;
use App\Enums\Admin\Mail\EmailMessageAttemptStatus;
use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Enums\Admin\Mail\MailboxChannelDirection;
use App\Enums\Admin\Mail\MailboxDriver;
use App\Enums\Admin\Mail\MailboxHealthStatus;
use App\Exceptions\Admin\Mail\AllMailChannelsFailedException;
use App\Exceptions\Admin\Mail\MailDriverException;
use App\Exceptions\Admin\Mail\NoAvailableMailChannelException;
use App\Exceptions\Admin\Mail\OutgoingMessageStateException;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\EmailMessageAttempt;
use App\Models\Admin\Mail\Mailbox;
use App\Models\Admin\Mail\MailboxChannel;
use App\Services\Admin\Mail\MailChannelHealthRecorder;
use App\Services\Admin\Mail\MailChannelSelector;
use App\Services\Admin\Mail\MailDriverRegistry;
use App\Services\Admin\Mail\OutgoingEmailMessageFactory;
use App\Services\Admin\Mail\OutgoingMailFailoverService;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class OutgoingMailFailoverServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_primary_channel_can_send_message_successfully(): void
    {
        $mailbox = $this->createMailbox();

        $primary = $this->createChannel(
            mailbox: $mailbox,
            name: 'Primary SMTP',
            primary: true,
            failoverOrder: 0,
        );

        $message = $this->createMessage(
            $mailbox
        );

        $payload = $this->payload(
            $message
        );

        $result = $this->sendResult();

        $driver = Mockery::mock(
            OutgoingMailDriver::class
        );

        $driver
            ->shouldReceive('send')
            ->once()
            ->withArgs(
                function (
                    MailboxChannel $channel,
                    OutgoingEmailMessageData $sentPayload,
                ) use (
                    $primary,
                    $payload
                ): bool {
                    return $channel->id === $primary->id
                        && $sentPayload === $payload;
                }
            )
            ->andReturn($result);

        $service = $this->service(
            channels: collect([
                $primary,
            ]),
            payload: $payload,
            driver: $driver,
            successChannel: $primary,
        );

        $actualResult = $service->send(
            $message
        );

        $this->assertSame(
            $result,
            $actualResult
        );

        $message->refresh();

        $this->assertSame(
            EmailMessageStatus::Sent,
            $message->status
        );

        $this->assertSame(
            $primary->id,
            $message->mailbox_channel_id
        );

        $this->assertSame(
            MailboxDriver::Smtp,
            $message->driver
        );

        $this->assertSame(
            'provider-message-123',
            $message->external_message_id
        );

        $this->assertSame(
            '<sent-message@example.test>',
            $message->internet_message_id
        );

        $this->assertNotNull(
            $message->sent_at
        );

        $this->assertNotNull(
            $message->processed_at
        );

        $this->assertNull(
            $message->failed_at
        );

        $this->assertDatabaseCount(
            'email_message_attempts',
            1
        );

        $attempt = EmailMessageAttempt::query()->sole();

        $this->assertSame(
            EmailMessageAttemptStatus::Succeeded,
            $attempt->status
        );

        $this->assertSame(
            $primary->id,
            $attempt->mailbox_channel_id
        );

        $this->assertSame(
            1,
            $attempt->attempt_number
        );

        $this->assertSame(
            'provider-message-123',
            $attempt->external_message_id
        );
    }

    public function test_failed_primary_uses_fallback_channel(): void
    {
        $mailbox = $this->createMailbox();

        $primary = $this->createChannel(
            mailbox: $mailbox,
            name: 'Primary SMTP',
            primary: true,
            failoverOrder: 0,
        );

        $fallback = $this->createChannel(
            mailbox: $mailbox,
            name: 'Fallback SMTP',
            primary: false,
            failoverOrder: 10,
        );

        $message = $this->createMessage(
            $mailbox
        );

        $payload = $this->payload(
            $message
        );

        $result = $this->sendResult();

        $primaryException =
            new MailDriverException(
                message: 'Primary SMTP connection failed.',

                driverErrorCode: 'smtp_connection_failed',

                retryable: true,

                failoverAllowed: true,

                affectsChannelHealth: true,

                context: [
                    'operation' => 'send',
                ],
            );

        $driver = Mockery::mock(
            OutgoingMailDriver::class
        );

        $driver
            ->shouldReceive('send')
            ->once()
            ->ordered()
            ->withArgs(
                fn (
                    MailboxChannel $channel,
                    OutgoingEmailMessageData $sentPayload,
                ): bool => $channel->id === $primary->id
                    && $sentPayload === $payload
            )
            ->andThrow(
                $primaryException
            );

        $driver
            ->shouldReceive('send')
            ->once()
            ->ordered()
            ->withArgs(
                fn (
                    MailboxChannel $channel,
                    OutgoingEmailMessageData $sentPayload,
                ): bool => $channel->id === $fallback->id
                    && $sentPayload === $payload
            )
            ->andReturn(
                $result
            );

        $drivers = Mockery::mock(
            MailDriverRegistry::class
        );

        $drivers
            ->shouldReceive('outgoing')
            ->twice()
            ->with(
                MailboxDriver::Smtp
            )
            ->andReturn(
                $driver
            );

        $selector = Mockery::mock(
            MailChannelSelector::class
        );

        $selector
            ->shouldReceive('outgoingCandidates')
            ->once()
            ->andReturn(
                collect([
                    $primary,
                    $fallback,
                ])
            );

        $factory = Mockery::mock(
            OutgoingEmailMessageFactory::class
        );

        $factory
            ->shouldReceive('make')
            ->once()
            ->andReturn(
                $payload
            );

        $health = Mockery::mock(
            MailChannelHealthRecorder::class
        );

        $health
            ->shouldReceive('markFailure')
            ->once()
            ->withArgs(
                fn (
                    MailboxChannel $channel,
                    ?string $errorCode,
                    string $errorMessage,
                ): bool => $channel->id === $primary->id
                    && $errorCode
                    === 'smtp_connection_failed'
                    && $errorMessage
                    === 'Primary SMTP connection failed.'
            );

        $health
            ->shouldReceive('markSuccess')
            ->once()
            ->withArgs(
                fn (
                    MailboxChannel $channel,
                    bool $hasActivity,
                ): bool => $channel->id === $fallback->id
                    && $hasActivity
            );

        $service = new OutgoingMailFailoverService(
            drivers: $drivers,
            selector: $selector,
            health: $health,
            messageFactory: $factory,
            sendingLockSeconds: 600,
        );

        $service->send(
            $message
        );

        $message->refresh();

        $this->assertSame(
            EmailMessageStatus::Sent,
            $message->status
        );

        $this->assertSame(
            $fallback->id,
            $message->mailbox_channel_id
        );

        $attempts = EmailMessageAttempt::query()
            ->orderBy('attempt_number')
            ->get();

        $this->assertCount(
            2,
            $attempts
        );

        $this->assertSame(
            EmailMessageAttemptStatus::Failed,
            $attempts[0]->status
        );

        $this->assertSame(
            'smtp_connection_failed',
            $attempts[0]->error_code
        );

        $this->assertTrue(
            $attempts[0]->retryable
        );

        $this->assertTrue(
            $attempts[0]->failover_allowed
        );

        $this->assertSame(
            EmailMessageAttemptStatus::Succeeded,
            $attempts[1]->status
        );

        $this->assertSame(
            $fallback->id,
            $attempts[1]->mailbox_channel_id
        );
    }

    public function test_non_failover_failure_records_actual_attempted_channel(): void
    {
        $mailbox = $this->createMailbox();

        $primary = $this->createChannel(
            mailbox: $mailbox,
            name: 'Primary SMTP',
            primary: true,
            failoverOrder: 0,
        );

        $fallback = $this->createChannel(
            mailbox: $mailbox,
            name: 'Unused Fallback SMTP',
            primary: false,
            failoverOrder: 10,
        );

        $message = $this->createMessage(
            $mailbox
        );

        $payload = $this->payload(
            $message
        );

        $driverException =
            new MailDriverException(
                message: 'Message was rejected permanently.',

                driverErrorCode: 'smtp_message_rejected',

                retryable: false,

                failoverAllowed: false,

                affectsChannelHealth: false,
            );

        $driver = Mockery::mock(
            OutgoingMailDriver::class
        );

        $driver
            ->shouldReceive('send')
            ->once()
            ->withArgs(
                fn (
                    MailboxChannel $channel,
                    OutgoingEmailMessageData $sentPayload,
                ): bool => $channel->id === $primary->id
                    && $sentPayload === $payload
            )
            ->andThrow(
                $driverException
            );

        $service = $this->service(
            channels: collect([
                $primary,
                $fallback,
            ]),
            payload: $payload,
            driver: $driver,
        );

        try {
            $service->send(
                $message
            );

            $this->fail(
                'Expected AllMailChannelsFailedException was not thrown.'
            );
        } catch (
            AllMailChannelsFailedException $exception
        ) {
            $this->assertNotSame(
                '',
                trim($exception->getMessage())
            );
        }

        $message->refresh();

        $this->assertSame(
            EmailMessageStatus::Failed,
            $message->status
        );

        $this->assertSame(
            $primary->id,
            $message->mailbox_channel_id
        );

        $this->assertNotSame(
            $fallback->id,
            $message->mailbox_channel_id
        );

        $this->assertSame(
            'all_channels_failed',
            $message->failure_code
        );

        $this->assertStringContainsString(
            'Message was rejected permanently.',
            $message->failure_message
        );

        $this->assertDatabaseCount(
            'email_message_attempts',
            1
        );

        $attempt = EmailMessageAttempt::query()->sole();

        $this->assertFalse(
            $attempt->retryable
        );

        $this->assertFalse(
            $attempt->failover_allowed
        );

        $this->assertSame(
            'smtp_message_rejected',
            $attempt->error_code
        );
    }

    public function test_no_available_channel_marks_message_failed(): void
    {
        $mailbox = $this->createMailbox();

        $message = $this->createMessage(
            $mailbox
        );

        $drivers = Mockery::mock(
            MailDriverRegistry::class
        );

        $drivers->shouldNotReceive(
            'outgoing'
        );

        $selector = Mockery::mock(
            MailChannelSelector::class
        );

        $selector
            ->shouldReceive('outgoingCandidates')
            ->once()
            ->andReturn(
                collect()
            );

        $health = Mockery::mock(
            MailChannelHealthRecorder::class
        );

        $health->shouldNotReceive(
            'markSuccess'
        );

        $health->shouldNotReceive(
            'markFailure'
        );

        $factory = Mockery::mock(
            OutgoingEmailMessageFactory::class
        );

        $factory->shouldNotReceive(
            'make'
        );

        $service = new OutgoingMailFailoverService(
            drivers: $drivers,
            selector: $selector,
            health: $health,
            messageFactory: $factory,
            sendingLockSeconds: 600,
        );

        try {
            $service->send(
                $message
            );

            $this->fail(
                'Expected NoAvailableMailChannelException was not thrown.'
            );
        } catch (
            NoAvailableMailChannelException $exception
        ) {
            $this->assertNotSame(
                '',
                trim($exception->getMessage())
            );
        }

        $message->refresh();

        $this->assertSame(
            EmailMessageStatus::Failed,
            $message->status
        );

        $this->assertSame(
            'no_available_channel',
            $message->failure_code
        );

        $this->assertNotNull(
            $message->failed_at
        );

        $this->assertDatabaseCount(
            'email_message_attempts',
            0
        );
    }

    public function test_recent_sending_lock_prevents_duplicate_processing(): void
    {
        $mailbox = $this->createMailbox();

        $message = $this->createMessage(
            mailbox: $mailbox,
            status: EmailMessageStatus::Sending,
            attributes: [
                'processing_started_at' => now()->subSeconds(30),
            ],
        );

        $drivers = Mockery::mock(
            MailDriverRegistry::class
        );

        $drivers->shouldNotReceive(
            'outgoing'
        );

        $selector = Mockery::mock(
            MailChannelSelector::class
        );

        $selector->shouldNotReceive(
            'outgoingCandidates'
        );

        $health = Mockery::mock(
            MailChannelHealthRecorder::class
        );

        $factory = Mockery::mock(
            OutgoingEmailMessageFactory::class
        );

        $factory->shouldNotReceive(
            'make'
        );

        $service = new OutgoingMailFailoverService(
            drivers: $drivers,
            selector: $selector,
            health: $health,
            messageFactory: $factory,
            sendingLockSeconds: 600,
        );

        $this->expectException(
            OutgoingMessageStateException::class
        );

        $service->send(
            $message
        );
    }

    private function service(
        Collection $channels,
        OutgoingEmailMessageData $payload,
        OutgoingMailDriver $driver,
        ?MailboxChannel $successChannel = null,
    ): OutgoingMailFailoverService {
        $drivers = Mockery::mock(
            MailDriverRegistry::class
        );

        $drivers
            ->shouldReceive('outgoing')
            ->atLeast()
            ->once()
            ->andReturn(
                $driver
            );

        $selector = Mockery::mock(
            MailChannelSelector::class
        );

        $selector
            ->shouldReceive('outgoingCandidates')
            ->once()
            ->andReturn(
                $channels
            );

        $factory = Mockery::mock(
            OutgoingEmailMessageFactory::class
        );

        $factory
            ->shouldReceive('make')
            ->once()
            ->andReturn(
                $payload
            );

        $health = Mockery::mock(
            MailChannelHealthRecorder::class
        );

        $health
            ->shouldReceive('markFailure')
            ->zeroOrMoreTimes();

        if ($successChannel !== null) {
            $health
                ->shouldReceive('markSuccess')
                ->once()
                ->withArgs(
                    fn (
                        MailboxChannel $channel,
                        bool $hasActivity,
                    ): bool => $channel->id
                        === $successChannel->id
                        && $hasActivity
                );
        } else {
            $health->shouldNotReceive(
                'markSuccess'
            );
        }

        return new OutgoingMailFailoverService(
            drivers: $drivers,
            selector: $selector,
            health: $health,
            messageFactory: $factory,
            sendingLockSeconds: 600,
        );
    }

    private function createMailbox(): Mailbox
    {
        $token = strtolower(
            (string) Str::ulid()
        );

        return Mailbox::query()->create([
            'name' => "Failover Mailbox {$token}",

            'email_address' => "failover-{$token}@example.test",

            'display_name' => 'Failover Mailbox',

            'department_id' => null,

            'is_active' => true,

            'is_default_outgoing' => false,

            'internal_notes' => null,
        ]);
    }

    private function createChannel(
        Mailbox $mailbox,
        string $name,
        bool $primary,
        int $failoverOrder,
    ): MailboxChannel {
        $channel = new MailboxChannel;

        $channel->forceFill([
            'mailbox_id' => $mailbox->id,

            'provider_connection_id' => null,

            'name' => $name,

            'direction' => MailboxChannelDirection::Outgoing,

            'driver' => MailboxDriver::Smtp,

            'is_primary' => $primary,

            'failover_order' => $failoverOrder,

            'is_enabled' => true,

            'configuration' => [],

            'health_status' => MailboxHealthStatus::Unknown,
        ])->save();

        return $channel->fresh();
    }

    private function createMessage(
        Mailbox $mailbox,
        EmailMessageStatus $status =
        EmailMessageStatus::Queued,
        array $attributes = [],
    ): EmailMessage {
        $token = strtolower(
            (string) Str::ulid()
        );

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

                    'idempotency_key' => "failover-test-{$token}",

                    'external_message_id' => null,

                    'internet_message_id' => "<queued-{$token}@example.test>",

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

                    'subject' => 'Outgoing failover test',

                    'text_body' => 'Outgoing failover test body.',

                    'html_body' => null,

                    'headers' => [],

                    'metadata' => [],

                    'queued_at' => now(),

                    'processing_started_at' => null,

                    'processed_at' => null,

                    'sent_at' => null,

                    'delivered_at' => null,

                    'failed_at' => null,

                    'failure_code' => null,

                    'failure_message' => null,
                ],
                $attributes
            )
        );
    }

    private function payload(
        EmailMessage $message
    ): OutgoingEmailMessageData {
        return new OutgoingEmailMessageData(
            idempotencyKey: $message->idempotency_key,

            from: new MailAddressData(
                address: $message->sender_address,

                name: $message->sender_name,
            ),

            to: [
                new MailAddressData(
                    address: $message->to_recipients[0]['address'],

                    name: $message->to_recipients[0]['name'],
                ),
            ],

            cc: [],

            bcc: [],

            replyTo: [],

            subject: $message->subject,

            textBody: $message->text_body,

            htmlBody: $message->html_body,

            headers: [],

            attachments: [],

            internetMessageId: $message->internet_message_id,

            inReplyToMessageId: null,

            references: [],

            metadata: [],
        );
    }

    private function sendResult(): OutgoingSendResultData
    {
        return new OutgoingSendResultData(
            externalMessageId: 'provider-message-123',

            internetMessageId: '<sent-message@example.test>',

            acceptedRecipients: [
                new MailAddressData(
                    address: 'customer@example.test',

                    name: 'Test Customer',
                ),
            ],

            rejectedRecipients: [],

            sentAt: new DateTimeImmutable,

            providerResponse: [
                'code' => 250,
                'message' => 'Accepted',
            ],

            metadata: [
                'transport' => 'smtp',
            ],
        );
    }
}
