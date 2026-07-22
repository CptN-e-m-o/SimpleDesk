<?php

namespace App\Support\Agents;

use App\Models\Role;

class AgentRoleOptions
{
    public function options(): array
    {
        return Role::query()
            ->where('type', 'agent')
            ->orderBy('label')
            ->get(['id', 'name', 'label', 'type'])
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'label' => $role->label,
                'type' => $role->type,
            ])
            ->values()
            ->all();
    }
}
