<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'business_hours' => $this->business_hours,
            'outgoing_email' => $this->outgoing_email,
            'signature' => $this->signature,
            'is_default' => $this->is_default,

            'is_deleted' => $this->trashed(),
            'deleted_at' => $this->deleted_at,

            'status' => $this->whenLoaded('status', fn () => $this->status ? [
                'id' => $this->status->id,
                'name' => $this->status->name,
                'code' => $this->status->code,
                'color' => $this->status->color,
            ] : null),

            'managers' => $this->whenLoaded('managers', fn () => $this->managers->map(fn ($manager) => [
                'id' => $manager->id,
                'name' => $manager->name,
                'email' => $manager->email,
            ])->values()),

            'teams' => $this->whenLoaded('teams', fn () => $this->teams->map(fn ($team) => [
                'id' => $team->id,
                'name' => $team->name,
            ])->values()),

            'users' => $this->whenLoaded('users', fn () => $this->users->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_manager' => (bool) $user->pivot->is_manager,
            ])->values()),

            'members_count' => $this->whenLoaded('users', fn () => $this->users->count()),
            'teams_count' => $this->whenLoaded('teams', fn () => $this->teams->count()),
            'managers_count' => $this->whenLoaded('managers', fn () => $this->managers->count()),
        ];
    }
}
