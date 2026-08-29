<?php

namespace Tests\Feature\Admin\Mail\Ticketing;

use App\Data\Admin\Mail\InboundEmailDecisionData;
use App\Data\Admin\Mail\ParsedInboundEmailContentData;
use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Enums\Admin\Mail\InboundEmailClassification;
use App\Enums\Admin\Mail\MailboxDriver;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\Mailbox;
use App\Models\Ticket;
use App\Models\TicketReply;
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

class InboundEmailTicketReplyProcessorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'simpledesk-mail-ticketing.customer_reply_status' => Ticket::STATUS_OPEN,

            'simpledesk-mail-ticketing.reopen_resolved_tickets' => true,

            'simpledesk-mail-ticketing.closed_ticket_action' => 'new_ticket',
        ]);
    }

    public function test_matching_waiting_ticket_creates_single_external_reply_and_reopens_ticket(): void
    {
        $requester = $this->createRequester();

        $mailbox = $this->createMailbox();

        $ticket = $this->createTicket(
            requester: $requester,
            mailbox: $mailbox,
            status: Ticket::STATUS_WAITING_FOR_CUSTOMER,
        );

        $message = $this->createIncomingReply(
            mailbox: $mailbox,
            requester: $requester,
        );

        $processor = $this->processorForReply(
            requester: $requester,
            ticket: $ticket,
            parsedBody: 'Проблема всё ещё сохраняется. Прикладываю дополнительную информацию.',
        );

        $firstResult = $processor->process(
            $message->id
        );

        $secondResult = $processor->process(
            $message->id
        );

        $message->refresh();
        $ticket->refresh();

        $this->assertSame(
            EmailMessageStatus::Processed,
            $message->status
        );

        $this->assertSame(
            $ticket->id,
            $message->ticket_id
        );

        $this->assertNotNull(
            $message->ticket_reply_id
        );

        $this->assertNotNull(
            $message->processed_at
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
            Ticket::STATUS_OPEN,
            $ticket->status
        );

        $this->assertNotNull(
            $ticket->last_reply_at
        );

        $this->assertDatabaseCount(
            'tickets',
            1
        );

        $this->assertDatabaseCount(
            'ticket_replies',
            1
        );

        $reply = TicketReply::query()->sole();

        $this->assertSame(
            $ticket->id,
            $reply->ticket_id
        );

        $this->assertSame(
            $requester->id,
            $reply->user_id
        );

        $this->assertSame(
            'Проблема всё ещё сохраняется. Прикладываю дополнительную информацию.',
            $reply->message
        );

        $this->assertFalse(
            $reply->is_internal
        );

        $this->assertSame(
            $reply->id,
            $message->ticket_reply_id
        );

        $this->assertSame(
            $reply->id,
            $firstResult->ticket_reply_id
        );

        $this->assertSame(
            $reply->id,
            $secondResult->ticket_reply_id
        );

        $ticketingMetadata =
            $message->metadata['ticketing']
            ?? [];

        $this->assertSame(
            'reply_created',
            $ticketingMetadata['action']
            ?? null
        );

        $this->assertSame(
            $ticket->id,
            $ticketingMetadata['ticket_id']
            ?? null
        );

        $this->assertSame(
            $reply->id,
            $ticketingMetadata['ticket_reply_id']
            ?? null
        );

        $this->assertSame(
            $requester->id,
            $ticketingMetadata['requester_id']
            ?? null
        );

        $this->assertSame(
            InboundEmailClassification::Human->value,
            $ticketingMetadata['classification']
            ?? null
        );
    }

    public function test_reply_to_resolved_ticket_reopens_ticket_and_clears_resolved_at(): void
    {
        $requester = $this->createRequester();

        $mailbox = $this->createMailbox();

        $ticket = $this->createTicket(
            requester: $requester,
            mailbox: $mailbox,
            status: Ticket::STATUS_RESOLVED,
            resolvedAt: now()->subDay(),
        );

        $this->assertNotNull(
            $ticket->resolved_at
        );

        $message = $this->createIncomingReply(
            mailbox: $mailbox,
            requester: $requester,
        );

        $processor = $this->processorForReply(
            requester: $requester,
            ticket: $ticket,
            parsedBody: 'После закрытия обращения ошибка появилась снова.',
        );

        $result = $processor->process(
            $message->id
        );

        $message->refresh();
        $ticket->refresh();

        $this->assertSame(
            EmailMessageStatus::Processed,
            $message->status
        );

        $this->assertSame(
            $ticket->id,
            $message->ticket_id
        );

        $this->assertNotNull(
            $message->ticket_reply_id
        );

        $this->assertSame(
            $message->ticket_reply_id,
            $result->ticket_reply_id
        );

        $this->assertSame(
            Ticket::STATUS_OPEN,
            $ticket->status
        );

        $this->assertNull(
            $ticket->resolved_at
        );

        $this->assertNull(
            $ticket->closed_at
        );

        $this->assertDatabaseCount(
            'tickets',
            1
        );

        $this->assertDatabaseCount(
            'ticket_replies',
            1
        );

        $reply = TicketReply::query()->sole();

        $this->assertSame(
            'После закрытия обращения ошибка появилась снова.',
            $reply->message
        );

        $this->assertFalse(
            $reply->is_internal
        );
    }

    private function createRequester(): User
    {
        $token = strtolower(
            (string) Str::ulid()
        );

        return User::query()->create([
            'email' => "requester-{$token}@example.test",

            'username' => "requester-{$token}",

            'first_name' => 'Mail',

            'last_name' => 'Requester',

            'password' => 'test-password',

            'email_verified_at' => now(),

            'is_active' => true,
        ]);
    }

    private function createMailbox(): Mailbox
    {
        $token = strtolower(
            (string) Str::ulid()
        );

        return Mailbox::query()->create([
            'name' => 'Test Support',

            'email_address' => "support-{$token}@example.test",

            'display_name' => 'Test Support',

            'department_id' => null,

            'is_active' => true,

            'is_default_outgoing' => false,

            'internal_notes' => null,
        ]);
    }

    private function createTicket(
        User $requester,
        Mailbox $mailbox,
        string $status,
        mixed $resolvedAt = null,
        mixed $closedAt = null,
    ): Ticket {
        $token = strtoupper(
            substr(
                (string) Str::ulid(),
                -10
            )
        );

        return Ticket::query()->create([
            'ticket_number' => "TEST-{$token}",

            'requester_id' => $requester->id,

            'category_id' => null,

            'assignee_id' => null,

            'mailbox_id' => $mailbox->id,

            'department_id' => null,

            'subject' => 'Не работает авторизация',

            'priority' => Ticket::PRIORITY_MEDIUM,

            'status' => $status,

            'source' => Ticket::SOURCE_EMAIL,

            'service' => null,

            'description' => 'Первоначальное обращение клиента.',

            'last_reply_at' => now()->subDays(2),

            'resolved_at' => $resolvedAt,

            'closed_at' => $closedAt,
        ]);
    }

    private function createIncomingReply(
        Mailbox $mailbox,
        User $requester,
    ): EmailMessage {
        $token = strtolower(
            (string) Str::ulid()
        );

        return EmailMessage::query()->create([
            'mailbox_id' => $mailbox->id,

            'mailbox_channel_id' => null,

            'ticket_id' => null,

            'ticket_reply_id' => null,

            'direction' => EmailMessageDirection::Incoming,

            'driver' => MailboxDriver::Imap,

            'status' => EmailMessageStatus::Received,

            'idempotency_key' => "test-incoming-reply-{$token}",

            'external_message_id' => "external-reply-{$token}",

            'internet_message_id' => "<reply-{$token}@example.test>",

            'in_reply_to_message_id' => '<original-agent-message@example.test>',

            'reference_message_ids' => [
                '<original-agent-message@example.test>',
            ],

            'sender_address' => $requester->email,

            'sender_name' => trim(
                "{$requester->first_name} {$requester->last_name}"
            ),

            'to_recipients' => [
                [
                    'address' => $mailbox->email_address,

                    'name' => $mailbox->display_name,
                ],
            ],

            'cc_recipients' => [],

            'bcc_recipients' => [],

            'reply_to_recipients' => [],

            'subject' => 'Re: Не работает авторизация',

            'text_body' => 'Проблема всё ещё сохраняется.',

            'html_body' => null,

            'headers' => [
                'in-reply-to' => [
                    '<original-agent-message@example.test>',
                ],

                'references' => [
                    '<original-agent-message@example.test>',
                ],
            ],

            'metadata' => [],

            'received_at' => now(),
        ]);
    }

    private function processorForReply(
        User $requester,
        Ticket $ticket,
        string $parsedBody,
    ): InboundEmailTicketProcessor {
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

                    reason: 'Human customer reply.',
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
                    body: $parsedBody,

                    source: 'text',

                    quotedTextRemoved: true,

                    signatureRemoved: false,

                    originalLength: 180,

                    parsedLength: mb_strlen($parsedBody),
                )
            );

        $requesters = Mockery::mock(
            InboundEmailRequesterResolver::class
        );

        $requesters
            ->shouldReceive('resolve')
            ->once()
            ->andReturn(
                $requester
            );

        $threads = Mockery::mock(
            InboundEmailThreadMatcher::class
        );

        $threads
            ->shouldReceive('match')
            ->once()
            ->andReturn(
                $ticket
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
}
