<?php

namespace Tests\Feature\Admin\System\Storage;

use App\Enums\Admin\System\StorageHealthStatus;
use App\Services\Admin\System\Storage\StorageFilesystemHealthProbe;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageFilesystemHealthProbeTest extends TestCase
{
    public function test_local_probe_verifies_storage_and_cleans_up_random_object(): void
    {
        $disk = Storage::build(['driver' => 'local', 'root' => storage_path('framework/testing/storage-health')]);
        $result = app(StorageFilesystemHealthProbe::class)->test($disk);

        $this->assertSame(StorageHealthStatus::Healthy, $result->status);
        $this->assertSame([], $disk->allFiles('.simpledesk-health'));
    }
}
