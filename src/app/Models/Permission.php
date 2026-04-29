<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = [
        'permission_group_id',
        'parent_id',
        'key',
        'label',
        'type',
        'ui_type',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function scopeUser($query)
    {
        return $query->where('type', 'user');
    }

    public function scopeAgent($query)
    {
        return $query->where('type', 'agent');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(PermissionGroup::class, 'permission_group_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'parent_id');
    }
}
