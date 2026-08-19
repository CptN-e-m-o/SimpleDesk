<?php

namespace App\Data\Admin\System\Cache;

use App\Enums\Admin\System\CacheDriverType;

final readonly class CacheDriverDefinitionData
{
    public function __construct(public CacheDriverType $type, public string $label, public string $description, public bool $requiresInfrastructure, public ?string $infrastructureType, public bool $recommendedForProduction, public bool $available = true, public ?string $unavailableReason = null, public array $options = []) {}

    public function toArray(): array
    {
        return ['type' => $this->type->value, 'label' => $this->label, 'description' => $this->description, 'requires_infrastructure' => $this->requiresInfrastructure, 'infrastructure_type' => $this->infrastructureType, 'recommended_for_production' => $this->recommendedForProduction, 'available' => $this->available, 'unavailable_reason' => $this->unavailableReason, 'options' => $this->options];
    }
}
