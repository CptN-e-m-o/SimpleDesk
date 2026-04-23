<?php

namespace App\Support\Teams;

use App\Models\Admin\Department;

class TeamDepartmentOptions
{
    public function options(): array
    {
        return Department::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Department $department) => [
                'id' => (int) $department->id,
                'name' => $department->name,
            ])
            ->all();
    }

    public function ids(): array
    {
        return Department::query()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
