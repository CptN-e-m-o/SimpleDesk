<?php

namespace App\Services\Admin\System\Infrastructure\Connections;

use App\Enums\Admin\System\InfrastructureConnectionType;
use Illuminate\Validation\ValidationException;

class S3CompatibleInfrastructureConnectionAdapter extends AbstractS3InfrastructureConnectionAdapter
{
    public function type(): InfrastructureConnectionType
    {
        return InfrastructureConnectionType::S3Compatible;
    }

    protected function label(): string
    {
        return 'S3-compatible';
    }

    protected function configurationRules(): array
    {
        return ['configuration.endpoint' => ['required', 'url:http,https', 'max:2048'], 'configuration.region' => ['required', 'string', 'max:100'], 'configuration.bucket' => ['required', 'string', 'max:255'], 'configuration.use_path_style_endpoint' => ['required', 'boolean']];
    }

    protected function normalizeConfiguration(array $configuration): array
    {
        $endpoint = rtrim($configuration['endpoint'], '/');
        $parts = parse_url($endpoint);
        if (isset($parts['user']) || isset($parts['pass'])) {
            throw ValidationException::withMessages(['configuration.endpoint' => 'Credentials are not allowed in the endpoint URL.']);
        }

        return ['endpoint' => $endpoint, 'region' => trim($configuration['region']), 'bucket' => trim($configuration['bucket']), 'use_path_style_endpoint' => (bool) $configuration['use_path_style_endpoint']];
    }

    protected function disk(array $configuration, array $credentials): array
    {
        return [...$this->baseDisk($configuration, $credentials), 'endpoint' => $configuration['endpoint'], 'use_path_style_endpoint' => $configuration['use_path_style_endpoint']];
    }
}
