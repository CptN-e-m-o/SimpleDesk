<?php

namespace App\Models;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'requester_id',
        'category_id',
        'assignee_id',
        'subject',
        'priority',
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

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

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

    public static function priorities(): array
    {
        return [
            self::PRIORITY_LOW,
            self::PRIORITY_MEDIUM,
            self::PRIORITY_HIGH,
            self::PRIORITY_URGENT,
        ];
    }

    public static function statusOptions(): array
    {
        return [
            ['value' => '', 'label' => 'All statuses'],
            ['value' => self::STATUS_OPEN, 'label' => 'Open'],
            ['value' => self::STATUS_IN_PROGRESS, 'label' => 'In Progress'],
            ['value' => self::STATUS_WAITING_FOR_CUSTOMER, 'label' => 'Waiting for Customer'],
            ['value' => self::STATUS_RESOLVED, 'label' => 'Resolved'],
            ['value' => self::STATUS_CLOSED, 'label' => 'Closed'],
        ];
    }

    public static function priorityOptions(): array
    {
        return [
            ['value' => '', 'label' => 'All priorities'],
            ['value' => self::PRIORITY_LOW, 'label' => 'Low'],
            ['value' => self::PRIORITY_MEDIUM, 'label' => 'Medium'],
            ['value' => self::PRIORITY_HIGH, 'label' => 'High'],
            ['value' => self::PRIORITY_URGENT, 'label' => 'Urgent'],
        ];
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_OPEN => 'Open',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_WAITING_FOR_CUSTOMER => 'Waiting for Customer',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_CLOSED => 'Closed',
            default => $status,
        };
    }

    public static function priorityLabel(string $priority): string
    {
        return match ($priority) {
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_MEDIUM => 'Medium',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent',
            default => $priority,
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
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class);
    }
}
