<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    const SORTABLE = ['login', 'email', 'last_name', 'created_at'];

    protected $fillable = [
        'first_name',
        'login',
        'email',
        'password',
        'role_id',
        'last_name',
        'patronymic',
        'avatar',
        'timezone',
        'phone_number',
        'phone_verified_at',
        'signature',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google2fa_secret',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'role_id' => UserRole::class,
        ];
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'user_id');
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_agent_id');
    }

    public function isAdminOrAgent(): bool
    {
        return in_array($this->role_id, [UserRole::Admin, UserRole::Agent]);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role_id, [UserRole::Admin]);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Reply::class, 'author_id');
    }

    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar
            ? asset('storage/'.$this->avatar)
            : asset('images/default-avatar.png');
    }

    public function loginHistories()
    {
        return $this->hasMany(LoginHistory::class);
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => implode(' ', array_filter([
                $this->last_name,
                $this->first_name,
                $this->patronymic,
            ]))
        );
    }

    public function scopeAgents($query)
    {
        return $query->whereIn('role_id', [UserRole::Agent, UserRole::Admin]);
    }

    public function scopeSort($query, $column, $direction)
    {
        return $query->orderBy($column, $direction);
    }
}
