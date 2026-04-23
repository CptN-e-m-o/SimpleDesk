<?php

namespace App\Support\Departments;

use App\Models\User;

class DepartmentUserOptions
{
    public function options()
    {
        return User::query()
            ->select(['id', 'name', 'email'])
            ->orderBy('name')
            ->get();
    }
}
