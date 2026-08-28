<?php

namespace App\Services\Admin\System\Storage;

use App\Data\Admin\System\Storage\StorageHealthResultData;
use App\Enums\Admin\System\StorageHealthStatus;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Throwable;

class StorageFilesystemHealthProbe
{
    public function test(Filesystem $disk): StorageHealthResultData
    {
        $path = '.simpledesk-health/'.Str::uuid().'.probe';
        $content = random_bytes(32);
        $started = hrtime(true);

        try {
            if (! $disk->put($path, $content) || $disk->get($path) !== $content) {
                return new StorageHealthResultData(StorageHealthStatus::Unhealthy, $this->latency($started), 'Storage write/read verification failed.');
            }
            if (! $disk->delete($path) || $disk->exists($path)) {
                return new StorageHealthResultData(StorageHealthStatus::Unhealthy, $this->latency($started), 'Storage probe cleanup verification failed.');
            }

            return new StorageHealthResultData(StorageHealthStatus::Healthy, $this->latency($started), 'Storage write, read and cleanup probe succeeded.');
        } catch (Throwable) {
            return new StorageHealthResultData(StorageHealthStatus::Unavailable, $this->latency($started), 'Storage provider is unavailable.');
        } finally {
            try {
                $disk->delete($path);
            } catch (Throwable) {
            }
        }
    }

    private function latency(int $started): int
    {
        return (int) round((hrtime(true) - $started) / 1_000_000);
    }
}
