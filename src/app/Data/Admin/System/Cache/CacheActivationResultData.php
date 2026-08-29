<?php

namespace App\Data\Admin\System\Cache;

use App\Models\Admin\System\CacheDriverSettings;

final readonly class CacheActivationResultData
{
    public function __construct(public CacheDriverSettings $settings, public bool $forceRequested, public bool $healthOverrideUsed, public bool $restartSignaled) {}
}
