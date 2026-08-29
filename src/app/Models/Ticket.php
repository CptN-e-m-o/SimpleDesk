<?php

namespace App\Models;

use App\Models\Admin\Department;
use App\Models\Admin\Mail\EmailAttachment;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\Mailbox;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'requester_id',
        'category_id',
        'assignee_id',
        'mailbox_id',
        'department_id',
        'subject',
        'priority_id',
        'ticket_type_id',
        'status',
        'source',
        'service',
        'description',
        'last_reply_at',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'last_reply_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_WAITING_FOR_CUSTOMER = 'waiting_for_customer';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    public const SOURCE_PORTAL = 'portal';

    public const SOURCE_EMAIL = 'email';

    public const SOURCE_API = 'api';

    public static function statuses(): array
    {
        return [
            self::STATUS_OPEN,
            self::STATUS_IN_PROGRESS,
            self::STATUS_WAITING_FOR_CUSTOMER,
            self::STATUS_RESOLVED,
            self::STATUS_CLOSED,
        ];
    }

    public static function statusOptions(): array
    {
        return [
            [
                'value' => '',
                'label' => 'All statuses',
            ],
            [
                'value' => self::STATUS_OPEN,
                'label' => 'Open',
            ],
            [
                'value' => self::STATUS_IN_PROGRESS,
                'label' => 'In Progress',
            ],
            [
                'value' => self::STATUS_WAITING_FOR_CUSTOMER,
                'label' => 'Waiting for Customer',
            ],
            [
                'value' => self::STATUS_RESOLVED,
                'label' => 'Resolved',
            ],
            [
                'value' => self::STATUS_CLOSED,
                'label' => 'Closed',
            ],
        ];
    }

    public static function statusLabel(
        string $status
    ): string {
        return match ($status) {
            self::STATUS_OPEN => 'Open',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_WAITING_FOR_CUSTOMER => 'Waiting for Customer',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_CLOSED => 'Closed',
            default => $status,
        };
    }

    public static function sources(): array
    {
        return [
            self::SOURCE_PORTAL,
            self::SOURCE_EMAIL,
            self::SOURCE_API,
        ];
    }

    public function requester(): BelongsTo
    {
        return $this
            ->belongsTo(
                User::class,
                'requester_id'
            )
            ->withTrashed();
    }

    public function assignee(): BelongsTo
    {
        return $this
            ->belongsTo(
                User::class,
                'assignee_id'
            )
            ->withTrashed();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(
            TicketCategory::class,
            'category_id'
        );
    }

    public function mailbox(): BelongsTo
    {
        return $this->belongsTo(
            Mailbox::class
        );
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(
            Department::class
        );
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(TicketPriority::class, 'priority_id')->withTrashed();
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class)->withTrashed();
    }

    public function replies(): HasMany
    {
        return $this->hasMany(
            TicketReply::class
        );
    }

    public function emailMessages(): HasMany
    {
        return $this->hasMany(
            EmailMessage::class
        );
    }

    public function emailAttachments(): HasManyThrough
    {
        return $this
            ->hasManyThrough(
                EmailAttachment::class,
                EmailMessage::class,
                'ticket_id',
                'email_message_id',
                'id',
                'id',
            )
            ->orderBy(
                'email_attachments.position'
            );
    }
}
