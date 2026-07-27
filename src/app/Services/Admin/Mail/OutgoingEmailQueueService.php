<?php

namespace App\Services\Mail;

use App\Data\Mail\MailAddressData;
use App\Data\Mail\OutgoingEmailMessageData;
use App\Enums\Mail\EmailMessageDirection;
use App\Enums\Mail\EmailMessageStatus;
use App\Jobs\Mail\SendOutgoingEmailJob;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\Mailbox;
use InvalidArgumentException;
use Throwable;

class OutgoingEmailQueueService
{
    public function __construct(
        private readonly MailAttachmentStorageService $attachmentStorage,
    ) {
    }

    public function queue(
        Mailbox $mailbox,
        OutgoingEmailMessageData $message,
        ?int $ticketId = null,
        ?int $ticketReplyId = null,
    ): EmailMessage {
        if (!$mailbox->is_active) {
            throw new InvalidArgumentException(
                "Mailbox [{$mailbox->id}] is disabled."
            );
        }

        $existing = EmailMessage::query()
            ->where(
                'idempotency_key',
                $message->idempotencyKey
            )
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $from = $message->from
            ?? new MailAddressData(
                address: $mailbox->email_address,
                name: $mailbox->display_name
                ?? $mailbox->name,
            );

        $emailMessage = EmailMessage::query()->create([
            'mailbox_id' => $mailbox->id,
            'ticket_id' => $ticketId,
            'ticket_reply_id' => $ticketReplyId,
            'direction' => EmailMessageDirection::Outgoing,
            'driver' => null,
            'status' => EmailMessageStatus::Preparing,
            'idempotency_key' =>
                $message->idempotencyKey,
            'in_reply_to_message_id' =>
                $message->inReplyToMessageId,
            'reference_message_ids' =>
                $message->references,
            'sender_address' => $from->address,
            'sender_name' => $from->name,
            'to_recipients' =>
                $this->addressesToArray($message->to),
            'cc_recipients' =>
                $this->addressesToArray($message->cc),
            'bcc_recipients' =>
                $this->addressesToArray($message->bcc),
            'reply_to_recipients' =>
                $this->addressesToArray($message->replyTo),
            'subject' => $message->subject,
            'text_body' => $message->textBody,
            'html_body' => $message->htmlBody,
            'headers' => $message->headers,
            'metadata' => $message->metadata,
        ]);

        try {
            foreach (
                array_values($message->attachments)
                as $position => $attachment
            ) {
                $this->attachmentStorage->store(
                    emailMessage: $emailMessage,
                    attachment: $attachment,
                    position: $position,
                );
            }

            $emailMessage->forceFill([
                'status' => EmailMessageStatus::Queued,
                'queued_at' => now(),
            ])->save();

            SendOutgoingEmailJob::dispatch(
                $emailMessage->id
            )
                ->onQueue(
                    config(
                        'simpledesk-mail.queues.outgoing',
                        'mail-outgoing'
                    )
                )
                ->afterCommit();

            return $emailMessage->fresh(
                'attachments'
            );
        } catch (Throwable $exception) {
            $emailMessage->forceFill([
                'status' => EmailMessageStatus::Failed,
                'failed_at' => now(),
                'failure_code' =>
                    'outgoing_preparation_failed',
                'failure_message' =>
                    $exception->getMessage(),
            ])->save();

            throw $exception;
        }
    }

    /**
     * @param array<int, MailAddressData> $addresses
     */
    private function addressesToArray(
        array $addresses
    ): array {
        return array_map(
            static fn (MailAddressData $address): array =>
            $address->toArray(),
            $addresses,
        );
    }
}
