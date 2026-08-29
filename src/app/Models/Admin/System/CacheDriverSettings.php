<?php

namespace App\Models\Admin\System;

use App\Enums\Admin\System\CacheConfigurationMode;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CacheDriverSettings extends Model
{
    public const SINGLETON_ID = 1;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['mode' => CacheConfigurationMode::class, 'activated_at' => 'immutable_datetime'];
    }

    public function activeConfiguration(): BelongsTo
    {
        return $this->belongsTo(CacheDriverConfiguration::class, 'active_configuration_id')->withTrashed();
    }

    public function activatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by');
    }
}
