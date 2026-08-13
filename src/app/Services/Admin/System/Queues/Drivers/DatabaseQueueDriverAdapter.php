<?php

namespace App\Services\Admin\System\Queues\Drivers;

use App\Contracts\Admin\System\Queues\QueueDriverAdapter;
use App\Data\Admin\System\Queues\QueueDriverDefinitionData;
use App\Data\Admin\System\Queues\QueueRuntimeConfigurationData;
use App\Enums\Admin\System\QueueDriverType;
use App\Models\Admin\System\QueueDriverConfiguration;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DatabaseQueueDriverAdapter implements QueueDriverAdapter
{
    public function type(): QueueDriverType
    {
        return QueueDriverType::Database;
    }

    public function definition(): QueueDriverDefinitionData
    {
        return new QueueDriverDefinitionData(
            type: $this->type(),
            label: 'Database',
            description: 'Store queued jobs in an application database.',
            options: ['database_connections' => array_keys((array) config('database.connections', []))],
        );
    }

    public function validateAndNormalize(array $configuration): array
    {
        $validated = Validator::make($configuration, [
            'database_connection' => ['required', 'string', Rule::in($this->definition()->options['database_connections'])],
            'retry_after' => ['required', 'integer', 'min:1'],
            'after_commit' => ['required', 'boolean'],
        ])->validate();

        return [
            'database_connection' => $validated['database_connection'],
            'retry_after' => (int) $validated['retry_after'],
            'after_commit' => (bool) $validated['after_commit'],
        ];
    }

    public function runtimeConfiguration(QueueDriverConfiguration $configuration): QueueRuntimeConfigurationData
    {
        $values = $this->validateAndNormalize($configuration->configuration ?? []);

        return new QueueRuntimeConfigurationData([
            'driver' => 'database',
            'connection' => $values['database_connection'],
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => $values['retry_after'],
            'after_commit' => $values['after_commit'],
        ]);
    }
}
