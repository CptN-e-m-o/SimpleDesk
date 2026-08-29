<?php

namespace App\Services\Admin\System\Storage;

use App\Data\Admin\System\Storage\StorageHealthResultData;
use Illuminate\Validation\ValidationException;
use Throwable;

class StorageDeploymentTargetService
{
    public function __construct(
        private readonly StorageDeploymentConfigurationSnapshot $snapshot,
        private readonly StorageFilesystemFactory $factory,
        private readonly StorageFilesystemHealthProbe $probe,
    ) {}

    public function resolve(): array
    {
        $filesystems = $this->snapshot->configuration();

        $disk = trim(
            (string) config(
                'simpledesk-storage.deployment.disk',
                'local',
            ),
        );

        $configuration = $filesystems['disks'][$disk] ?? null;

        if ($disk === '' || ! is_array($configuration)) {
            throw $this->invalid();
        }

        $driver = $configuration['driver'] ?? null;

        if (! is_string($driver) || trim($driver) === '') {
            throw $this->invalid();
        }

        $driver = trim($driver);

        $this->assertKnownStructure(
            $driver,
            $configuration,
        );

        try {
            $this->factory->build($configuration);
        } catch (Throwable) {
            throw $this->invalid();
        }

        return [
            'disk' => $disk,
            'driver' => $driver,
            'configuration' => $configuration,
        ];
    }

    public function test(?array $target = null): StorageHealthResultData
    {
        $target ??= $this->resolve();

        return $this->probe->test(
            $this->factory->buildForHealth(
                $target['configuration'],
            ),
        );
    }

    public function safeTarget(): array
    {
        try {
            $target = $this->resolve();

            return [
                'disk' => $target['disk'],
                'driver' => $target['driver'],
                'available' => true,
                'structurally_valid' => true,
            ];
        } catch (ValidationException $exception) {
            return [
                'disk' => config(
                    'simpledesk-storage.deployment.disk',
                ),
                'driver' => null,
                'available' => false,
                'structurally_valid' => false,
                'message' => collect($exception->errors())
                    ->flatten()
                    ->first(),
            ];
        }
    }

    private function assertKnownStructure(
        string $driver,
        array $configuration,
    ): void {
        if ($driver === 'local') {
            $root = $configuration['root'] ?? null;

            if (! is_string($root) || trim($root) === '') {
                throw $this->invalid();
            }

            return;
        }

        if ($driver === 's3') {
            $bucket = $configuration['bucket'] ?? null;

            if (! is_string($bucket) || trim($bucket) === '') {
                throw $this->invalid();
            }
        }
    }

    private function invalid(): ValidationException
    {
        return ValidationException::withMessages([
            'activation' => 'The deployment filesystem disk is missing, unsupported, or structurally malformed.',
        ]);
    }
}
