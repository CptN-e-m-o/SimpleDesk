<?php

namespace App\Data\Admin\System\Queues;

use App\Enums\Admin\System\QueueDriverType;

final readonly class QueueDriverDefinitionData
{
    public function __construct(
        public QueueDriverType $type,
        public string $label,
        public string $description,
        public bool $requiresInfrastructure = false,
        public ?string $infrastructureType = null,
        public bool $recommendedForProduction = true,
        public array $options = [],
    ) {}

    public function toArray(): array
    {
        return [
            'type' =>
                $this->type->value,

            'label' =>
                $this->label,

            'description' =>
                $this->description,

            'requires_infrastructure' =>
                $this->requiresInfrastructure,

            'infrastructure_type' =>
                $this->infrastructureType,

            'recommended_for_production' =>
                $this->recommendedForProduction,

            'options' =>
                $this->options,
        ];
    }
}
