<?php

namespace App\Services\Admin\System\Storage;

use App\Data\Admin\System\Storage\StorageHealthResultData;
use Illuminate\Validation\ValidationException;

class StorageDeploymentTargetService
{
    public function __construct(private readonly StorageDeploymentConfigurationSnapshot $snapshot, private readonly StorageFilesystemFactory $factory, private readonly StorageFilesystemHealthProbe $probe) {}

    public function resolve(): array
    {
        $filesystems = $this->snapshot->configuration();
        $disk = trim((string) config('simpledesk-storage.deployment.disk', 'local'));
        $configuration = $filesystems['disks'][$disk] ?? null;
        if ($disk === '' || ! is_array($configuration) || ! is_string($configuration['driver'] ?? null) || trim($configuration['driver']) === '') {
            throw ValidationException::withMessages(['activation' => 'The deployment filesystem disk is missing or malformed.']);
        }

        return ['disk' => $disk, 'driver' => $configuration['driver'], 'configuration' => $configuration];
    }

    public function test(?array $target = null): StorageHealthResultData
    {
        $target ??= $this->resolve();

        return $this->probe->test($this->factory->build($target['configuration']));
    }

    public function safeTarget(): array
    {
        try {
            $target = $this->resolve();

            return ['disk' => $target['disk'], 'driver' => $target['driver'], 'available' => true, 'structurally_valid' => true];
        } catch (ValidationException $exception) {
            return ['disk' => config('simpledesk-storage.deployment.disk'), 'driver' => null, 'available' => false, 'structurally_valid' => false, 'message' => collect($exception->errors())->flatten()->first()];
        }
    }
}
