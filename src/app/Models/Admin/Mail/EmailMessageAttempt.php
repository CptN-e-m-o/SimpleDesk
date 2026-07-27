<?php

namespace App\Models\Admin\Mail;

use App\Enums\Mail\EmailMessageAttemptStatus;
use App\Enums\Mail\MailboxDriver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailMessageAttempt extends Model
{
    protected $fillable = [
        'email_message_id',
        'mailbox_channel_id',
        'attempt_number',
        'driver',
        'status',
        'external_message_id',
        'internet_message_id',
        'accepted_recipients',
        'rejected_recipients',
        'provider_response',
        'retryable',
        'failover_allowed',
        'error_class',
        'error_code',
        'error_message',
        'metadata',
        'started_at',
        'completed_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'driver' => MailboxDriver::class,
            'status' => EmailMessageAttemptStatus::class,
            'accepted_recipients' => 'array',
            'rejected_recipients' => 'array',
            'provider_response' => 'array',
            'retryable' => 'boolean',
            'failover_allowed' => 'boolean',
            'metadata' => 'array',
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'failed_at' => 'immutable_datetime',
        ];
    }

    public function emailMessage(): BelongsTo
    {
        return $this->belongsTo(EmailMessage::class);
    }

    public function mailboxChannel(): BelongsTo
    {
        return $this->belongsTo(MailboxChannel::class);
    }
}
