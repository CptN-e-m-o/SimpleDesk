<?php

namespace App\Services\Admin\System\Infrastructure\Connections;

use App\Enums\Admin\System\InfrastructureConnectionType;

class ReverbInfrastructureConnectionAdapter extends PusherProtocolInfrastructureConnectionAdapter
{
    public function type(): InfrastructureConnectionType
    {
        return InfrastructureConnectionType::Reverb;
    }

    protected function label(): string
    {
        return 'Reverb endpoint';
    }
}
