<?php

namespace Tests\Feature\Admin\Mail\Ticketing;

use App\Data\Admin\Mail\InboundEmailDecisionData;
use App\Data\Admin\Mail\ParsedInboundEmailContentData;
use App\Enums\Admin\Mail\EmailAttachmentScanStatus;
use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Enums\Admin\Mail\InboundEmailClassification;
use App\Enums\Admin\Mail\MailboxDriver;
use App\Exceptions\Admin\Mail\InboundEmailTicketingException;
use App\Models\Admin\Mail\EmailAttachment;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\Mailbox;
use App\Models\Ticket;
use App\Models\User\User;
use App\Services\Admin\Mail\ReplyParsing\InboundEmailClassifier;
use App\Services\Admin\Mail\ReplyParsing\InboundEmailReplyParser;
use App\Services\Admin\Mail\Ticketing\InboundEmailRequesterResolver;
use App\Services\Admin\Mail\Ticketing\InboundEmailThreadMatcher;
use App\Services\Admin\Mail\Ticketing\InboundEmailTicketProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class InboundEmailTicketProcessorTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_attachment_blocks_ticketing_until_scan_finishes(): void
    {
        $message = $this->createIncomingMessage(
            EmailAttachmentScanStatus::Pending
        );

        $processor = $this->processorThatMustStopBeforeTicketing();

        $exception = $this->captureTicketingException(
            fn () => $processor->process($message->id)
        );

        $this->assertTrue(
            $exception->retryable(),
            'Pending antivirus scan must produce a retryable exception.'
        );

        $message->refresh();

        $this->assertSame(
            EmailMessageStatus::Received,
            $message->status
        );

        $this->assertNull($message->ticket_id);
        $this->assertNull($message->ticket_reply_id);
        $this->assertNull($message->processed_at);

        $this->assertDatabaseCount('tickets', 0);
        $this->assertDatabaseCount('ticket_replies', 0);
    }

    public function test_not_scanned_attachment_blocks_ticketing_until_scan_finishes(): void
    {
        $message = $this->createIncomingMessage(
            EmailAttachmentScanStatus::NotScanned
        );

        $processor = $this->processorThatMustStopBeforeTicketing();

        $exception = $this->captureTicketingException(
            fn () => $processor->process($message->id)
        );

        $this->assertTrue(
            $exception->retryable(),
            'Not-scanned attachment must produce a retryable exception.'
        );

        $message->refresh();

        $this->assertSame(
            EmailMessageStatus::Received,
            $message->status
        );

        $this->assertNull($message->ticket_id);
        $this->assertNull($message->ticket_reply_id);
        $this->assertNull($message->processed_at);

        $this->assertDatabaseCount('tickets', 0);
        $this->assertDatabaseCount('ticket_replies', 0);
    }

    public function test_infected_attachment_blocks_ticket_creation_permanently(): void
    {
        $message = $this->createIncomingMessage(
            EmailAttachmentScanStatus::Infected
        );

        $processor = $this->processorThatMustStopBeforeTicketing();

        $exception = $this->captureTicketingException(
            fn () => $processor->process($message->id)
        );

        $this->assertFalse(
            $exception->retryable(),
            'Infected attachment must produce a non-retryable exception.'
        );

        $message->refresh();

        $this->assertSame(
            EmailMessageStatus::Failed,
            $message->status
        );

        $this->assertNotNull($message->failed_at);
        $this->assertNull($message->ticket_id);
        $this->assertNull($message->ticket_reply_id);
        $this->assertNull($message->processed_at);

        $this->assertDatabaseCount('tickets', 0);
        $this->assertDatabaseCount('ticket_replies', 0);
    }

    public function test_failed_attachment_scan_blocks_ticket_creation_permanently(): void
    {
        $message = $this->createIncomingMessage(
            EmailAttachmentScanStatus::Failed
        );

        $processor = $this->processorThatMustStopBeforeTicketing();

        $exception = $this->captureTicketingException(
            fn () => $processor->process($message->id)
        );

        $this->assertFalse(
            $exception->retryable(),
            'Final antivirus scan failure must produce a non-retryable exception.'
        );

        $message->refresh();

        $this->assertSame(
            EmailMessageStatus::Failed,
            $message->status
        );

        $this->assertNotNull($message->failed_at);
        $this->assertNull($message->ticket_id);
        $this->assertNull($message->ticket_reply_id);
        $this->assertNull($message->processed_at);

        $this->assertDatabaseCount('tickets', 0);
        $this->assertDatabaseCount('ticket_replies', 0);
    }

    public function test_clean_attachment_allows_ticket_creation_and_processing_is_idempotent(): void
    {
        $message = $this->createIncomingMessage(
            EmailAttachmentScanStatus::Clean
        );

        $requester = User::query()->create([
            'email' => 'requester@example.test',
            'username' => 'mail-test-requester',
            'first_name' => 'Mail',
            'last_name' => 'Requester',
            'password' => 'test-password',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $processor = $this->processorThatCreatesTicket(
            $requester
        );

        $firstResult = $processor->process(
            $message->id
        );

        $secondResult = $processor->process(
            $message->id
        );

        $message->refresh();

        $this->assertSame(
            EmailMessageStatus::Processed,
            $message->status
        );

        $this->assertNotNull($message->processed_at);
        $this->assertNull($message->failed_at);
        $this->assertNull($message->failure_code);
        $this->assertNull($message->failure_message);

        $this->assertNotNull($message->ticket_id);
        $this->assertNull($message->ticket_reply_id);

        $this->assertDatabaseCount('tickets', 1);
        $this->assertDatabaseCount('ticket_replies', 0);

        $ticket = Ticket::query()->sole();

        $this->assertSame(
            $requester->id,
            $ticket->requester_id
        );

        $this->assertSame(
            $message->mailbox_id,
            $ticket->mailbox_id
        );

        $this->assertSame(
            Ticket::SOURCE_EMAIL,
            $ticket->source
        );

        $this->assertSame(
            'Не работает авторизация',
            $ticket->subject
        );

        $this->assertSame(
            'Здравствуйте. Не могу войти в личный кабинет.',
            $ticket->description
        );

        $this->assertSame(
            $ticket->id,
            $message->ticket_id
        );

        $this->assertSame(
            $ticket->id,
            $firstResult->ticket_id
        );

        $this->assertSame(
            $ticket->id,
            $secondResult->ticket_id
        );
    }

    private function createIncomingMessage(
        EmailAttachmentScanStatus $scanStatus
    ): EmailMessage {
        $token = strtolower(
            (string) Str::ulid()
        );

        $mailbox = Mailbox::query()->create([
            'name' => 'Test Support',
            'email_address' => "support-{$token}@example.test",
            'display_name' => 'Test Support',
            'department_id' => null,
            'is_active' => true,
            'is_default_outgoing' => false,
            'internal_notes' => null,
        ]);

        $message = EmailMessage::query()->create([
            'mailbox_id' => $mailbox->id,
            'mailbox_channel_id' => null,
            'ticket_id' => null,
            'ticket_reply_id' => null,

            'direction' => EmailMessageDirection::Incoming,
            'driver' => MailboxDriver::Imap,
            'status' => EmailMessageStatus::Received,

            'idempotency_key' => "test-incoming-{$token}",

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

            'subject' => 'Не работает авторизация',

            'text_body' => 'Здравствуйте. Не могу войти в личный кабинет.',

            'html_body' => null,
            'headers' => [],
            'metadata' => [],

            'received_at' => now(),
        ]);

        $content = 'SimpleDesk test attachment';

        EmailAttachment::query()->create([
            'email_message_id' => $message->id,
            'position' => 0,

            'external_id' => "attachment-{$token}",

            'deduplication_key' => hash(
                'sha256',
                "{$message->id}|{$token}|document.txt"
            ),

            'file_name' => 'document.txt',
            'mime_type' => 'text/plain',

            'size' => strlen($content),

            'disk' => 'local',

            'path' => "testing/mail/{$message->id}/document.txt",

            'checksum_sha256' => hash(
                'sha256',
                $content
            ),

            'content_id' => null,
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

            'quarantined_at' => $scanStatus === EmailAttachmentScanStatus::Infected
                    ? now()
                    : null,

            'scan_result' => null,
            'metadata' => [],
        ]);

        return $message->fresh([
            'mailbox',
            'attachments',
        ]);
    }

    private function processorThatMustStopBeforeTicketing(): InboundEmailTicketProcessor
    {
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

        $classifier = Mockery::mock(
            InboundEmailClassifier::class
        );

        $classifier->shouldNotReceive(
            'classify'
        );

        $replyParser = Mockery::mock(
            InboundEmailReplyParser::class
        );

        $replyParser->shouldNotReceive(
            'parse'
        );

        $this->app->instance(
            InboundEmailRequesterResolver::class,
            $requesters
        );

        $this->app->instance(
            InboundEmailThreadMatcher::class,
            $threads
        );

        $this->app->instance(
            InboundEmailClassifier::class,
            $classifier
        );

        $this->app->instance(
            InboundEmailReplyParser::class,
            $replyParser
        );

        return $this->app->make(
            InboundEmailTicketProcessor::class
        );
    }

    private function processorThatCreatesTicket(
        User $requester
    ): InboundEmailTicketProcessor {
        $requesters = Mockery::mock(
            InboundEmailRequesterResolver::class
        );

        $requesters
            ->shouldReceive('resolve')
            ->once()
            ->andReturn($requester);

        $threads = Mockery::mock(
            InboundEmailThreadMatcher::class
        );

        $threads
            ->shouldReceive('match')
            ->once()
            ->andReturnNull();

        $classifier = Mockery::mock(
            InboundEmailClassifier::class
        );

        $classifier
            ->shouldReceive('classify')
            ->once()
            ->andReturn(
                new InboundEmailDecisionData(
                    shouldProcess: true,
                    classification: InboundEmailClassification::Human,
                    reason: 'Human test email.',
                )
            );

        $replyParser = Mockery::mock(
            InboundEmailReplyParser::class
        );

        $replyParser
            ->shouldReceive('parse')
            ->once()
            ->andReturn(
                new ParsedInboundEmailContentData(
                    body: 'Здравствуйте. Не могу войти в личный кабинет.',

                    source: 'text',

                    quotedTextRemoved: false,
                    signatureRemoved: false,

                    originalLength: 49,
                    parsedLength: 49,
                )
            );

        $this->app->instance(
            InboundEmailRequesterResolver::class,
            $requesters
        );

        $this->app->instance(
            InboundEmailThreadMatcher::class,
            $threads
        );

        $this->app->instance(
            InboundEmailClassifier::class,
            $classifier
        );

        $this->app->instance(
            InboundEmailReplyParser::class,
            $replyParser
        );

        return $this->app->make(
            InboundEmailTicketProcessor::class
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
