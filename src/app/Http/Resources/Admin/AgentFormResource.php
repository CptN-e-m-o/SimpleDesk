<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentFormResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'email' => $this->email,
            'username' => $this->username,

            'first_name' => $this->first_name,
            'last_name' => $this->last_name,

            'location' => $this->location,

            'phone_country_iso2' => $this->phone_country_iso2,
            'phone_country_code' => $this->phone_country_code,
            'phone_number' => $this->phone_number,
            'phone_ext' => $this->phone_ext,

            'mobile_country_iso2' => $this->mobile_country_iso2,
            'mobile_country_code' => $this->mobile_country_code,
            'mobile_number' => $this->mobile_number,

            'timezone' => $this->timezone,
            'signature' => $this->signature,

            'is_active' => (bool) $this->is_active,

            'role_ids' => $this->whenLoaded('roles', function () {
                return $this->roles->pluck('id')->values();
            }, []),

            'department_ids' => $this->whenLoaded('departments', function () {
                return $this->departments->pluck('id')->values();
            }, []),

            'team_ids' => $this->whenLoaded('teams', function () {
                return $this->teams->pluck('id')->values();
            }, []),
        ];
    }
}
