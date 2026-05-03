<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
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
            'is_deleted' => $this->trashed(),
            'deleted_at' => $this->deleted_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->format('F d, Y h:i a'),
        ];
    }
}
