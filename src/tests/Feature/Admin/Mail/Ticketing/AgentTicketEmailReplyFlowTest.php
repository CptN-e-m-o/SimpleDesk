<?php

namespace Tests\Feature\Admin\Mail\Ticketing;

use App\Data\Admin\Mail\MailAttachmentData;
use App\Enums\Admin\Mail\EmailAttachmentScanStatus;
use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Exceptions\Admin\Mail\TicketReplyEmailException;
use App\Jobs\Admin\Mail\SendOutgoingEmailJob;
use App\Models\Admin\Mail\EmailAttachment;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\TicketReply;
use App\Models\User\User;
use App\Services\Admin\Mail\Ticketing\TicketReplyEmailService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesMailTestData;
use Tests\TestCase;

class AgentTicketEmailReplyFlowTest extends TestCase
{
    use CreatesMailTestData;
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Queue::fake();

        config()->set(
            'simpledesk-mail-antivirus.enabled',
            false
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
                'application/pdf',
            ]
        );

        config()->set(
            'simpledesk-mail.outgoing.max_attachment_count',
            10
        );

        config()->set(
            'simpledesk-mail.outgoing.max_attachment_bytes',
            25 * 1024 * 1024
        );

        config()->set(
            'simpledesk-mail.outgoing.max_total_attachment_bytes',
            40 * 1024 * 1024
        );

        config()->set(
            'simpledesk-mail-ticketing.outgoing_replies.enabled',
            true
        );
    }

    public function test_agent_can_create_email_reply_with_attachment(): void
    {
        $agent = $this->createSuperAdmin();

        $requester = User::factory()->create();

        $ticket = $this->createTicket(
            requester: $requester,
            agent: $agent,
        );

        $contents = 'Customer diagnostic information.';

        $response = $this
            ->actingAs($agent)
            ->post(
                route(
                    'agent.tickets.email-replies.store',
                    $ticket
                ),
                [
                    'message' => 'Please check the attached file.',
                    'attachments' => [
                        UploadedFile::fake()->createWithContent(
                            'diagnostic.txt',
                            $contents
                        ),
                    ],
                ]
            );

        $response
            ->assertRedirect()
            ->assertSessionHas(
                'success',
                'Reply was queued for email delivery.'
            );

        $reply = TicketReply::query()->sole();

        $emailMessage = EmailMessage::query()->sole();

        $attachment = EmailAttachment::query()->sole();

        $this->assertSame(
            $ticket->id,
            $reply->ticket_id
        );

        $this->assertSame(
            $agent->id,
            $reply->user_id
        );

        $this->assertSame(
            'Please check the attached file.',
            $reply->message
        );

        $this->assertFalse(
            $reply->is_internal
        );

        $this->assertSame(
            EmailMessageDirection::Outgoing,
            $emailMessage->direction
        );

        $this->assertSame(
            EmailMessageStatus::Queued,
            $emailMessage->status
        );

        $this->assertSame(
            $ticket->id,
            $emailMessage->ticket_id
        );

        $this->assertSame(
            $reply->id,
            $emailMessage->ticket_reply_id
        );

        $this->assertSame(
            'ticket-reply:'
            . $reply->id
            . ':outgoing:v1',
            $emailMessage->idempotency_key
        );

        $this->assertSame(
            $requester->email,
            $emailMessage->to_recipients[0]['address']
        );

        $this->assertSame(
            'diagnostic.txt',
            $attachment->file_name
        );

        $this->assertSame(
            'text/plain',
            $attachment->mime_type
        );

        $this->assertSame(
            strlen($contents),
            $attachment->size
        );

        $this->assertSame(
            hash('sha256', $contents),
            $attachment->checksum_sha256
        );

        $this->assertSame(
            EmailAttachmentScanStatus::NotScanned,
            $attachment->scan_status
        );

        Storage::disk(
            $attachment->disk
        )->assertExists(
            $attachment->path
        );

        $this->assertSame(
            $contents,
            Storage::disk(
                $attachment->disk
            )->get(
                $attachment->path
            )
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
    }

    public function test_requester_portal_reply_does_not_create_outgoing_email(): void
    {
        $agent = $this->createSuperAdmin();

        $requester = User::factory()->create();

        $ticket = $this->createTicket(
            requester: $requester,
            agent: $agent,
        );

        $response = $this
            ->actingAs($requester)
            ->post(
                route(
                    'tickets.replies.store',
                    $ticket
                ),
                [
                    'message' => 'Additional information from the customer.',
                ]
            );

        $response->assertRedirect(
            route(
                'tickets.show',
                $ticket
            )
        );

        $this->assertDatabaseHas(
            'ticket_replies',
            [
                'ticket_id' => $ticket->id,
                'user_id' => $requester->id,
                'message' => 'Additional information from the customer.',
                'is_internal' => false,
            ]
        );

        $this->assertDatabaseCount(
            'email_messages',
            0
        );

        $this->assertDatabaseCount(
            'email_attachments',
            0
        );

        Queue::assertNotPushed(
            SendOutgoingEmailJob::class
        );
    }

    public function test_internal_note_cannot_be_queued_as_email(): void
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
                'message' => 'Internal investigation notes.',
                'is_internal' => true,
            ]);

        try {
            app(
                TicketReplyEmailService::class
            )->queue(
                ticketReplyId: $reply->id,
                dispatch: true,
            );

            $this->fail(
                'Internal note was queued for email delivery.'
            );
        } catch (
        TicketReplyEmailException $exception
        ) {
            $this->assertSame(
                'internal_ticket_reply',
                $exception->errorCode()
            );

            $this->assertFalse(
                $exception->retryable()
            );
        }

        $this->assertDatabaseCount(
            'email_messages',
            0
        );

        Queue::assertNotPushed(
            SendOutgoingEmailJob::class
        );
    }

    public function test_repeated_queueing_is_idempotent_for_message_and_attachment(): void
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
                'message' => 'The same reply must only be prepared once.',
                'is_internal' => false,
            ]);

        $attachment = new MailAttachmentData(
            fileName: 'result.txt',
            mimeType: 'text/plain',
            size: strlen('result'),
            content: 'result',
        );

        $service = app(
            TicketReplyEmailService::class
        );

        $firstMessage = $service->queue(
            ticketReplyId: $reply->id,
            dispatch: false,
            attachments: [$attachment],
        );

        $secondMessage = $service->queue(
            ticketReplyId: $reply->id,
            dispatch: false,
            attachments: [$attachment],
        );

        $this->assertSame(
            $firstMessage->id,
            $secondMessage->id
        );

        $this->assertDatabaseCount(
            'email_messages',
            1
        );

        $this->assertDatabaseCount(
            'email_attachments',
            1
        );

        $storedAttachment = EmailAttachment::query()->sole();

        Storage::disk(
            $storedAttachment->disk
        )->assertExists(
            $storedAttachment->path
        );

        $this->assertSame(
            'result',
            Storage::disk(
                $storedAttachment->disk
            )->get(
                $storedAttachment->path
            )
        );

        Queue::assertNotPushed(
            SendOutgoingEmailJob::class
        );
    }

    public function test_disallowed_attachment_mime_type_is_rejected_before_reply_creation(): void
    {
        config()->set(
            'simpledesk-mail.outgoing.allowed_attachment_mime_types',
            [
                'text/plain',
            ]
        );

        $agent = $this->createSuperAdmin();

        $requester = User::factory()->create();

        $ticket = $this->createTicket(
            requester: $requester,
            agent: $agent,
        );

        $response = $this
            ->actingAs($agent)
            ->post(
                route(
                    'agent.tickets.email-replies.store',
                    $ticket
                ),
                [
                    'message' => 'This upload must be rejected.',
                    'attachments' => [
                        UploadedFile::fake()->create(
                            'payload.bin',
                            1,
                            'application/octet-stream'
                        ),
                    ],
                ]
            );

        $response
            ->assertRedirect()
            ->assertSessionHasErrors(
                'attachments.0'
            );

        $this->assertDatabaseCount(
            'ticket_replies',
            0
        );

        $this->assertDatabaseCount(
            'email_messages',
            0
        );

        $this->assertDatabaseCount(
            'email_attachments',
            0
        );

        Queue::assertNotPushed(
            SendOutgoingEmailJob::class
        );
    }

    public function test_oversized_attachment_is_rejected_before_reply_creation(): void
    {
        config()->set(
            'simpledesk-mail.outgoing.max_attachment_bytes',
            1024
        );

        config()->set(
            'simpledesk-mail.outgoing.max_total_attachment_bytes',
            1024
        );

        $agent = $this->createSuperAdmin();

        $requester = User::factory()->create();

        $ticket = $this->createTicket(
            requester: $requester,
            agent: $agent,
        );

        $response = $this
            ->actingAs($agent)
            ->post(
                route(
                    'agent.tickets.email-replies.store',
                    $ticket
                ),
                [
                    'message' => 'This upload is too large.',
                    'attachments' => [
                        UploadedFile::fake()->create(
                            'large.txt',
                            2,
                            'text/plain'
                        ),
                    ],
                ]
            );

        $response
            ->assertRedirect()
            ->assertSessionHasErrors(
                'attachments.0'
            );

        $this->assertDatabaseCount(
            'ticket_replies',
            0
        );

        $this->assertDatabaseCount(
            'email_messages',
            0
        );

        $this->assertDatabaseCount(
            'email_attachments',
            0
        );

        Queue::assertNotPushed(
            SendOutgoingEmailJob::class
        );
    }
}
