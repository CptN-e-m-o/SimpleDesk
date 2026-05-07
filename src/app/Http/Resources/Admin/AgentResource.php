<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'username' => $this->username,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'name' => trim($this->first_name . ' ' . $this->last_name),
            'location' => $this->location,

            'phone_country_iso2' => $this->phone_country_iso2,
            'phone_country_code' => $this->phone_country_code,
            'phone_number' => $this->phone_number,
            'phone_ext' => $this->phone_ext,

            'mobile_country_iso2' => $this->mobile_country_iso2,
            'mobile_country_code' => $this->mobile_country_code,
            'mobile_number' => $this->mobile_number,

            'timezone' => $this->timezone,
            'is_active' => (bool) $this->is_active,
            'is_deleted' => $this->trashed(),
            'deleted_at' => $this->deleted_at?->toISOString(),
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->map(fn ($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'label' => $role->label,
                    'type' => $role->type,
                ])->values();
            }),
        ];
    }
}
