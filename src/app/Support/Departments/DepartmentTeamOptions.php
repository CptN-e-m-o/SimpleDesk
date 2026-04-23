<?php

namespace App\Support\Departments;

use App\Models\Admin\Team;

class DepartmentTeamOptions
{
    public function options()
    {
        return Team::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();
    }
}
