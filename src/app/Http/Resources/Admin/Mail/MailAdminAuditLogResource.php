<?php

namespace App\Http\Resources\Admin\Mail;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MailAdminAuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'actor' => $this->whenLoaded(
                'actor',
                fn (): ?array => $this->actor === null
                    ? null
                    : [
                        'id' => $this->actor->id,
                        'email' => $this->actor->email,
                        'username' => $this->actor->username,
                        'name' => $this->actor->name,
                    ]
            ),

            'mailbox' => $this->whenLoaded(
                'mailbox',
                fn (): ?array => $this->mailbox === null
                    ? null
                    : [
                        'id' => $this->mailbox->id,
                        'name' => $this->mailbox->name,
                        'email_address' => $this->mailbox->email_address,
                    ]
            ),

            'event' => $this->event->value,
            'status' => $this->status->value,
            'subject_type' => $this->subject_type?->value,
            'subject_id' => $this->subject_id,
            'request_id' => $this->request_id,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'context' => $this->context ?? [],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
