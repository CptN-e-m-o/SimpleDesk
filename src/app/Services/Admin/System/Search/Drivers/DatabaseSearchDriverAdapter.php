<?php

namespace App\Services\Admin\System\Search\Drivers;

use App\Contracts\Admin\System\Search\SearchDriverAdapter;
use App\Data\Admin\System\Search\SearchDriverDefinitionData;
use App\Data\Admin\System\Search\SearchHealthResultData;
use App\Data\Admin\System\Search\SearchRuntimeConfigurationData;
use App\Enums\Admin\System\SearchDriverType;
use App\Enums\Admin\System\SearchHealthStatus;
use App\Models\Admin\System\SearchDriverConfiguration;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class DatabaseSearchDriverAdapter implements SearchDriverAdapter
{
    public function type(): SearchDriverType
    {
        return SearchDriverType::Database;
    }

    public function definition(): SearchDriverDefinitionData
    {
        return new SearchDriverDefinitionData($this->type(), 'Database', 'Laravel Scout database engine.', true, false);
    }

    public function validateAndNormalize(array $configuration, mixed $infrastructureConnectionId): array
    {
        if ($infrastructureConnectionId !== null && $infrastructureConnectionId !== '') {
            throw ValidationException::withMessages(['infrastructure_connection_id' => 'Database Search does not use an infrastructure connection.']);
        }
        if ($configuration !== []) {
            throw ValidationException::withMessages(['configuration' => 'Database Search configuration must be empty.']);
        }

        return ['configuration' => [], 'infrastructure_connection_id' => null];
    }

    public function runtimeConfiguration(SearchDriverConfiguration $configuration): SearchRuntimeConfigurationData
    {
        $this->validateAndNormalize($configuration->configuration ?? [], $configuration->infrastructure_connection_id);

        return new SearchRuntimeConfigurationData($this->type());
    }

    public function test(SearchDriverConfiguration $configuration): SearchHealthResultData
    {
        $this->runtimeConfiguration($configuration);
        $started = hrtime(true);
        $driver = DB::connection()->getDriverName();
        if (! in_array($driver, ['pgsql', 'mysql'], true)) {
            return new SearchHealthResultData(SearchHealthStatus::Unavailable, 0, "The Scout database engine is not supported on [{$driver}].", ['database_driver' => $driver]);
        }
        try {
            DB::select('SELECT 1');

            return new SearchHealthResultData(SearchHealthStatus::Healthy, $this->elapsed($started), 'Database Search connectivity verified.', ['database_driver' => $driver, 'operation' => 'select_1']);
        } catch (Throwable) {
            return new SearchHealthResultData(SearchHealthStatus::Unhealthy, $this->elapsed($started), 'Database Search connectivity could not be verified.', ['database_driver' => $driver, 'operation' => 'select_1']);
        }
    }

    private function elapsed(int $started): int
    {
        return (int) round((hrtime(true) - $started) / 1_000_000);
    }
}
