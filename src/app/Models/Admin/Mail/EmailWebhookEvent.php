<?php

namespace App\Models\Admin\Mail;

use App\Enums\Mail\EmailWebhookEventStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailWebhookEvent extends Model
{
    protected $fillable = [
        'mailbox_channel_id',
        'email_message_id',
        'provider',
        'event_type',
        'external_event_id',
        'idempotency_key',
        'signature_verified',
        'payload',
        'status',
        'attempts',
        'received_at',
        'processing_started_at',
        'processed_at',
        'failed_at',
        'last_error_message',
    ];

    protected function casts(): array
    {
        return [
            'signature_verified' => 'boolean',
            'payload' => 'array',
            'status' => EmailWebhookEventStatus::class,
            'attempts' => 'integer',
            'received_at' => 'immutable_datetime',
            'processing_started_at' => 'immutable_datetime',
            'processed_at' => 'immutable_datetime',
            'failed_at' => 'immutable_datetime',
        ];
    }

    public function mailboxChannel(): BelongsTo
    {
        return $this->belongsTo(MailboxChannel::class);
    }

    public function emailMessage(): BelongsTo
    {
        return $this->belongsTo(EmailMessage::class);
    }
}
