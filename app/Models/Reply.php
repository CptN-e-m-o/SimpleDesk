<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Reply extends Model
{
    protected $fillable = [
        'body',
        'ticket_id',
        'author_id',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function getLocalizedCreatedAtAttribute(): string
    {
        if (Auth::check() && Auth::user()->timezone) {
            return $this->created_at->setTimezone(Auth::user()->timezone)->format('d.m.Y H:i');
        }

        return $this->created_at->format('d.m.Y H:i').' UTC';
    }
}
