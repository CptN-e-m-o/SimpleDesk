<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Ticket extends Authenticatable
{
    protected $fillable = [
        'title',
        'description',
        'user_id',
        'assigned_agent_id',
        'status_id',
        'priority_id',
    ];

    protected function casts(): array
    {
        return [
            'status_id' => TicketStatus::class,
            'priority_id' => TicketPriority::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Reply::class)->orderBy('created_at', 'asc');
    }
}
