<?php

namespace App\Contracts\Admin\System\Infrastructure;

use App\Data\Admin\System\Infrastructure\InfrastructureConnectionDefinitionData;
use App\Data\Admin\System\Infrastructure\InfrastructureHealthResultData;
use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Models\Admin\System\InfrastructureConnection;

interface InfrastructureConnectionAdapter
{
    public function type(): InfrastructureConnectionType;

    public function definition(): InfrastructureConnectionDefinitionData;

    public function validateAndNormalize(array $configuration, array $credentials, string $source): array;

    public function secretFields(): array;

    public function publicRepresentation(InfrastructureConnection $connection): array;

    public function test(InfrastructureConnection $connection): InfrastructureHealthResultData;
}
