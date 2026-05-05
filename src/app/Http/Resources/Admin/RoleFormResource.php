<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleFormResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'label' => $this->label,
            'description' => $this->description,
            'type' => $this->type,
            'is_system' => (bool) $this->is_system,
            'is_default' => (bool) $this->is_default,
            'permission_ids' => $this->permissions
                ->pluck('id')
                ->values()
                ->all(),
        ];
    }
}
