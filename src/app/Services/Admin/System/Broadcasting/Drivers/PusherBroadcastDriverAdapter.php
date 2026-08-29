<?php

namespace App\Services\Admin\System\Broadcasting\Drivers;

use App\Enums\Admin\System\BroadcastDriverType;
use App\Enums\Admin\System\InfrastructureConnectionType;

class PusherBroadcastDriverAdapter extends PusherProtocolBroadcastDriverAdapter
{
    public function type(): BroadcastDriverType
    {
        return BroadcastDriverType::Pusher;
    }

    protected function infrastructureType(): InfrastructureConnectionType
    {
        return InfrastructureConnectionType::Pusher;
    }

    protected function label(): string
    {
        return 'Pusher';
    }
}
