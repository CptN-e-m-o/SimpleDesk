<?php

namespace App\Data\Admin\System\Queues;

final readonly class QueueRuntimeConfigurationData
{
    public function __construct(
        public array $queueConnection,
        public array $redisConnections = [],
    ) {}
}
