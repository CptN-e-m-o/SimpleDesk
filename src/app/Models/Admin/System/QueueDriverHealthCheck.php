<?php

namespace App\Models\Admin\System;

use App\Enums\Admin\System\QueueHealthStatus;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueDriverHealthCheck extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['status' => QueueHealthStatus::class, 'details' => 'array'];
    }

    public function configuration(): BelongsTo
    {
        return $this->belongsTo(QueueDriverConfiguration::class, 'queue_driver_configuration_id');
    }

    public function testedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tested_by');
    }
}
