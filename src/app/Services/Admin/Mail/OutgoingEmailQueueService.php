<?php

namespace App\Services\Admin\Mail;

use App\Data\Admin\Mail\MailAddressData;
use App\Data\Admin\Mail\OutgoingEmailMessageData;
use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Jobs\Admin\Mail\SendOutgoingEmailJob;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\Mailbox;
use InvalidArgumentException;
use Throwable;

class OutgoingEmailQueueService
{
    public function __construct(
        private readonly MailAttachmentStorageService $attachmentStorage,
        private readonly MailInternetMessageIdFactory $messageIds,
    ) {
    }

    public function queue(
        Mailbox $mailbox,
        OutgoingEmailMessageData $message,
        ?int $ticketId = null,
        ?int $ticketReplyId = null,
        bool $dispatch = true,
    ): EmailMessage {
        if (!$mailbox->is_active) {
            throw new InvalidArgumentException(
                "Mailbox [{$mailbox->id}] is disabled."
            );
        }

        $from = $message->from
            ?? new MailAddressData(
                address: $mailbox->email_address,
                name:
                $mailbox->display_name
                ?? $mailbox->name,
            );

        $internetMessageId =
            $message->internetMessageId
            ?? $this->messageIds->make(
            mailbox: $mailbox,
            idempotencyKey:
            $message->idempotencyKey,
        );

        $emailMessage = EmailMessage::query()
            ->firstOrCreate(
                [
                    'idempotency_key' =>
                        $message->idempotencyKey,
                ],
                [
                    'mailbox_id' => $mailbox->id,
                    'ticket_id' => $ticketId,
                    'ticket_reply_id' =>
                        $ticketReplyId,
                    'direction' =>
                        EmailMessageDirection::Outgoing,
                    'driver' => null,
                    'status' =>
                        EmailMessageStatus::Preparing,
                    'internet_message_id' =>
                        $internetMessageId,
                    'in_reply_to_message_id' =>
                        $message->inReplyToMessageId,
                    'reference_message_ids' =>
                        $message->references,
                    'sender_address' =>
                        $from->address,
                    'sender_name' => $from->name,
                    'to_recipients' =>
                        $this->addressesToArray(
                            $message->to
                        ),
                    'cc_recipients' =>
                        $this->addressesToArray(
                            $message->cc
                        ),
                    'bcc_recipients' =>
                        $this->addressesToArray(
                            $message->bcc
                        ),
                    'reply_to_recipients' =>
                        $this->addressesToArray(
                            $message->replyTo
                        ),
                    'subject' =>
                        $message->subject,
                    'text_body' =>
                        $message->textBody,
                    'html_body' =>
                        $message->htmlBody,
                    'headers' =>
                        $message->headers,
                    'metadata' =>
                        $message->metadata,
                ]
            );

        if (!$emailMessage->wasRecentlyCreated) {
            return $emailMessage->loadMissing(
                'attachments'
            );
        }

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

            if ($dispatch) {
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
            }

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
            static fn (
                MailAddressData $address
            ): array => $address->toArray(),
            $addresses,
        );
    }
}
