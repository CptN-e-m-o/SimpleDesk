<?php

namespace App\Models\Admin\Mail;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailboxChannelSyncState extends Model
{
    protected $fillable = [
        'mailbox_channel_id',
        'cursor',
        'cursor_metadata',
        'last_sync_started_at',
        'last_sync_completed_at',
        'last_sync_failed_at',
        'consecutive_failures',
        'last_fetched_count',
        'last_stored_count',
        'last_duplicate_count',
        'last_acknowledged_count',
        'last_error_code',
        'last_error_message',
    ];

    protected function casts(): array
    {
        return [
            'cursor_metadata' => 'array',
            'last_sync_started_at' => 'immutable_datetime',
            'last_sync_completed_at' => 'immutable_datetime',
            'last_sync_failed_at' => 'immutable_datetime',
            'consecutive_failures' => 'integer',
            'last_fetched_count' => 'integer',
            'last_stored_count' => 'integer',
            'last_duplicate_count' => 'integer',
            'last_acknowledged_count' => 'integer',
        ];
    }

    public function mailboxChannel(): BelongsTo
    {
        return $this->belongsTo(MailboxChannel::class);
    }
}
