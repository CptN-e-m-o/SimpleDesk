<?php

namespace App\Services\Admin\System\Infrastructure\Connections;

use App\Enums\Admin\System\InfrastructureConnectionType;

class PusherInfrastructureConnectionAdapter extends PusherProtocolInfrastructureConnectionAdapter
{
    public function type(): InfrastructureConnectionType
    {
        return InfrastructureConnectionType::Pusher;
    }

    protected function label(): string
    {
        return 'Pusher';
    }
}
