<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLoginSession extends Model
{
    protected $fillable = [
        'user_id',
        'guard',
        'session_id',
        'device_type',
        'device_name',
        'platform',
        'platform_version',
        'browser',
        'browser_version',
        'ip_address',
        'country',
        'region',
        'city',
        'latitude',
        'longitude',
        'user_agent',
        'logged_in_at',
        'last_activity_at',
        'logged_out_at',
        'is_current',
        'is_successful',
    ];

    protected $casts = [
        'logged_in_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'logged_out_at' => 'datetime',
        'is_current' => 'boolean',
        'is_successful' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
