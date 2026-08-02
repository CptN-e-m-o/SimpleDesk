<?php

namespace App\Http\Resources\Admin\Mail;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MailboxResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email_address' => $this->email_address,
            'display_name' => $this->display_name,
            'department_id' => $this->department_id,
            'department' => $this->whenLoaded(
                'department',
                fn (): ?array => $this->department === null
                    ? null
                    : [
                        'id' => $this->department->id,
                        'name' => $this->department->name,
                    ]
            ),
            'is_active' => $this->is_active,
            'is_default_outgoing' => $this->is_default_outgoing,
            'internal_notes' => $this->internal_notes,
            'channels_count' => $this->whenCounted('channels'),
            'email_messages_count' => $this->whenCounted('emailMessages'),
            'channels' => MailboxChannelResource::collection(
                $this->whenLoaded('channels')
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
