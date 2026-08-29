<?php

namespace App\Models\Admin\System;

use App\Enums\Admin\System\StorageDriverType;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class StorageDriverConfiguration extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['driver' => StorageDriverType::class, 'configuration' => 'array', 'is_enabled' => 'boolean'];
    }

    public function infrastructureConnection(): BelongsTo
    {
        return $this->belongsTo(InfrastructureConnection::class)->withTrashed();
    }

    public function healthChecks(): HasMany
    {
        return $this->hasMany(StorageDriverHealthCheck::class);
    }

    public function latestHealthCheck(): HasOne
    {
        return $this->hasOne(StorageDriverHealthCheck::class)->latestOfMany();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
