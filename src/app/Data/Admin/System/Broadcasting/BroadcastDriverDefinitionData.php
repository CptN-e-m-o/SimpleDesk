<?php

namespace App\Data\Admin\System\Broadcasting;

use App\Enums\Admin\System\BroadcastDriverType;

readonly class BroadcastDriverDefinitionData
{
    public function __construct(
        public BroadcastDriverType $type,
        public string $name,
        public string $description,
        public bool $available,
        public ?string $unavailableReason = null,
    ) {}

    public function toArray(): array
    {
        return ['type' => $this->type->value, 'name' => $this->name, 'description' => $this->description, 'available' => $this->available, 'unavailable_reason' => $this->unavailableReason];
    }
}
