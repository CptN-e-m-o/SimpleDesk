<?php

namespace Tests\Feature\Admin\System\Queues;

use App\Models\User\User;
use App\Services\Admin\System\Queues\QueueDriverCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class QueueDriverAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_sqs_cannot_be_created_until_adapter_is_registered(): void
    {
        $actor =
            User::factory()
                ->create();

        $service =
            app(
                QueueDriverCatalogService::class,
            );

        try {
            $service->create(
                [
                    'name' =>
                        'Future SQS',

                    'driver' =>
                        'sqs',

                    'configuration' =>
                        [],

                    'is_enabled' =>
                        true,
                ],
                $actor,
            );

            $this->fail(
                'SQS should not be creatable before its adapter is registered.',
            );
        } catch (
        ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'driver',
                $exception->errors(),
            );
        }
    }

    public function test_beanstalkd_cannot_be_created_until_adapter_is_registered(): void
    {
        $actor =
            User::factory()
                ->create();

        $service =
            app(
                QueueDriverCatalogService::class,
            );

        try {
            $service->create(
                [
                    'name' =>
                        'Future Beanstalkd',

                    'driver' =>
                        'beanstalkd',

                    'configuration' =>
                        [],

                    'is_enabled' =>
                        true,
                ],
                $actor,
            );

            $this->fail(
                'Beanstalkd should not be creatable before its adapter is registered.',
            );
        } catch (
        ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'driver',
                $exception->errors(),
            );
        }
    }
}
