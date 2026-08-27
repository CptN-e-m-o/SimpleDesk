<?php

namespace App\Models\Admin\System;

use App\Enums\Admin\System\SearchConfigurationMode;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchDriverSettings extends Model
{
    public const SINGLETON_ID = 1;

    public $incrementing = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['mode' => SearchConfigurationMode::class, 'activated_at' => 'datetime'];
    }

    public function activeConfiguration(): BelongsTo
    {
        return $this->belongsTo(SearchDriverConfiguration::class, 'active_configuration_id')->withTrashed();
    }

    public function activatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by');
    }
}
