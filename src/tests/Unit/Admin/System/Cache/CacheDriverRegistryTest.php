<?php

namespace Tests\Unit\Admin\System\Cache;

use App\Enums\Admin\System\CacheDriverType;
use App\Exceptions\Admin\System\Cache\CacheDriverAdapterNotRegisteredException;
use App\Services\Admin\System\Cache\CacheDriverRegistry;
use Tests\TestCase;

class CacheDriverRegistryTest extends TestCase
{
    public function test_supported_adapters_resolve(): void
    {
        $registry = app(CacheDriverRegistry::class);
        $this->assertSame([CacheDriverType::Database, CacheDriverType::File, CacheDriverType::Redis], $registry->registeredTypes());
        foreach ($registry->registeredTypes() as $type) $this->assertSame($type, $registry->adapter($type)->type());
    }

    public function test_unknown_adapter_is_rejected_safely(): void
    {
        $this->expectException(CacheDriverAdapterNotRegisteredException::class);
        app(CacheDriverRegistry::class)->adapter('dynamodb');
    }
}
