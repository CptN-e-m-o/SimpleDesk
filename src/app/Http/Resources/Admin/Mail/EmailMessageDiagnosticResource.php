<?php

namespace App\Http\Resources\Admin\Mail;

use App\Services\Admin\Mail\Diagnostics\MailDiagnosticsThresholds;
use App\Services\Admin\Mail\MailSensitiveDataRedactor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmailMessageDiagnosticResource extends JsonResource
{
    public function toArray(
        Request $request
    ): array {
        $redactor = app(
            MailSensitiveDataRedactor::class
        );

        $thresholds = app(
            MailDiagnosticsThresholds::class
        );

        return [
            'id' => $this->id,

            'mailbox' => $this->whenLoaded(
                'mailbox',
                fn (): ?array =>
                $this->mailbox === null
                    ? null
                    : [
                    'id' =>
                        $this->mailbox->id,

                    'name' =>
                        $this->mailbox->name,

                    'email_address' =>
                        $this
                            ->mailbox
                            ->email_address,
                ]
            ),

            'channel' => $this->whenLoaded(
                'mailboxChannel',
                fn (): ?array =>
                $this->mailboxChannel === null
                    ? null
                    : [
                    'id' =>
                        $this
                            ->mailboxChannel
                            ->id,

                    'name' =>
                        $this
                            ->mailboxChannel
                            ->name,

                    'direction' =>
                        $this
                            ->mailboxChannel
                            ->direction
                            ->value,

                    'driver' =>
                        $this
                            ->mailboxChannel
                            ->driver
                            ->value,
                ]
            ),

            'ticket_id' =>
                $this->ticket_id,

            'ticket_reply_id' =>
                $this->ticket_reply_id,

            'direction' =>
                $this->direction->value,

            'driver' =>
                $this->driver?->value,

            'status' =>
                $this->status->value,

            'is_stuck' =>
                $thresholds->isMessageStuck(
                    $this->resource
                ),

            'idempotency_key' =>
                $this->idempotency_key,

            'external_message_id' =>
                $this->external_message_id,

            'internet_message_id' =>
                $this->internet_message_id,

            'sender_address' =>
                $this->sender_address,

            'sender_name' =>
                $this->sender_name,

            'recipient_counts' => [
                'to' => count(
                    $this->to_recipients ?? []
                ),

                'cc' => count(
                    $this->cc_recipients ?? []
                ),

                'bcc' => count(
                    $this->bcc_recipients ?? []
                ),

                'reply_to' => count(
                    $this->reply_to_recipients ?? []
                ),
            ],

            'subject' => $this->subject,

            'attachments_count' =>
                $this->whenCounted(
                    'attachments'
                ),

            'attachment_rejections_count' =>
                $this->whenCounted(
                    'attachmentRejections'
                ),

            'attempts_count' =>
                $this->whenCounted(
                    'attempts'
                ),

            'received_at' =>
                $this
                    ->received_at
                    ?->toIso8601String(),

            'queued_at' =>
                $this
                    ->queued_at
                    ?->toIso8601String(),

            'processing_started_at' =>
                $this
                    ->processing_started_at
                    ?->toIso8601String(),

            'processed_at' =>
                $this
                    ->processed_at
                    ?->toIso8601String(),

            'sent_at' =>
                $this
                    ->sent_at
                    ?->toIso8601String(),

            'delivered_at' =>
                $this
                    ->delivered_at
                    ?->toIso8601String(),

            'failed_at' =>
                $this
                    ->failed_at
                    ?->toIso8601String(),

            'failure_code' =>
                $this->failure_code,

            'failure_message' =>
                $this->failure_message === null
                    ? null
                    : $redactor->redactString(
                    $this->failure_message
                ),

            'created_at' =>
                $this
                    ->created_at
                    ?->toIso8601String(),

            'updated_at' =>
                $this
                    ->updated_at
                    ?->toIso8601String(),
        ];
    }
}
