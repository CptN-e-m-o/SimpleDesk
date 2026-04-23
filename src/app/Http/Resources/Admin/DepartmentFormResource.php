<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentFormResource extends JsonResource
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
            'department_status_id' => $this->department_status_id,
            'signature' => $this->signature,
            'is_default' => $this->is_default,

            'managers' => $this->managers->map(fn ($manager) => [
                'id' => $manager->id,
                'name' => $manager->name,
                'email' => $manager->email,
            ])->values(),

            'teams' => $this->teams->map(fn ($team) => [
                'id' => $team->id,
                'name' => $team->name,
            ])->values(),
        ];
    }
}
