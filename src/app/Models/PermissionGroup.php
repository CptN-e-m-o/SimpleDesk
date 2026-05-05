<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermissionGroup extends Model
{
    protected $fillable = [
        'key',
        'label',
        'panel',
        'type',
        'sort_order',
    ];

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }
}
