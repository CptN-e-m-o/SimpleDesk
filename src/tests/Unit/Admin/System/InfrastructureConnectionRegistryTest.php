<?php

namespace Tests\Unit\Admin\System;

use App\Exceptions\Admin\System\Infrastructure\InvalidInfrastructureConnectionAdapterException;
use App\Services\Admin\System\Infrastructure\InfrastructureConnectionRegistry;
use Tests\TestCase;

class InfrastructureConnectionRegistryTest extends TestCase
{
    public function test_registry_rejects_invalid_adapter_class(): void
    {
        $this->expectException(InvalidInfrastructureConnectionAdapterException::class);
        (new InfrastructureConnectionRegistry($this->app, ['redis' => \stdClass::class]))->adapter('redis');
    }
}
