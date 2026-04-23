<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamFormResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lead = $this->whenLoaded('members', function () {
            return $this->members->firstWhere('pivot.is_lead', true);
        });

        return [
            'id' => $this->id,
            'name' => $this->name,
            'departments' => $this->whenLoaded('departments', function () {
                return $this->departments
                    ->map(fn ($department) => [
                        'id' => (int) $department->id,
                        'name' => $department->name,
                        'slug' => $department->slug,
                    ])
                    ->values();
            }, collect()),
            'is_active' => (bool) $this->is_active,
            'admin_notes' => $this->admin_notes,
            'member_ids' => $this->whenLoaded('members', function () {
                return $this->members
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->values();
            }, collect()),
            'lead_user_id' => $lead?->id ? (int) $lead->id : null,
        ];
    }
}
