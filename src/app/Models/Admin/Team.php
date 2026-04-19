<?php

namespace App\Models\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'departments',
        'is_active',
        'admin_notes',
    ];

    protected $casts = [
        'departments' => 'array',
        'is_active' => 'boolean',
    ];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user')
            ->withPivot('is_lead')
            ->withTimestamps();
    }

    public function lead(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user')
            ->withPivot('is_lead')
            ->wherePivot('is_lead', true);
    }
}
