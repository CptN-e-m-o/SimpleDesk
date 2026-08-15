<?php

namespace App\Http\Controllers\Admin\System\Queues;

use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Enums\Admin\System\QueueConfigurationMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\System\Queues\QueueDriverConfigurationIndexRequest;
use App\Http\Requests\Admin\System\Queues\StoreQueueDriverConfigurationRequest;
use App\Http\Requests\Admin\System\Queues\UpdateQueueDriverConfigurationRequest;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Admin\System\QueueDriverConfiguration;
use App\Models\Admin\System\QueueDriverSettings;
use App\Services\Admin\System\Queues\QueueBacklogService;
use App\Services\Admin\System\Queues\QueueDriverCatalogService;
use App\Services\Admin\System\Queues\QueueDriverRegistry;
use App\Services\Admin\System\Queues\QueueSafetyPolicy;
use App\Services\Admin\System\Queues\QueueWorkloadRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QueueDriverConfigurationController extends Controller
{
    public function __construct(
        private readonly QueueDriverRegistry $registry,
        private readonly QueueDriverCatalogService $catalog,
        private readonly QueueWorkloadRegistry $workloads,
        private readonly QueueBacklogService $backlog,
        private readonly QueueSafetyPolicy $safety,
    ) {}

    public function index(
        QueueDriverConfigurationIndexRequest $request,
    ): Response {
        $filters = $request->validated();

        $query = QueueDriverConfiguration::query()
            ->with([
                'latestHealthCheck',
                'infrastructureConnection',
            ]);

        $archived =
            $filters['archived']
            ?? 'active';

        if ($archived === 'archived') {
            $query->onlyTrashed();
        } elseif ($archived === 'all') {
            $query->withTrashed();
        }

        if (! empty($filters['search'])) {
            $query->where(
                'name',
                'like',
                '%'.trim(
                    $filters['search'],
                ).'%',
            );
        }

        if (! empty($filters['driver'])) {
            $query->where(
                'driver',
                $filters['driver'],
            );
        }

        if (! empty($filters['state'])) {
            $query->where(
                'is_enabled',
                $filters['state']
                === 'enabled',
            );
        }

        if (! empty($filters['health'])) {
            $query->whereHas(
                'latestHealthCheck',
                fn (Builder $query) => $query->where(
                    'status',
                    $filters['health'],
                ),
            );
        }

        $settings = QueueDriverSettings::query()
            ->with(
                'activeConfiguration',
            )
            ->find(
                QueueDriverSettings::SINGLETON_ID,
            );

        $items = $query
            ->latest()
            ->paginate(25)
            ->withQueryString()
            ->through(
                fn (
                    QueueDriverConfiguration $configuration,
                ) => $this
                    ->catalog
                    ->safe(
                        $configuration,
                    ),
            );

        return Inertia::render(
            'Admin/System/Queues/Index',
            [
                'ownership' => [
                    'mode' => $settings
                        ?->mode
                        ->value
                        ?? QueueConfigurationMode::Deployment->value,

                    'owned' => $settings !== null,

                    'worker_restart_required' => $settings
                        ?->worker_restart_required
                        ?? false,
                ],

                'effective_connection' => config(
                    'queue.default',
                ),

                'effective_driver' => config(
                    'queue.connections.'
                    .config('queue.default')
                    .'.driver',
                ),

                'deployment_target' => $this->deploymentTarget(),

                'active_configuration' => $settings
                    ?->activeConfiguration
                    ? $this
                        ->catalog
                        ->safe(
                            $settings
                                ->activeConfiguration,
                        )
                        : null,

                'configurations' => $items,

                'definitions' => $this->definitions(),

                'workloads' => array_map(
                    fn ($definition) => $definition->toArray(),
                    $this
                        ->workloads
                        ->definitions(),
                ),

                'backlog' => $this
                    ->backlog
                    ->inspect(),

                'filters' => $filters,

                'stats' => [
                    'total' => QueueDriverConfiguration::withTrashed()
                        ->count(),

                    'enabled' => QueueDriverConfiguration::query()
                        ->where(
                            'is_enabled',
                            true,
                        )
                        ->count(),

                    'archived' => QueueDriverConfiguration::onlyTrashed()
                        ->count(),
                ],
            ],
        );
    }

    public function create(): Response
    {
        return Inertia::render(
            'Admin/System/Queues/Create',
            [
                'definitions' => $this->definitions(),

                'redis_connections' => $this->redisConnections(),

                'defaults' => [
                    'minimum_retry_after' => $this
                        ->safety
                        ->minimumRetryAfterSeconds(),
                ],
            ],
        );
    }

    public function store(
        StoreQueueDriverConfigurationRequest $request,
    ): RedirectResponse {
        $this
            ->catalog
            ->create(
                $request->validated(),
                $request->user(),
            );

        return to_route(
            'admin.system.queues.index',
        )->with(
            'success',
            'Queue configuration created.',
        );
    }

    public function edit(
        QueueDriverConfiguration $configuration,
    ): Response {
        return Inertia::render(
            'Admin/System/Queues/Edit',
            [
                'configuration' => $this
                    ->catalog
                    ->safe(
                        $configuration,
                    ),

                'definitions' => $this->definitions(),

                'redis_connections' => $this->redisConnections(
                    $configuration,
                ),

                'defaults' => [
                'minimum_retry_after' => $this
                    ->safety
                    ->minimumRetryAfterSeconds(),
                ],
            ],
        );
    }

    public function update(
        UpdateQueueDriverConfigurationRequest $request,
        QueueDriverConfiguration $configuration,
    ): RedirectResponse {
        $this
            ->catalog
            ->update(
                $configuration,
                $request->validated(),
                $request->user(),
            );

        return back();
    }

    public function setEnabled(
        Request $request,
        QueueDriverConfiguration $configuration,
    ): RedirectResponse {
        $request->validate([
            'is_enabled' => [
                'required',
                'boolean',
            ],
        ]);

        $this
            ->catalog
            ->setEnabled(
                $configuration,
                $request->boolean(
                    'is_enabled',
                ),
                $request->user(),
            );

        return back();
    }

    public function destroy(
        Request $request,
        QueueDriverConfiguration $configuration,
    ): RedirectResponse {
        $this
            ->catalog
            ->archive(
                $configuration,
                $request->user(),
            );

        return back();
    }

    public function restore(
        Request $request,
        int $id,
    ): RedirectResponse {
        $this
            ->catalog
            ->restore(
                $id,
                $request->user(),
            );

        return back();
    }

    public function forceDelete(
        Request $request,
        int $id,
    ): RedirectResponse {
        $this
            ->catalog
            ->forceDelete(
                $id,
                $request->user(),
            );

        return back();
    }

    private function definitions(): array
    {
        return array_map(
            fn ($definition) => $definition->toArray(),
            $this
                ->registry
                ->definitions(),
        );
    }

    private function redisConnections(
        ?QueueDriverConfiguration $configuration = null,
    ): array {
        $referencedIds = array_values(
            array_filter([
                $configuration
                    ?->infrastructure_connection_id,
            ]),
        );

        return InfrastructureConnection::withTrashed()
            ->where(
                'type',
                InfrastructureConnectionType::Redis->value,
            )
            ->where(
                function (Builder $query) use (
                    $referencedIds,
                ): void {
                    $query->where(
                        function (Builder $query): void {
                            $query
                                ->whereNull(
                                    'deleted_at',
                                )
                                ->where(
                                    'is_enabled',
                                    true,
                                );
                        },
                    );

                    if ($referencedIds !== []) {
                        $query->orWhereIn(
                            'id',
                            $referencedIds,
                        );
                    }
                },
            )
            ->orderBy('name')
            ->get()
            ->map(
                fn (
                    InfrastructureConnection $connection,
                ) => [
                    'id' => $connection->id,

                    'name' => $connection->name,

                    'type' => $connection
                        ->type
                        ->value,

                    'source' => $connection
                        ->source
                        ->value,

                    'is_enabled' => $connection
                        ->is_enabled,

                    'deleted_at' => $connection
                        ->deleted_at
                        ?->toIso8601String(),
                ],
            )
            ->all();
    }

    private function deploymentTarget(): array
    {
        $connection = trim(
            (string) config(
                'simpledesk-queues.deployment.connection',
                '',
            ),
        );

        $configuration = $connection !== ''
            ? config("queue.connections.{$connection}")
            : null;

        return [
            'connection' => $connection !== ''
                ? $connection
                : null,
            'driver' => is_array($configuration)
                ? ($configuration['driver'] ?? null)
                : null,
            'available' => $connection !== ''
                && is_array($configuration)
                && ! empty($configuration['driver']),
        ];
    }
}
