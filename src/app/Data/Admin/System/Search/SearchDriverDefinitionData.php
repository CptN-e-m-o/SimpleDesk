<?php

namespace App\Data\Admin\System\Search;

use App\Enums\Admin\System\SearchDriverType;

final readonly class SearchDriverDefinitionData
{
    public function __construct(public SearchDriverType $type, public string $label, public string $description, public bool $available, public bool $requiresInfrastructureConnection) {}

    public function toArray(): array
    {
        return ['type' => $this->type->value, 'label' => $this->label, 'description' => $this->description, 'available' => $this->available, 'requires_infrastructure_connection' => $this->requiresInfrastructureConnection];
    }
}
