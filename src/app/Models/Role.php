<?php

namespace App\Models;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'label',
        'description',
        'type',
        'is_system',
        'is_default',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function scopeUser($query)
    {
        return $query->where('type', 'user');
    }

    public function scopeAgent($query)
    {
        return $query->where('type', 'agent');
    }
}
