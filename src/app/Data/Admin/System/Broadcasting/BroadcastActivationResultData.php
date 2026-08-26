<?php

namespace App\Data\Admin\System\Broadcasting;

use App\Models\Admin\System\BroadcastDriverSettings;

readonly class BroadcastActivationResultData
{
    public function __construct(public BroadcastDriverSettings $settings, public bool $forceRequested, public bool $healthOverrideUsed, public bool $restartSignaled) {}
}
