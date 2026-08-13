<?php

namespace Tests\Unit\Admin\System\Queues;

use App\Enums\Admin\System\QueueDriverType;
use App\Exceptions\Admin\System\Queues\QueueDriverAdapterNotRegisteredException;
use App\Services\Admin\System\Queues\QueueDriverRegistry;
use Tests\TestCase;

class QueueDriverRegistryTest extends TestCase
{
    public function test_only_database_redis_and_sync_are_registered(): void
    {
        $registry = $this->app->make(QueueDriverRegistry::class);

        $this->assertSame(
            [QueueDriverType::Database, QueueDriverType::Redis, QueueDriverType::Sync],
            $registry->registeredTypes(),
        );
        $this->assertCount(3, $registry->definitions());
    }

    public function test_sqs_and_beanstalkd_are_not_registered(): void
    {
        $registry = $this->app->make(QueueDriverRegistry::class);

        foreach ([QueueDriverType::Sqs, QueueDriverType::Beanstalkd] as $type) {
            try {
                $registry->adapter($type);
                $this->fail("{$type->value} should not be registered.");
            } catch (QueueDriverAdapterNotRegisteredException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
