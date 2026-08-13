<?php

namespace App\Data\Admin\System\Queues;

use App\Enums\Admin\System\QueueDriverType;

final readonly class QueueDriverDefinitionData
{
    public function __construct(
        public QueueDriverType $type,
        public string $label,
        public string $description,
        public bool $recommendedForProduction = true,
        public array $options = [],
    ) {}
}
