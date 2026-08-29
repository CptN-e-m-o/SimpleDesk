<?php

namespace App\Data\Admin\System\Storage;

use App\Enums\Admin\System\StorageDriverType;

final readonly class StorageDriverDefinitionData
{
    public function __construct(public StorageDriverType $driver, public string $label, public bool $available, public bool $requiresInfrastructure, public ?string $infrastructureType = null, public ?string $message = null) {}

    public function toArray(): array
    {
        return ['driver' => $this->driver->value, 'label' => $this->label, 'available' => $this->available, 'requires_infrastructure' => $this->requiresInfrastructure, 'infrastructure_type' => $this->infrastructureType, 'message' => $this->message];
    }
}
