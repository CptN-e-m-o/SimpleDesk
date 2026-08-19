<?php

namespace App\Models\Admin\System;

use App\Enums\Admin\System\CacheHealthStatus;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CacheDriverHealthCheck extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['status' => CacheHealthStatus::class, 'details' => 'array']; }
    public function configuration(): BelongsTo { return $this->belongsTo(CacheDriverConfiguration::class); }
    public function testedBy(): BelongsTo { return $this->belongsTo(User::class, 'tested_by'); }
}
