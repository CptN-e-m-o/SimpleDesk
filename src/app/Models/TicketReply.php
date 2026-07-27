<?php

namespace App\Models;

use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Models\Admin\Mail\EmailAttachment;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TicketReply extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'message',
        'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(
            Ticket::class
        );
    }

    public function user(): BelongsTo
    {
        return $this
            ->belongsTo(
                User::class
            )
            ->withTrashed();
    }

    public function emailMessages(): HasMany
    {
        return $this->hasMany(
            EmailMessage::class
        );
    }

    public function incomingEmailMessage(): HasOne
    {
        return $this
            ->hasOne(
                EmailMessage::class
            )
            ->where(
                'direction',
                EmailMessageDirection::Incoming->value
            );
    }

    public function outgoingEmailMessage(): HasOne
    {
        return $this
            ->hasOne(
                EmailMessage::class
            )
            ->where(
                'direction',
                EmailMessageDirection::Outgoing->value
            );
    }

    public function emailAttachments(): HasManyThrough
    {
        return $this
            ->hasManyThrough(
                EmailAttachment::class,
                EmailMessage::class,
                'ticket_reply_id',
                'email_message_id',
                'id',
                'id',
            )
            ->orderBy(
                'email_attachments.position'
            );
    }

    public function cameFromIncomingEmail(): bool
    {
        if (
            $this->relationLoaded(
                'incomingEmailMessage'
            )
        ) {
            return $this->incomingEmailMessage !== null;
        }

        return $this
            ->incomingEmailMessage()
            ->exists();
    }

    public function canBeSentByEmail(): bool
    {
        if ($this->is_internal) {
            return false;
        }

        return !$this->cameFromIncomingEmail();
    }
}
