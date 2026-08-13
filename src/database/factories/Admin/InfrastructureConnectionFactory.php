<?php

namespace Database\Factories\Admin;

use App\Enums\Admin\System\InfrastructureConnectionSource;
use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Models\Admin\System\InfrastructureConnection;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InfrastructureConnection> */
class InfrastructureConnectionFactory extends Factory
{
    protected $model = InfrastructureConnection::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'type' => InfrastructureConnectionType::Redis,
            'source' => InfrastructureConnectionSource::Managed,
            'configuration' => ['host' => '127.0.0.1', 'port' => 6379, 'database' => 0, 'username' => null, 'tls' => false, 'connect_timeout_seconds' => 5],
            'credentials' => [],
            'is_enabled' => true,
        ];
    }
}
