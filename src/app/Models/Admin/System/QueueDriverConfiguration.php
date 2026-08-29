<?php

namespace App\Models\Admin\System;

use App\Enums\Admin\System\QueueDriverType;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class QueueDriverConfiguration extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'driver' => QueueDriverType::class,
            'configuration' => 'array',
            'infrastructure_connection_id' => 'integer',
            'is_enabled' => 'boolean',
        ];
    }

    public function infrastructureConnection(): BelongsTo
    {
        return $this
            ->belongsTo(
                InfrastructureConnection::class,
                'infrastructure_connection_id',
            )
            ->withTrashed();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by',
        );
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by',
        );
    }

    public function settings(): HasMany
    {
        return $this->hasMany(
            QueueDriverSettings::class,
            'active_configuration_id',
        );
    }

    public function healthChecks(): HasMany
    {
        return $this->hasMany(
            QueueDriverHealthCheck::class,
        );
    }

    public function latestHealthCheck(): HasOne
    {
        return $this
            ->hasOne(
                QueueDriverHealthCheck::class,
            )
            ->latestOfMany();
    }
}
