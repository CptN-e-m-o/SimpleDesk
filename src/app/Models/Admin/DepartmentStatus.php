<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepartmentStatus extends Model
{
    protected $fillable = [
        'name',
        'code',
        'color',
        'icon',
    ];

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class, 'department_status_id');
    }
}
