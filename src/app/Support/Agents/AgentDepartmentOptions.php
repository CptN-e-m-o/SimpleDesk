<?php

namespace App\Support\Agents;

use App\Models\Admin\Department;

class AgentDepartmentOptions
{
    public function options(): array
    {
        return Department::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Department $department) => [
                'id' => $department->id,
                'name' => $department->name,
            ])
            ->values()
            ->all();
    }
}
