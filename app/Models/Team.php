<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'owner_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'team_user')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function isOwner(?User $user): bool
    {
        return $user && $this->owner_id === $user->id;
    }

    public function isAdmin(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->members()
            ->where('user_id', $user->id)
            ->wherePivot('role', 'admin')
            ->exists();
    }

    public function canBeManagedBy(?User $user): bool
    {
        return $this->isOwner($user) || $this->isAdmin($user);
    }

    public function getUserRole(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        if ($this->isOwner($user)) {
            return 'owner';
        }

        return $this->members()
            ->where('user_id', $user->id)
            ->first()
            ?->pivot
            ->role;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOwnedBy($query, ?User $user)
    {
        return $query->where('owner_id', $user?->id);
    }
}
