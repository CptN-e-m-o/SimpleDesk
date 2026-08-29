<?php

namespace Tests\Fakes\Admin\System;

use App\Contracts\Admin\System\Infrastructure\InfrastructureConnectionAdapter;
use App\Data\Admin\System\Infrastructure\InfrastructureConnectionDefinitionData;
use App\Data\Admin\System\Infrastructure\InfrastructureHealthResultData;
use App\Enums\Admin\System\InfrastructureConnectionSource;
use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Enums\Admin\System\InfrastructureHealthStatus;
use App\Models\Admin\System\InfrastructureConnection;

class FakeInfrastructureConnectionAdapter implements InfrastructureConnectionAdapter
{
    public InfrastructureHealthResultData $result;

    public function __construct()
    {
        $this->result = new InfrastructureHealthResultData(InfrastructureHealthStatus::Healthy, 3, 'Fake connection verified.');
    }

    public function type(): InfrastructureConnectionType
    {
        return InfrastructureConnectionType::Redis;
    }

    public function definition(): InfrastructureConnectionDefinitionData
    {
        return new InfrastructureConnectionDefinitionData($this->type(), 'Fake Redis', 'Test adapter', [InfrastructureConnectionSource::Managed], true);
    }

    public function validateAndNormalize(array $configuration, array $credentials, string $source): array
    {
        return ['configuration' => $configuration, 'credentials' => array_filter($credentials, fn ($value) => $value !== '')];
    }

    public function secretFields(): array
    {
        return ['password'];
    }

    public function publicRepresentation(InfrastructureConnection $connection): array
    {
        return ['configuration' => $connection->configuration ?? [], 'credential_flags' => ['password_configured' => isset($connection->secrets()['password'])]];
    }

    public function test(InfrastructureConnection $connection): InfrastructureHealthResultData
    {
        return $this->result;
    }
}
