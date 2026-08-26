<?php

namespace Tests\Unit\Admin\System\Broadcasting;

use App\Enums\Admin\System\BroadcastDriverType;
use App\Services\Admin\System\Broadcasting\BroadcastDriverRegistry;
use Tests\TestCase;

class BroadcastDriverRegistryTest extends TestCase
{
    public function test_supported_definitions_and_deferred_ably_are_honest(): void
    {
        $definitions = collect(app(BroadcastDriverRegistry::class)->definitions())->keyBy(fn ($definition) => $definition->type->value);
        $this->assertTrue($definitions[BroadcastDriverType::Reverb->value]->available);
        $this->assertTrue($definitions[BroadcastDriverType::Pusher->value]->available);
        $this->assertFalse($definitions[BroadcastDriverType::Ably->value]->available);
        $this->assertNotEmpty($definitions[BroadcastDriverType::Ably->value]->unavailableReason);
    }
}
