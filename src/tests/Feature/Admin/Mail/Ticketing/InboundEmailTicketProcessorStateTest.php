<?php

namespace Tests\Feature\Admin\Mail\Ticketing;

use App\Data\Admin\Mail\InboundEmailDecisionData;
use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Enums\Admin\Mail\InboundEmailClassification;
use App\Enums\Admin\Mail\MailboxDriver;
use App\Exceptions\Admin\Mail\InboundEmailAlreadyProcessingException;
use App\Exceptions\Admin\Mail\InboundEmailTicketingException;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\Mailbox;
use App\Services\Admin\Mail\ReplyParsing\InboundEmailClassifier;
use App\Services\Admin\Mail\ReplyParsing\InboundEmailReplyParser;
use App\Services\Admin\Mail\Ticketing\InboundEmailRequesterResolver;
use App\Services\Admin\Mail\Ticketing\InboundEmailThreadMatcher;
use App\Services\Admin\Mail\Ticketing\InboundEmailTicketProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class InboundEmailTicketProcessorStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_classifier_can_ignore_message_without_creating_ticket(): void
    {
        $message = $this->createMessage(
            direction: EmailMessageDirection::Incoming,
            status: EmailMessageStatus::Received,
        );

        $classifier = Mockery::mock(
            InboundEmailClassifier::class
        );

        $classifier
            ->shouldReceive('classify')
            ->once()
            ->andReturn(
                new InboundEmailDecisionData(
                    shouldProcess: false,
                    classification: InboundEmailClassification::Human,
                    reason: 'Message ignored by test classification.',
                )
            );

        $processor = $this->makeProcessor(
            classifier: $classifier
        );

        $result = $processor->process(
            $message->id
        );

        $message->refresh();

        $this->assertSame(
            EmailMessageStatus::Processed,
            $message->status
        );

        $this->assertSame(
            EmailMessageStatus::Processed,
            $result->status
        );

        $this->assertNull(
            $message->ticket_id
        );

        $this->assertNull(
            $message->ticket_reply_id
        );

        $this->assertNull(
            $message->processing_started_at
        );

        $this->assertNotNull(
            $message->processed_at
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

        $ticketing = $message->metadata['ticketing'] ?? [];

        $this->assertSame(
            'ignored',
            $ticketing['action'] ?? null
        );

        $this->assertSame(
            InboundEmailClassification::Human->value,
            $ticketing['classification'] ?? null
        );

        $this->assertSame(
            'Message ignored by test classification.',
            $ticketing['classification_reason'] ?? null
        );

        $this->assertNull(
            $ticketing['ticket_id'] ?? null
        );

        $this->assertNull(
            $ticketing['ticket_reply_id'] ?? null
        );

        $this->assertNull(
            $ticketing['requester_id'] ?? null
        );

        $this->assertNotNull(
            $ticketing['processed_at'] ?? null
        );

        $this->assertDatabaseCount(
            'tickets',
            0
        );

        $this->assertDatabaseCount(
            'ticket_replies',
            0
        );
    }

    public function test_already_processed_message_is_returned_without_reprocessing(): void
    {
        $message = $this->createMessage(
            direction: EmailMessageDirection::Incoming,
            status: EmailMessageStatus::Processed,
            attributes: [
                'processed_at' => now()->subMinute(),

                'metadata' => [
                    'ticketing' => [
                        'action' => 'ignored',
                        'classification' => 'test',
                    ],
                ],
            ],
        );

        $processor = $this->makeProcessor();

        $result = $processor->process(
            $message->id
        );

        $message->refresh();

        $this->assertSame(
            $message->id,
            $result->id
        );

        $this->assertSame(
            EmailMessageStatus::Processed,
            $message->status
        );

        $this->assertSame(
            'ignored',
            $message->metadata['ticketing']['action'] ?? null
        );

        $this->assertSame(
            'test',
            $message->metadata['ticketing']['classification'] ?? null
        );

        $this->assertDatabaseCount(
            'tickets',
            0
        );

        $this->assertDatabaseCount(
            'ticket_replies',
            0
        );
    }

    public function test_recent_processing_lock_prevents_parallel_processing(): void
    {
        config([
            'simpledesk-mail-ticketing.processing_lock_seconds' => 600,
        ]);

        $message = $this->createMessage(
            direction: EmailMessageDirection::Incoming,
            status: EmailMessageStatus::Processing,
            attributes: [
                'processing_started_at' => now()->subSeconds(30),
            ],
        );

        $processor = $this->makeProcessor();

        try {
            $processor->process(
                $message->id
            );

            $this->fail(
                'Expected InboundEmailAlreadyProcessingException was not thrown.'
            );
        } catch (
            InboundEmailAlreadyProcessingException $exception
        ) {
            $this->assertInstanceOf(
                InboundEmailAlreadyProcessingException::class,
                $exception
            );

            $this->assertNotSame(
                '',
                trim($exception->getMessage())
            );
        }

        $message->refresh();

        $this->assertSame(
            EmailMessageStatus::Processing,
            $message->status
        );

        $this->assertNotNull(
            $message->processing_started_at
        );

        $this->assertNull(
            $message->processed_at
        );

        $this->assertNull(
            $message->failed_at
        );

        $this->assertNull(
            $message->failure_code
        );

        $this->assertNull(
            $message->ticket_id
        );

        $this->assertNull(
            $message->ticket_reply_id
        );

        $this->assertDatabaseCount(
            'tickets',
            0
        );

        $this->assertDatabaseCount(
            'ticket_replies',
            0
        );
    }

    public function test_outgoing_message_cannot_be_processed_as_incoming_ticket(): void
    {
        $message = $this->createMessage(
            direction: EmailMessageDirection::Outgoing,
            status: EmailMessageStatus::Received,
        );

        $processor = $this->makeProcessor();

        $exception = $this->captureTicketingException(
            fn () => $processor->process(
                $message->id
            )
        );

        $this->assertFalse(
            $exception->retryable()
        );

        $this->assertSame(
            'email_message_not_incoming',
            $exception->errorCode()
        );

        $message->refresh();

        $this->assertSame(
            EmailMessageStatus::Failed,
            $message->status
        );

        $this->assertNotNull(
            $message->failed_at
        );

        $this->assertNull(
            $message->processing_started_at
        );

        $this->assertSame(
            'inbound_ticket_processing_failed',
            $message->failure_code
        );

        $this->assertSame(
            'email_message_not_incoming',
            $message->metadata['ticketing_failure']['error_code'] ?? null
        );

        $this->assertSame(
            InboundEmailTicketingException::class,
            $message->metadata['ticketing_failure']['exception'] ?? null
        );

        $this->assertNull(
            $message->ticket_id
        );

        $this->assertNull(
            $message->ticket_reply_id
        );

        $this->assertDatabaseCount(
            'tickets',
            0
        );

        $this->assertDatabaseCount(
            'ticket_replies',
            0
        );
    }

    private function makeProcessor(
        ?InboundEmailClassifier $classifier = null
    ): InboundEmailTicketProcessor {
        if ($classifier === null) {
            $classifier = Mockery::mock(
                InboundEmailClassifier::class
            );

            $classifier->shouldNotReceive(
                'classify'
            );
        }

        $replyParser = Mockery::mock(
            InboundEmailReplyParser::class
        );

        $replyParser->shouldNotReceive(
            'parse'
        );

        $requesters = Mockery::mock(
            InboundEmailRequesterResolver::class
        );

        $requesters->shouldNotReceive(
            'resolve'
        );

        $threads = Mockery::mock(
            InboundEmailThreadMatcher::class
        );

        $threads->shouldNotReceive(
            'match'
        );

        $this->app->instance(
            InboundEmailClassifier::class,
            $classifier
        );

        $this->app->instance(
            InboundEmailReplyParser::class,
            $replyParser
        );

        $this->app->instance(
            InboundEmailRequesterResolver::class,
            $requesters
        );

        $this->app->instance(
            InboundEmailThreadMatcher::class,
            $threads
        );

        return $this->app->make(
            InboundEmailTicketProcessor::class
        );
    }

    private function createMessage(
        EmailMessageDirection $direction,
        EmailMessageStatus $status,
        array $attributes = [],
    ): EmailMessage {
        $token = strtolower(
            (string) Str::ulid()
        );

        $mailbox = Mailbox::query()->create([
            'name' => "Test Mailbox {$token}",

            'email_address' => "support-{$token}@example.test",

            'display_name' => 'Test Support',

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

                    'direction' => $direction,

                    'driver' => MailboxDriver::Imap,

                    'status' => $status,

                    'idempotency_key' => "state-test-{$token}",

                    'external_message_id' => "external-{$token}",

                    'internet_message_id' => "<{$token}@example.test>",

                    'in_reply_to_message_id' => null,

                    'reference_message_ids' => [],

                    'sender_address' => "customer-{$token}@example.test",

                    'sender_name' => 'Test Customer',

                    'to_recipients' => [
                        [
                            'address' => $mailbox->email_address,

                            'name' => $mailbox->display_name,
                        ],
                    ],

                    'cc_recipients' => [],

                    'bcc_recipients' => [],

                    'reply_to_recipients' => [],

                    'subject' => 'Test mail processor state',

                    'text_body' => 'Test message body.',

                    'html_body' => null,

                    'headers' => [],

                    'metadata' => [],

                    'received_at' => now(),

                    'processing_started_at' => null,

                    'processed_at' => null,

                    'failed_at' => null,

                    'failure_code' => null,

                    'failure_message' => null,
                ],
                $attributes
            )
        );
    }

    private function captureTicketingException(
        callable $callback
    ): InboundEmailTicketingException {
        try {
            $callback();
        } catch (
            InboundEmailTicketingException $exception
        ) {
            return $exception;
        }

        $this->fail(
            'Expected InboundEmailTicketingException was not thrown.'
        );
    }
}
