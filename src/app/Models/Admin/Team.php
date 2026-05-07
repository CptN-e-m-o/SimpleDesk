<?php

namespace App\Models\Admin;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'admin_notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class)
            ->withTimestamps();
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user')
            ->withPivot('is_lead')
            ->withTimestamps();
    }

    public function leads(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user')
            ->withPivot('is_lead')
            ->wherePivot('is_lead', true)
            ->withTimestamps();
    }
}
