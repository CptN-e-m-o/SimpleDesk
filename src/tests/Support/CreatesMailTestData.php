<?php

namespace Tests\Support;

use App\Enums\Admin\Mail\EmailAttachmentScanStatus;
use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Models\Admin\Mail\EmailAttachment;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\Mailbox;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User\User;
use Illuminate\Support\Facades\Storage;

trait CreatesMailTestData
{
    protected function createSuperAdmin(): User
    {
        $agent = User::factory()->create();

        $role = Role::query()->create([
            'name' => 'super_admin',
            'label' => 'Super administrator',
            'description' => null,
            'type' => 'agent',
            'is_system' => true,
            'is_default' => false,
        ]);

        $agent->roles()->attach(
            $role
        );

        return $agent;
    }

    protected function createMailbox(
        array $attributes = []
    ): Mailbox {
        return Mailbox::query()->create(
            array_merge(
                [
                    'name' => 'Support',
                    'email_address' =>
                        'support@simpledesk.test',

                    'display_name' =>
                        'SimpleDesk Support',

                    'department_id' => null,
                    'is_active' => true,

                    'is_default_outgoing' =>
                        true,

                    'internal_notes' => null,
                ],
                $attributes
            )
        );
    }

    protected function createTicket(
        User $requester,
        ?User $agent = null,
        ?Mailbox $mailbox = null,
        array $attributes = [],
    ): Ticket {
        $mailbox ??= $this->createMailbox();

        return Ticket::factory()->create(
            array_merge(
                [
                    'requester_id' =>
                        $requester->id,

                    'category_id' => null,

                    'assignee_id' =>
                        $agent?->id,

                    'mailbox_id' =>
                        $mailbox->id,

                    'department_id' => null,

                    'status' =>
                        Ticket::STATUS_OPEN,

                    'source' =>
                        Ticket::SOURCE_PORTAL,
                ],
                $attributes
            )
        );
    }

    /**
     * @return array{
     *     0: EmailAttachment,
     *     1: string,
     *     2: Ticket,
     *     3: EmailMessage
     * }
     */
    protected function createStoredAttachment(
        User $requester,
        ?User $agent = null,
        EmailAttachmentScanStatus $scanStatus =
        EmailAttachmentScanStatus::Clean,
    ): array {
        $mailbox = $this->createMailbox();

        $ticket = $this->createTicket(
            requester: $requester,
            agent: $agent,
            mailbox: $mailbox,
        );

        $emailMessage =
            EmailMessage::query()->create([
                'mailbox_id' =>
                    $mailbox->id,

                'mailbox_channel_id' =>
                    null,

                'ticket_id' =>
                    $ticket->id,

                'ticket_reply_id' =>
                    null,

                'direction' =>
                    EmailMessageDirection::Incoming,

                'driver' => null,

                'status' =>
                    EmailMessageStatus::Processed,

                'idempotency_key' =>
                    'download-test:'
                    . $ticket->id,

                'external_message_id' =>
                    null,

                'internet_message_id' =>
                    null,

                'subject' =>
                    'Attachment download test',

                'text_body' =>
                    'Incoming email body.',

                'html_body' =>
                    '<p>Incoming email body.</p>',

                'headers' => [],
                'metadata' => [],
            ]);

        $contents =
            'Private customer attachment.';

        $path = implode(
            '/',
            [
                'mail',
                'attachments',
                (string) $emailMessage->id,
                'customer-notes.txt',
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
                    'download-test:'
                    . $emailMessage->id
                ),

                'file_name' =>
                    'customer-notes.txt',

                'mime_type' =>
                    'text/plain',

                'size' =>
                    strlen($contents),

                'disk' => 'local',
                'path' => $path,

                'checksum_sha256' => hash(
                    'sha256',
                    $contents
                ),

                'content_id' => null,
                'is_inline' => false,

                'scan_status' =>
                    $scanStatus,

                'scanned_at' => now(),

                'quarantined_at' =>
                    null,

                'scan_result' => null,
                'metadata' => [],
            ]);

        return [
            $attachment,
            $contents,
            $ticket,
            $emailMessage,
        ];
    }
}
