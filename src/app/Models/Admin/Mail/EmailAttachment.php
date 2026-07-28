<?php

namespace App\Models\Admin\Mail;

use App\Enums\Admin\Mail\EmailAttachmentScanStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailAttachment extends Model
{
    protected $fillable = [
        'email_message_id',
        'position',
        'external_id',
        'deduplication_key',
        'file_name',
        'mime_type',
        'size',
        'disk',
        'path',
        'checksum_sha256',
        'content_id',
        'is_inline',
        'scan_status',
        'scan_started_at',
        'scan_attempts',
        'scanned_at',
        'scan_failure_code',
        'scan_failure_message',
        'quarantined_at',
        'scan_result',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'size' => 'integer',
            'is_inline' => 'boolean',
            'scan_status' => EmailAttachmentScanStatus::class,
            'scan_started_at' => 'immutable_datetime',
            'scan_attempts' => 'integer',
            'scanned_at' => 'immutable_datetime',
            'quarantined_at' => 'immutable_datetime',
            'scan_result' => 'array',
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
