<?php

namespace App\Data\Admin\System\Broadcasting;

readonly class BroadcastRuntimeConfigurationData
{
    public function __construct(public array $connection, public array $client) {}
}
