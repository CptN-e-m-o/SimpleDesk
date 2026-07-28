<?php

namespace App\Models\Admin\Mail;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailAttachmentRejection extends Model
{
    protected $fillable = [
        'email_message_id',
        'position',
        'external_id',
        'deduplication_key',
        'file_name',
        'mime_type',
        'reported_size',
        'content_id',
        'is_inline',
        'reason_code',
        'reason_message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'reported_size' => 'integer',
            'is_inline' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function emailMessage(): BelongsTo
    {
        return $this->belongsTo(
            EmailMessage::class
        );
    }
}
