<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $members = $this->whenLoaded('members', function () {
            return $this->members->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email ?? null,
                    'is_lead' => (bool) ($member->pivot->is_lead ?? false),
                ];
            })->values();
        });

        $lead = $this->whenLoaded('members', function () {
            $leadMember = $this->members->firstWhere('pivot.is_lead', true);

            return $leadMember ? [
                'id' => $leadMember->id,
                'name' => $leadMember->name,
                'email' => $leadMember->email ?? null,
            ] : null;
        });

        return [
            'id' => $this->id,
            'name' => $this->name,
            'members_count' => $this->members_count ?? ($this->relationLoaded('members') ? $this->members->count() : 0),
            'is_active' => (bool) $this->is_active,
            'departments' => $this->departments ?? [],
            'admin_notes' => $this->admin_notes,
            'lead' => $lead,
            'members' => $members,

            'deleted_at' => $this->deleted_at?->toISOString(),
            'is_deleted' => $this->trashed(),
        ];
    }
}
