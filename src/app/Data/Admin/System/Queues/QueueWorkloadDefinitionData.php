<?php

namespace App\Data\Admin\System\Queues;

final readonly class QueueWorkloadDefinitionData
{
    public function __construct(public string $key, public string $label, public string $description, public string $queueName, public ?string $connectionName, public bool $usesDefaultConnection, public bool $enabled = true) {}

    public function toArray(): array
    {
        return ['key' => $this->key, 'label' => $this->label, 'description' => $this->description, 'queue_name' => $this->queueName, 'connection_name' => $this->connectionName, 'uses_default_connection' => $this->usesDefaultConnection, 'enabled' => $this->enabled];
    }
}
