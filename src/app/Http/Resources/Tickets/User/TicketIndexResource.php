<?php

namespace App\Http\Resources\Tickets\User;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketIndexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'subject' => $this->subject,
            'status' => $this->status,
            'status_label' => Ticket::statusLabel($this->status),
            'priority' => $this->priority,
            'priority_label' => Ticket::priorityLabel($this->priority),
            'service' => $this->service,
            'created_at' => $this->created_at?->toDateTimeString(),
            'last_reply_at' => $this->last_reply_at?->toDateTimeString(),
            'category' => $this->category
                ? [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ]
                : null,
        ];
    }
}
