<?php

namespace App\Services\Admin\System\Search;

class SearchDeploymentConfigurationSnapshot
{
    private ?array $configuration = null;

    public function capture(array $configuration): void
    {
        $this->configuration ??= $configuration;
    }

    public function configuration(): array
    {
        return $this->configuration ?? [];
    }
}
