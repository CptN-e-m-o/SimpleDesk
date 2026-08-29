<?php

namespace App\Models\Admin\System;

use App\Enums\Admin\System\StorageHealthStatus;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorageDriverHealthCheck extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['status' => StorageHealthStatus::class, 'details' => 'array'];
    }

    public function configuration(): BelongsTo
    {
        return $this->belongsTo(StorageDriverConfiguration::class, 'storage_driver_configuration_id')->withTrashed();
    }

    public function testedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tested_by');
    }
}
