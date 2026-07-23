<?php

namespace App\Models\Admin\Mail;

use App\Enums\Mail\EmailMessageDirection;
use App\Enums\Mail\EmailMessageStatus;
use App\Enums\Mail\MailboxDriver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailMessage extends Model
{
    protected $fillable = [
        'mailbox_id',
        'mailbox_channel_id',
        'ticket_id',
        'ticket_reply_id',
        'direction',
        'driver',
        'status',
        'idempotency_key',
        'external_message_id',
        'internet_message_id',
        'in_reply_to_message_id',
        'reference_message_ids',
        'sender_address',
        'sender_name',
        'to_recipients',
        'cc_recipients',
        'bcc_recipients',
        'reply_to_recipients',
        'subject',
        'text_body',
        'html_body',
        'headers',
        'metadata',
        'raw_message_disk',
        'raw_message_path',
        'received_at',
        'queued_at',
        'processing_started_at',
        'processed_at',
        'sent_at',
        'delivered_at',
        'failed_at',
        'failure_code',
        'failure_message',
    ];

    protected function casts(): array
    {
        return [
            'direction' => EmailMessageDirection::class,
            'driver' => MailboxDriver::class,
            'status' => EmailMessageStatus::class,
            'reference_message_ids' => 'array',
            'to_recipients' => 'array',
            'cc_recipients' => 'array',
            'bcc_recipients' => 'array',
            'reply_to_recipients' => 'array',
            'headers' => 'array',
            'metadata' => 'array',
            'received_at' => 'immutable_datetime',
            'queued_at' => 'immutable_datetime',
            'processing_started_at' => 'immutable_datetime',
            'processed_at' => 'immutable_datetime',
            'sent_at' => 'immutable_datetime',
            'delivered_at' => 'immutable_datetime',
            'failed_at' => 'immutable_datetime',
        ];
    }

    public function mailbox(): BelongsTo
    {
        return $this->belongsTo(Mailbox::class);
    }

    public function mailboxChannel(): BelongsTo
    {
        return $this->belongsTo(MailboxChannel::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function ticketReply(): BelongsTo
    {
        return $this->belongsTo(TicketReply::class);
    }

    public function webhookEvents(): HasMany
    {
        return $this->hasMany(EmailWebhookEvent::class);
    }
}
