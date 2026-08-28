<?php

namespace Tests\Unit\Admin\System\Storage;

use App\Enums\Admin\System\StorageDriverType;
use App\Services\Admin\System\Storage\StorageDriverRegistry;
use Tests\TestCase;

class StorageDriverRegistryTest extends TestCase
{
    public function test_it_resolves_all_managed_storage_adapters(): void
    {
        $registry = $this->app->make(StorageDriverRegistry::class);

        foreach (StorageDriverType::cases() as $type) {
            $this->assertSame($type, $registry->adapter($type)->type());
        }
    }

    public function test_definitions_report_installed_s3_runtime(): void
    {
        $definitions = collect($this->app->make(StorageDriverRegistry::class)->definitions())->keyBy(fn ($definition) => $definition->driver->value);

        $this->assertTrue($definitions['local']->available);
        $this->assertTrue($definitions['s3']->available);
        $this->assertTrue($definitions['s3_compatible']->available);
    }
}
