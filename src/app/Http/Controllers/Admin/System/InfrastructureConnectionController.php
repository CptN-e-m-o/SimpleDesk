<?php

namespace App\Http\Controllers\Admin\System;

use App\Enums\Admin\System\InfrastructureHealthStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\System\InfrastructureConnectionIndexRequest;
use App\Http\Requests\Admin\System\StoreInfrastructureConnectionRequest;
use App\Http\Requests\Admin\System\UpdateInfrastructureConnectionRequest;
use App\Models\Admin\System\InfrastructureConnection;
use App\Services\Admin\System\Infrastructure\InfrastructureConnectionCatalogService;
use App\Services\Admin\System\Infrastructure\InfrastructureConnectionRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InfrastructureConnectionController extends Controller
{
    public function __construct(
        private readonly InfrastructureConnectionRegistry $registry,
        private readonly InfrastructureConnectionCatalogService $catalog,
    ) {}

    public function index(
        InfrastructureConnectionIndexRequest $request,
    ): Response {
        $filters =
            $request->validated();

        $query =
            InfrastructureConnection::query()
                ->with(
                    'latestHealthCheck',
                );

        $this->applyFilters(
            $query,
            $filters,
        );

        $connections =
            $query
                ->latest()
                ->paginate(25)
                ->withQueryString()
                ->through(
                    fn (
                        InfrastructureConnection $connection,
                    ): array => [
                        ...$this->catalog->safe(
                            $connection,
                        ),

                        'deleted_at' =>
                            $connection
                                ->deleted_at
                                ?->toIso8601String(),

                        'latest_health_check' =>
                            $connection
                                ->latestHealthCheck
                                ?->toArray(),
                    ],
                );

        return Inertia::render(
            'Admin/System/Connections/Index',
            [
                'connections' =>
                    $connections,

                'definitions' =>
                    array_map(
                        fn ($definition) =>
                        $definition->toArray(),
                        $this->registry
                            ->definitions(),
                    ),

                'filters' =>
                    $filters,

                'stats' =>
                    $this->stats(),
            ],
        );
    }

    public function create(): Response
    {
        return Inertia::render(
            'Admin/System/Connections/Create',
            [
                'definitions' =>
                    array_map(
                        fn ($definition) =>
                        $definition->toArray(),
                        $this->registry
                            ->definitions(),
                    ),
            ],
        );
    }

    public function store(
        StoreInfrastructureConnectionRequest $request,
    ): RedirectResponse {
        $this->catalog->create(
            $request->validated(),
            $request->user(),
        );

        return to_route(
            'admin.system.connections.index',
        )->with(
            'success',
            'Connection created.',
        );
    }

    public function edit(
        InfrastructureConnection $connection,
    ): Response {
        return Inertia::render(
            'Admin/System/Connections/Edit',
            [
                'connection' =>
                    $this->catalog->safe(
                        $connection,
                    ),

                'definitions' =>
                    array_map(
                        fn ($definition) =>
                        $definition->toArray(),
                        $this->registry
                            ->definitions(),
                    ),
            ],
        );
    }

    public function update(
        UpdateInfrastructureConnectionRequest $request,
        InfrastructureConnection $connection,
    ): RedirectResponse {
        $this->catalog->update(
            $connection,
            $request->validated(),
            $request->user(),
        );

        return to_route(
            'admin.system.connections.index',
        )->with(
            'success',
            'Connection updated.',
        );
    }

    public function toggle(
        Request $request,
        InfrastructureConnection $connection,
    ): RedirectResponse {
        $this->catalog->setEnabled(
            $connection,
            ! $connection->is_enabled,
            $request->user(),
        );

        return back();
    }

    public function destroy(
        Request $request,
        InfrastructureConnection $connection,
    ): RedirectResponse {
        $this->catalog->archive(
            $connection,
            $request->user(),
        );

        return back();
    }

    public function restore(
        Request $request,
        int $id,
    ): RedirectResponse {
        $this->catalog->restore(
            $id,
            $request->user(),
        );

        return back();
    }

    public function forceDelete(
        Request $request,
        int $id,
    ): RedirectResponse {
        $this->catalog->forceDelete(
            $id,
            $request->user(),
        );

        return back();
    }

    private function applyFilters(
        Builder $query,
        array $filters,
    ): void {
        $archived =
            $filters['archived']
            ?? 'active';

        if ($archived === 'archived') {
            $query->onlyTrashed();
        } elseif ($archived === 'all') {
            $query->withTrashed();
        }

        if (! empty($filters['search'])) {
            $search =
                trim(
                    $filters['search'],
                );

            $query->where(
                'name',
                'like',
                '%'.$search.'%',
            );
        }

        if (! empty($filters['type'])) {
            $query->where(
                'type',
                $filters['type'],
            );
        }

        if (! empty($filters['source'])) {
            $query->where(
                'source',
                $filters['source'],
            );
        }

        if (
            ($filters['state'] ?? null)
            === 'enabled'
        ) {
            $query->where(
                'is_enabled',
                true,
            );
        }

        if (
            ($filters['state'] ?? null)
            === 'disabled'
        ) {
            $query->where(
                'is_enabled',
                false,
            );
        }

        if (! empty($filters['health'])) {
            $health =
                $filters['health'];

            if (
                $health ===
                InfrastructureHealthStatus::Unknown->value
            ) {
                $query->where(
                    function (
                        Builder $healthQuery,
                    ): void {
                        $healthQuery
                            ->whereDoesntHave(
                                'latestHealthCheck',
                            )
                            ->orWhereHas(
                                'latestHealthCheck',
                                fn (
                                    Builder $latest,
                                ) =>
                                $latest->where(
                                    'status',
                                    InfrastructureHealthStatus::Unknown->value,
                                ),
                            );
                    },
                );
            } else {
                $query->whereHas(
                    'latestHealthCheck',
                    fn (
                        Builder $latest,
                    ) =>
                    $latest->where(
                        'status',
                        $health,
                    ),
                );
            }
        }
    }

    private function stats(): array
    {
        $problemStatuses = [
            InfrastructureHealthStatus::Degraded->value,
            InfrastructureHealthStatus::Unhealthy->value,
            InfrastructureHealthStatus::Unavailable->value,
        ];

        return [
            'total' =>
                InfrastructureConnection::withTrashed()
                    ->count(),

            'enabled' =>
                InfrastructureConnection::query()
                    ->where(
                        'is_enabled',
                        true,
                    )
                    ->count(),

            'healthy' =>
                InfrastructureConnection::query()
                    ->where('is_enabled', true)
                    ->whereHas(
                        'latestHealthCheck',
                        fn (Builder $query) =>
                        $query->where(
                            'status',
                            InfrastructureHealthStatus::Healthy->value,
                        ),
                    )
                    ->count(),

            'problems' =>
                InfrastructureConnection::query()
                    ->where('is_enabled', true)
                    ->whereHas(
                        'latestHealthCheck',
                        fn (Builder $query) =>
                        $query->whereIn(
                            'status',
                            $problemStatuses,
                        ),
                    )
                    ->count(),

            'archived' =>
                InfrastructureConnection::onlyTrashed()
                    ->count(),
        ];
    }
}
