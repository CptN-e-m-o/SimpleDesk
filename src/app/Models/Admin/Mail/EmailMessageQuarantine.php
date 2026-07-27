<?php

namespace App\Models\Admin\Mail;

use App\Enums\Admin\Mail\EmailQuarantineResolution;
use App\Enums\Admin\Mail\EmailQuarantineStage;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailMessageQuarantine extends Model
{
    protected $fillable = [
        'email_message_id',
        'mailbox_id',
        'mailbox_channel_id',
        'stage',
        'reason_code',
        'reason_message',
        'exception_class',
        'attempts',
        'first_quarantined_at',
        'last_quarantined_at',
        'released_at',
        'released_by_id',
        'resolved_at',
        'resolution',
        'metadata',
    ];

    protected $casts = [
        'stage' =>
            EmailQuarantineStage::class,

        'resolution' =>
            EmailQuarantineResolution::class,

        'attempts' => 'integer',

        'first_quarantined_at' =>
            'immutable_datetime',

        'last_quarantined_at' =>
            'immutable_datetime',

        'released_at' =>
            'immutable_datetime',

        'resolved_at' =>
            'immutable_datetime',

        'metadata' => 'array',
    ];

    public function emailMessage(): BelongsTo
    {
        return $this->belongsTo(
            EmailMessage::class
        );
    }

    public function mailbox(): BelongsTo
    {
        return $this->belongsTo(
            Mailbox::class
        );
    }

    public function mailboxChannel(): BelongsTo
    {
        return $this->belongsTo(
            MailboxChannel::class
        );
    }

    public function releasedBy(): BelongsTo
    {
        return $this
            ->belongsTo(
                User::class,
                'released_by_id'
            )
            ->withTrashed();
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    public function isReleasedForRetry(): bool
    {
        return $this->released_at !== null
            && $this->resolved_at === null;
    }
}
