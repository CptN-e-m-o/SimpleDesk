<?php

namespace App\Models\Admin\System;

use App\Enums\Admin\System\SearchDriverType;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class SearchDriverConfiguration extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['driver' => SearchDriverType::class, 'configuration' => 'array', 'is_enabled' => 'boolean'];
    }

    public function infrastructureConnection(): BelongsTo
    {
        return $this->belongsTo(InfrastructureConnection::class)->withTrashed();
    }

    public function healthChecks(): HasMany
    {
        return $this->hasMany(SearchDriverHealthCheck::class);
    }

    public function latestHealthCheck(): HasOne
    {
        return $this->hasOne(SearchDriverHealthCheck::class)->latestOfMany();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
