<?php

namespace App\Http\Resources\Admin\Mail;

use App\Services\Admin\Mail\MailSensitiveDataRedactor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmailQuarantineDiagnosticResource extends JsonResource
{
    public function toArray(
        Request $request
    ): array {
        $redactor = app(
            MailSensitiveDataRedactor::class
        );

        return [
            'id' => $this->id,

            'email_message_id' => $this->email_message_id,

            'mailbox' => $this->whenLoaded(
                'mailbox',
                fn (): ?array => $this->mailbox === null
                    ? null
                    : [
                        'id' => $this->mailbox->id,

                        'name' => $this->mailbox->name,

                        'email_address' => $this
                            ->mailbox
                            ->email_address,
                    ]
            ),

            'channel' => $this->whenLoaded(
                'mailboxChannel',
                fn (): ?array => $this->mailboxChannel === null
                    ? null
                    : [
                        'id' => $this
                            ->mailboxChannel
                            ->id,

                        'name' => $this
                            ->mailboxChannel
                            ->name,
                    ]
            ),

            'message' => $this->whenLoaded(
                'emailMessage',
                fn (): ?array => $this->emailMessage === null
                    ? null
                    : [
                        'id' => $this
                            ->emailMessage
                            ->id,

                        'direction' => $this
                            ->emailMessage
                            ->direction
                            ->value,

                        'status' => $this
                            ->emailMessage
                            ->status
                            ->value,

                        'sender_address' => $this
                            ->emailMessage
                            ->sender_address,

                        'subject' => $this
                            ->emailMessage
                            ->subject,
                    ]
            ),

            'stage' => $this->stage->value,

            'reason_code' => $this->reason_code,

            'reason_message' => $this->reason_message === null
                    ? null
                    : $redactor->redactString(
                        $this->reason_message
                    ),

            'attempts' => $this->attempts,

            'state' => match (true) {
                $this->resolved_at !== null => 'resolved',

                $this->released_at !== null => 'released_for_retry',

                default => 'open',
            },

            'first_quarantined_at' => $this
                ->first_quarantined_at
                ?->toIso8601String(),

            'last_quarantined_at' => $this
                ->last_quarantined_at
                ?->toIso8601String(),

            'released_at' => $this
                ->released_at
                ?->toIso8601String(),

            'released_by' => $this->whenLoaded(
                'releasedBy',
                fn (): ?array => $this->releasedBy === null
                    ? null
                    : [
                        'id' => $this
                            ->releasedBy
                            ->id,

                        'email' => $this
                            ->releasedBy
                            ->email,

                        'username' => $this
                            ->releasedBy
                            ->username,
                    ]
            ),

            'resolved_at' => $this
                ->resolved_at
                ?->toIso8601String(),

            'resolution' => $this->resolution?->value,

            'created_at' => $this
                ->created_at
                ?->toIso8601String(),

            'updated_at' => $this
                ->updated_at
                ?->toIso8601String(),
        ];
    }
}
