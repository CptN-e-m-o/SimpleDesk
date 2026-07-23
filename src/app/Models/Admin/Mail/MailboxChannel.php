<?php

namespace App\Models\Admin\Mail;

use App\Enums\Mail\MailAuthenticationType;
use App\Enums\Mail\MailboxChannelDirection;
use App\Enums\Mail\MailboxDriver;
use App\Enums\Mail\MailboxHealthStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MailboxChannel extends Model
{
    protected $fillable = [
        'mailbox_id',
        'provider_connection_id',
        'name',
        'direction',
        'driver',
        'auth_type',
        'is_enabled',
        'is_primary',
        'failover_order',
        'configuration',
        'secret_configuration',
        'health_status',
        'last_checked_at',
        'last_success_at',
        'last_activity_at',
        'last_error_at',
        'last_error_code',
        'last_error_message',
    ];

    protected $hidden = [
        'secret_configuration',
    ];

    protected function casts(): array
    {
        return [
            'direction' => MailboxChannelDirection::class,
            'driver' => MailboxDriver::class,
            'auth_type' => MailAuthenticationType::class,
            'is_enabled' => 'boolean',
            'is_primary' => 'boolean',
            'failover_order' => 'integer',
            'configuration' => 'array',
            'secret_configuration' => 'encrypted:array',
            'health_status' => MailboxHealthStatus::class,
            'last_checked_at' => 'immutable_datetime',
            'last_success_at' => 'immutable_datetime',
            'last_activity_at' => 'immutable_datetime',
            'last_error_at' => 'immutable_datetime',
        ];
    }

    public function mailbox(): BelongsTo
    {
        return $this->belongsTo(Mailbox::class);
    }

    public function providerConnection(): BelongsTo
    {
        return $this->belongsTo(
            MailProviderConnection::class,
            'provider_connection_id'
        );
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(MailboxSubscription::class);
    }

    public function emailMessages(): HasMany
    {
        return $this->hasMany(EmailMessage::class);
    }

    public function webhookEvents(): HasMany
    {
        return $this->hasMany(EmailWebhookEvent::class);
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    public function scopeIncoming(Builder $query): Builder
    {
        return $query->where(
            'direction',
            MailboxChannelDirection::Incoming->value
        );
    }

    public function scopeOutgoing(Builder $query): Builder
    {
        return $query->where(
            'direction',
            MailboxChannelDirection::Outgoing->value
        );
    }

    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }
}
