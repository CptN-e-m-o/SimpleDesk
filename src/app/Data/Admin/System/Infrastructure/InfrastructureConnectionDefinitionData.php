<?php

namespace App\Data\Admin\System\Infrastructure;

use App\Enums\Admin\System\InfrastructureConnectionSource;
use App\Enums\Admin\System\InfrastructureConnectionType;

final readonly class InfrastructureConnectionDefinitionData
{
    public function __construct(
        public InfrastructureConnectionType $type,
        public string $label,
        public string $description,
        public array $sources,
        public bool $available,
        public array $options = [],
    ) {}

    public function toArray(): array
    {
        return ['type' => $this->type->value, 'label' => $this->label, 'description' => $this->description, 'sources' => array_map(fn (InfrastructureConnectionSource $source) => $source->value, $this->sources), 'available' => $this->available, 'options' => $this->options];
    }
}
