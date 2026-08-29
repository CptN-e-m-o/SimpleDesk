<?php

namespace Tests\Feature\Admin\Mail\Ticketing;

use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Enums\Admin\Mail\MailboxDriver;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\Mailbox;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\User\User;
use App\Services\Admin\Mail\Ticketing\InboundEmailThreadMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InboundEmailThreadMatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_matches_ticket_using_in_reply_to_message_id(): void
    {
        $requester = $this->createRequester();

        $mailbox = $this->createMailbox();

        $ticket = $this->createTicket(
            requester: $requester,
            mailbox: $mailbox,
        );

        $this->createEmailMessage(
            mailbox: $mailbox,
            ticket: $ticket,
            direction: EmailMessageDirection::Outgoing,
            status: EmailMessageStatus::Sent,
            internetMessageId: '<Agent-Reply-123@Example.Test>',
        );

        $incomingMessage = $this->createEmailMessage(
            mailbox: $mailbox,
            direction: EmailMessageDirection::Incoming,
            status: EmailMessageStatus::Received,
            inReplyToMessageId: '  <AGENT-REPLY-123@example.test>  ',
        );

        $matchedTicket = $this->matcher()->match(
            $incomingMessage
        );

        $this->assertNotNull(
            $matchedTicket
        );

        $this->assertSame(
            $ticket->id,
            $matchedTicket->id
        );
    }

    public function test_it_uses_the_latest_reference_first(): void
    {
        $requester = $this->createRequester();

        $mailbox = $this->createMailbox();

        $firstTicket = $this->createTicket(
            requester: $requester,
            mailbox: $mailbox,
        );

        $secondTicket = $this->createTicket(
            requester: $requester,
            mailbox: $mailbox,
        );

        $this->createEmailMessage(
            mailbox: $mailbox,
            ticket: $firstTicket,
            direction: EmailMessageDirection::Outgoing,
            status: EmailMessageStatus::Sent,
            internetMessageId: '<first-reference@example.test>',
        );

        $this->createEmailMessage(
            mailbox: $mailbox,
            ticket: $secondTicket,
            direction: EmailMessageDirection::Outgoing,
            status: EmailMessageStatus::Sent,
            internetMessageId: '<second-reference@example.test>',
        );

        $incomingMessage = $this->createEmailMessage(
            mailbox: $mailbox,
            direction: EmailMessageDirection::Incoming,
            status: EmailMessageStatus::Received,
            referenceMessageIds: [
                '<first-reference@example.test>',
                '<second-reference@example.test>',
            ],
        );

        $matchedTicket = $this->matcher()->match(
            $incomingMessage
        );

        $this->assertNotNull(
            $matchedTicket
        );

        $this->assertSame(
            $secondTicket->id,
            $matchedTicket->id
        );
    }

    public function test_it_does_not_match_message_from_another_mailbox(): void
    {
        $requester = $this->createRequester();

        $firstMailbox = $this->createMailbox();

        $secondMailbox = $this->createMailbox();

        $firstMailboxTicket = $this->createTicket(
            requester: $requester,
            mailbox: $firstMailbox,
        );

        $this->createEmailMessage(
            mailbox: $firstMailbox,
            ticket: $firstMailboxTicket,
            direction: EmailMessageDirection::Outgoing,
            status: EmailMessageStatus::Sent,
            internetMessageId: '<shared-message-id@example.test>',
        );

        $incomingMessage = $this->createEmailMessage(
            mailbox: $secondMailbox,
            direction: EmailMessageDirection::Incoming,
            status: EmailMessageStatus::Received,
            inReplyToMessageId: '<shared-message-id@example.test>',
        );

        $matchedTicket = $this->matcher()->match(
            $incomingMessage
        );

        $this->assertNull(
            $matchedTicket
        );
    }

    public function test_closed_ticket_returns_null_when_action_is_new_ticket(): void
    {
        config([
            'simpledesk-mail-ticketing.closed_ticket_action' => 'new_ticket',
        ]);

        $requester = $this->createRequester();

        $mailbox = $this->createMailbox();

        $ticket = $this->createTicket(
            requester: $requester,
            mailbox: $mailbox,
            status: Ticket::STATUS_CLOSED,
        );

        $this->createEmailMessage(
            mailbox: $mailbox,
            ticket: $ticket,
            direction: EmailMessageDirection::Outgoing,
            status: EmailMessageStatus::Sent,
            internetMessageId: '<closed-ticket@example.test>',
        );

        $incomingMessage = $this->createEmailMessage(
            mailbox: $mailbox,
            direction: EmailMessageDirection::Incoming,
            status: EmailMessageStatus::Received,
            inReplyToMessageId: '<closed-ticket@example.test>',
        );

        $matchedTicket = $this->matcher()->match(
            $incomingMessage
        );

        $this->assertNull(
            $matchedTicket
        );
    }

    public function test_closed_ticket_can_be_matched_when_action_allows_reply(): void
    {
        config([
            'simpledesk-mail-ticketing.closed_ticket_action' => 'reply',
        ]);

        $requester = $this->createRequester();

        $mailbox = $this->createMailbox();

        $ticket = $this->createTicket(
            requester: $requester,
            mailbox: $mailbox,
            status: Ticket::STATUS_CLOSED,
        );

        $this->createEmailMessage(
            mailbox: $mailbox,
            ticket: $ticket,
            direction: EmailMessageDirection::Outgoing,
            status: EmailMessageStatus::Sent,
            internetMessageId: '<closed-ticket-reply@example.test>',
        );

        $incomingMessage = $this->createEmailMessage(
            mailbox: $mailbox,
            direction: EmailMessageDirection::Incoming,
            status: EmailMessageStatus::Received,
            inReplyToMessageId: '<closed-ticket-reply@example.test>',
        );

        $matchedTicket = $this->matcher()->match(
            $incomingMessage
        );

        $this->assertNotNull(
            $matchedTicket
        );

        $this->assertSame(
            $ticket->id,
            $matchedTicket->id
        );
    }

    private function matcher(): InboundEmailThreadMatcher
    {
        return $this->app->make(
            InboundEmailThreadMatcher::class
        );
    }

    private function createRequester(): User
    {
        $token = strtolower(
            (string) Str::ulid()
        );

        return User::query()->create([
            'email' => "thread-requester-{$token}@example.test",

            'username' => "thread-requester-{$token}",

            'first_name' => 'Thread',

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
            'name' => "Thread Mailbox {$token}",

            'email_address' => "thread-support-{$token}@example.test",

            'display_name' => 'Thread Support',

            'department_id' => null,

            'is_active' => true,

            'is_default_outgoing' => false,

            'internal_notes' => null,
        ]);
    }

    private function createTicket(
        User $requester,
        Mailbox $mailbox,
        string $status = Ticket::STATUS_OPEN,
    ): Ticket {
        $token = strtoupper(
            substr(
                (string) Str::ulid(),
                -10
            )
        );

        return Ticket::query()->create([
            'ticket_number' => "THREAD-{$token}",

            'requester_id' => $requester->id,

            'category_id' => null,

            'assignee_id' => null,

            'mailbox_id' => $mailbox->id,

            'department_id' => null,

            'subject' => 'Thread matcher test ticket',

            'priority_id' => TicketPriority::query()->where('is_default', true)->valueOrFail('id'),

            'status' => $status,

            'source' => Ticket::SOURCE_EMAIL,

            'service' => null,

            'description' => 'Thread matcher test description.',

            'last_reply_at' => now(),

            'resolved_at' => $status === Ticket::STATUS_RESOLVED
                    ? now()
                    : null,

            'closed_at' => $status === Ticket::STATUS_CLOSED
                    ? now()
                    : null,
        ]);
    }

    private function createEmailMessage(
        Mailbox $mailbox,
        EmailMessageDirection $direction,
        EmailMessageStatus $status,
        ?Ticket $ticket = null,
        ?string $internetMessageId = null,
        ?string $inReplyToMessageId = null,
        array $referenceMessageIds = [],
    ): EmailMessage {
        $token = strtolower(
            (string) Str::ulid()
        );

        return EmailMessage::query()->create([
            'mailbox_id' => $mailbox->id,

            'mailbox_channel_id' => null,

            'ticket_id' => $ticket?->id,

            'ticket_reply_id' => null,

            'direction' => $direction,

            'driver' => $direction
                === EmailMessageDirection::Outgoing
                    ? MailboxDriver::Smtp
                    : MailboxDriver::Imap,

            'status' => $status,

            'idempotency_key' => "thread-matcher-{$token}",

            'external_message_id' => "external-{$token}",

            'internet_message_id' => $internetMessageId,

            'in_reply_to_message_id' => $inReplyToMessageId,

            'reference_message_ids' => $referenceMessageIds,

            'sender_address' => $direction
                === EmailMessageDirection::Outgoing
                    ? $mailbox->email_address
                    : "customer-{$token}@example.test",

            'sender_name' => $direction
                === EmailMessageDirection::Outgoing
                    ? $mailbox->display_name
                    : 'Test Customer',

            'to_recipients' => [
                [
                    'address' => $direction
                        === EmailMessageDirection::Outgoing
                            ? "customer-{$token}@example.test"
                            : $mailbox->email_address,

                    'name' => $direction
                        === EmailMessageDirection::Outgoing
                            ? 'Test Customer'
                            : $mailbox->display_name,
                ],
            ],

            'cc_recipients' => [],

            'bcc_recipients' => [],

            'reply_to_recipients' => [],

            'subject' => 'Re: Thread matcher test',

            'text_body' => 'Thread matcher test body.',

            'html_body' => null,

            'headers' => [],

            'metadata' => [],

            'received_at' => $direction
                === EmailMessageDirection::Incoming
                    ? now()
                    : null,

            'sent_at' => $direction
                === EmailMessageDirection::Outgoing
                    ? now()
                    : null,

            'processed_at' => $status
                === EmailMessageStatus::Processed
                    ? now()
                    : null,
        ]);
    }
}
