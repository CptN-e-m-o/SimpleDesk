<?php

namespace App\Models\Admin\System;

use App\Enums\Admin\System\InfrastructureConnectionSource;
use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Models\User\User;
use Database\Factories\Admin\InfrastructureConnectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class InfrastructureConnection extends Model
{
    /** @use HasFactory<InfrastructureConnectionFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $hidden = ['credentials'];

    protected function casts(): array
    {
        return [
            'type' => InfrastructureConnectionType::class,
            'source' => InfrastructureConnectionSource::class,
            'configuration' => 'array',
            'credentials' => 'encrypted:array',
            'is_enabled' => 'boolean',
        ];
    }

    protected static function newFactory(): InfrastructureConnectionFactory
    {
        return InfrastructureConnectionFactory::new();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function healthChecks(): HasMany
    {
        return $this->hasMany(InfrastructureConnectionHealthCheck::class);
    }

    public function latestHealthCheck(): HasOne
    {
        return $this->hasOne(InfrastructureConnectionHealthCheck::class)->latestOfMany();
    }

    public function secrets(): array
    {
        $credentials = $this->getAttribute('credentials');

        return is_array($credentials) ? $credentials : [];
    }
}
