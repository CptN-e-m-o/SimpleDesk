<?php

namespace App\Models\Admin\Mail;

use App\Enums\Admin\Mail\MailAdminAuditEvent;
use App\Enums\Admin\Mail\MailAdminAuditStatus;
use App\Enums\Admin\Mail\MailAdminAuditSubjectType;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailAdminAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'actor_id',
        'mailbox_id',
        'event',
        'status',
        'subject_type',
        'subject_id',
        'request_id',
        'ip_address',
        'user_agent',
        'context',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'event' => MailAdminAuditEvent::class,
            'status' => MailAdminAuditStatus::class,
            'subject_type' => MailAdminAuditSubjectType::class,
            'subject_id' => 'integer',
            'context' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this
            ->belongsTo(User::class, 'actor_id')
            ->withTrashed();
    }

    public function mailbox(): BelongsTo
    {
        return $this
            ->belongsTo(Mailbox::class)
            ->withTrashed();
    }
}
