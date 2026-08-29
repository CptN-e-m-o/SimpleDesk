<?php

namespace App\Services\Admin\System\Cache\Drivers;

use App\Contracts\Admin\System\Cache\CacheDriverAdapter;
use App\Data\Admin\System\Cache\CacheDriverDefinitionData;
use App\Data\Admin\System\Cache\CacheHealthResultData;
use App\Data\Admin\System\Cache\CacheRuntimeConfigurationData;
use App\Enums\Admin\System\CacheDriverType;
use App\Enums\Admin\System\CacheHealthStatus;
use App\Models\Admin\System\CacheDriverConfiguration;
use App\Services\Admin\System\Cache\CacheStoreHealthProbe;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DatabaseCacheDriverAdapter implements CacheDriverAdapter
{
    public function __construct(private readonly CacheStoreHealthProbe $probe) {}

    public function type(): CacheDriverType
    {
        return CacheDriverType::Database;
    }

    public function definition(): CacheDriverDefinitionData
    {
        return new CacheDriverDefinitionData($this->type(), 'Database', 'Use Laravel database cache and lock tables.', false, null, true, options: ['database_connections' => $this->allowedConnections()]);
    }

    public function validateAndNormalize(array $configuration): array
    {
        $validated = Validator::make(['database_connection' => $configuration['database_connection'] ?? config('database.default')], ['database_connection' => ['required', 'string', Rule::in($this->allowedConnections())]])->validate();

        return ['database_connection' => $validated['database_connection']];
    }

    public function runtimeConfiguration(CacheDriverConfiguration $configuration): CacheRuntimeConfigurationData
    {
        $values = $this->validateAndNormalize($configuration->configuration ?? []);

        return new CacheRuntimeConfigurationData(['driver' => 'database', 'connection' => $values['database_connection'], 'table' => 'cache', 'lock_connection' => $values['database_connection'], 'lock_table' => 'cache_locks']);
    }

    public function test(CacheDriverConfiguration $configuration): CacheHealthResultData
    {
        $runtime = $this->runtimeConfiguration($configuration);
        $connection = $runtime->store['connection'];
        if (! Schema::connection($connection)->hasTable('cache') || ! Schema::connection($connection)->hasTable('cache_locks')) {
            return new CacheHealthResultData(CacheHealthStatus::Unhealthy, 0, 'The configured database cache and lock tables are required.', ['database_connection' => $connection, 'cache_table' => 'cache', 'lock_table' => 'cache_locks']);
        }

        return $this->probe->test($runtime->store, details: ['database_connection' => $connection, 'cache_table' => 'cache', 'lock_table' => 'cache_locks']);
    }

    private function allowedConnections(): array
    {
        $available = array_keys((array) config('database.connections', []));
        $configured = array_values(array_unique(array_filter(array_map(fn ($v) => trim((string) $v), (array) config('simpledesk-cache.database.allowed_connections', [])))));
        if ($configured !== []) {
            return array_values(array_intersect($configured, $available));
        }
        $default = trim((string) config('database.default', ''));

        return $default !== '' && in_array($default, $available, true) ? [$default] : [];
    }
}
