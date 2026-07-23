<?php

namespace App\Models\Admin\Mail;

use App\Enums\Mail\MailboxSubscriptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailboxSubscription extends Model
{
    protected $fillable = [
        'mailbox_channel_id',
        'subscription_type',
        'external_subscription_id',
        'status',
        'cursor',
        'expires_at',
        'last_notification_at',
        'last_renewed_at',
        'last_error_at',
        'last_error_message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => MailboxSubscriptionStatus::class,
            'expires_at' => 'immutable_datetime',
            'last_notification_at' => 'immutable_datetime',
            'last_renewed_at' => 'immutable_datetime',
            'last_error_at' => 'immutable_datetime',
            'metadata' => 'array',
        ];
    }

    public function mailboxChannel(): BelongsTo
    {
        return $this->belongsTo(MailboxChannel::class);
    }
}
