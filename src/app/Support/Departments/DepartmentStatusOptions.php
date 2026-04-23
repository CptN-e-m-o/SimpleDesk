<?php

namespace App\Support\Departments;

use App\Models\Admin\DepartmentStatus;

class DepartmentStatusOptions
{
    public function options()
    {
        return DepartmentStatus::query()
            ->select(['id', 'name', 'code', 'color'])
            ->orderBy('name')
            ->get();
    }
}
