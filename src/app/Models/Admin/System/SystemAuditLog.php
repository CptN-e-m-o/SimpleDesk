<?php

namespace App\Models\Admin\System;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class SystemAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'actor_id',
        'area',
        'action',
        'subject_type',
        'subject_id',
        'before_state',
        'after_state',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'before_state' => 'array',
            'after_state' => 'array',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(
            static function (): never {
                throw new LogicException(
                    'System audit log entries are immutable and cannot be updated.',
                );
            },
        );

        static::deleting(
            static function (): never {
                throw new LogicException(
                    'System audit log entries are append-only and cannot be deleted.',
                );
            },
        );
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'actor_id',
        );
    }
}
