<?php

namespace App\Services\Admin\System\Broadcasting\Drivers;

use App\Enums\Admin\System\BroadcastDriverType;
use App\Enums\Admin\System\InfrastructureConnectionType;

class ReverbBroadcastDriverAdapter extends PusherProtocolBroadcastDriverAdapter
{
    public function type(): BroadcastDriverType
    {
        return BroadcastDriverType::Reverb;
    }

    protected function infrastructureType(): InfrastructureConnectionType
    {
        return InfrastructureConnectionType::Reverb;
    }

    protected function label(): string
    {
        return 'Reverb';
    }
}
