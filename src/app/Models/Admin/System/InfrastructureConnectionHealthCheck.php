<?php

namespace App\Models\Admin\System;

use App\Enums\Admin\System\InfrastructureHealthStatus;
use App\Enums\Admin\System\InfrastructureHealthTrigger;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfrastructureConnectionHealthCheck extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['status' => InfrastructureHealthStatus::class, 'trigger' => InfrastructureHealthTrigger::class, 'details' => 'array'];
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(InfrastructureConnection::class, 'infrastructure_connection_id');
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
