<?php

namespace App\Services\Admin\System\Infrastructure\Connections;

use App\Enums\Admin\System\InfrastructureConnectionType;

class AwsInfrastructureConnectionAdapter extends AbstractS3InfrastructureConnectionAdapter
{
    public function type(): InfrastructureConnectionType
    {
        return InfrastructureConnectionType::Aws;
    }

    protected function label(): string
    {
        return 'Amazon S3';
    }

    protected function configurationRules(): array
    {
        return ['configuration.region' => ['required', 'string', 'max:100'], 'configuration.bucket' => ['required', 'string', 'max:255']];
    }

    protected function normalizeConfiguration(array $configuration): array
    {
        return ['region' => trim($configuration['region']), 'bucket' => trim($configuration['bucket'])];
    }

    protected function disk(array $configuration, array $credentials): array
    {
        return $this->baseDisk($configuration, $credentials);
    }
}
