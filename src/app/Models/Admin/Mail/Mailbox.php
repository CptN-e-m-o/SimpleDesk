<?php

namespace App\Models\Admin\Mail;

use App\Enums\Mail\MailboxChannelDirection;
use App\Models\Admin\Department;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mailbox extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email_address',
        'display_name',
        'department_id',
        'is_active',
        'is_default_outgoing',
        'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default_outgoing' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function channels(): HasMany
    {
        return $this->hasMany(MailboxChannel::class);
    }

    public function incomingChannels(): HasMany
    {
        return $this
            ->hasMany(MailboxChannel::class)
            ->where(
                'direction',
                MailboxChannelDirection::Incoming->value
            );
    }

    public function outgoingChannels(): HasMany
    {
        return $this
            ->hasMany(MailboxChannel::class)
            ->where(
                'direction',
                MailboxChannelDirection::Outgoing->value
            );
    }

    public function primaryIncomingChannel(): HasOne
    {
        return $this
            ->hasOne(MailboxChannel::class)
            ->where(
                'direction',
                MailboxChannelDirection::Incoming->value
            )
            ->where('is_primary', true);
    }

    public function primaryOutgoingChannel(): HasOne
    {
        return $this
            ->hasOne(MailboxChannel::class)
            ->where(
                'direction',
                MailboxChannelDirection::Outgoing->value
            )
            ->where('is_primary', true);
    }

    public function emailMessages(): HasMany
    {
        return $this->hasMany(EmailMessage::class);
    }
}
