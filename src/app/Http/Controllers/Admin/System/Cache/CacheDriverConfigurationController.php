<?php

namespace App\Http\Controllers\Admin\System\Cache;

use App\Enums\Admin\System\CacheConfigurationMode;
use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\System\Cache\StoreCacheDriverConfigurationRequest;
use App\Http\Requests\Admin\System\Cache\UpdateCacheDriverConfigurationRequest;
use App\Models\Admin\System\CacheDriverConfiguration;
use App\Models\Admin\System\CacheDriverSettings;
use App\Models\Admin\System\InfrastructureConnection;
use App\Services\Admin\System\Cache\CacheDeploymentTargetService;
use App\Services\Admin\System\Cache\CacheDriverCatalogService;
use App\Services\Admin\System\Cache\CacheDriverRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CacheDriverConfigurationController extends Controller
{
    public function __construct(private readonly CacheDriverRegistry $registry, private readonly CacheDriverCatalogService $catalog, private readonly CacheDeploymentTargetService $deployment) {}

    public function index(Request $request): Response
    {
        $query = CacheDriverConfiguration::query()->with(['latestHealthCheck', 'infrastructureConnection']);
        if ($request->string('archived')->value() === 'archived') {
            $query->onlyTrashed();
        } elseif ($request->string('archived')->value() === 'all') {
            $query->withTrashed();
        } $settings = CacheDriverSettings::query()->with('activeConfiguration')->find(1);

        return Inertia::render('Admin/System/Cache/Index', ['ownership' => ['mode' => $settings?->mode->value ?? CacheConfigurationMode::Deployment->value, 'owned' => $settings !== null], 'effective_store' => config('cache.default'), 'effective_driver' => config('cache.stores.'.config('cache.default').'.driver'), 'deployment_target' => $this->deployment->safeTarget(), 'active_configuration' => $settings?->activeConfiguration ? $this->catalog->safe($settings->activeConfiguration) : null, 'configurations' => $query->latest()->paginate(25)->withQueryString()->through(fn ($item) => $this->catalog->safe($item)), 'definitions' => array_map(fn ($d) => $d->toArray(), $this->registry->definitions()), 'stats' => ['total' => CacheDriverConfiguration::withTrashed()->count(), 'enabled' => CacheDriverConfiguration::query()->where('is_enabled', true)->count(), 'archived' => CacheDriverConfiguration::onlyTrashed()->count()]]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/System/Cache/Create', ['definitions' => array_map(fn ($d) => $d->toArray(), $this->registry->definitions()), 'redis_connections' => $this->redisConnections()]);
    }

    public function store(StoreCacheDriverConfigurationRequest $request): RedirectResponse
    {
        $this->catalog->create($request->validated(), $request->user());

        return to_route('admin.system.cache.index')->with('success', 'Cache configuration created.');
    }

    public function edit(CacheDriverConfiguration $configuration): Response
    {
        return Inertia::render('Admin/System/Cache/Edit', ['configuration' => $this->catalog->safe($configuration), 'definitions' => array_map(fn ($d) => $d->toArray(), $this->registry->definitions()), 'redis_connections' => $this->redisConnections($configuration)]);
    }

    public function update(UpdateCacheDriverConfigurationRequest $request, CacheDriverConfiguration $configuration): RedirectResponse
    {
        $this->catalog->update($configuration, $request->validated(), $request->user());

        return back()->with('success', 'Cache configuration updated.');
    }

    public function setEnabled(Request $request, CacheDriverConfiguration $configuration): RedirectResponse
    {
        $request->validate(['is_enabled' => ['required', 'boolean']]);
        $this->catalog->setEnabled($configuration, $request->boolean('is_enabled'), $request->user());

        return back();
    }

    public function destroy(Request $request, CacheDriverConfiguration $configuration): RedirectResponse
    {
        $this->catalog->archive($configuration, $request->user());

        return back();
    }

    public function restore(Request $request, int $id): RedirectResponse
    {
        $this->catalog->restore($id, $request->user());

        return back();
    }

    public function forceDelete(Request $request, int $id): RedirectResponse
    {
        $this->catalog->forceDelete($id, $request->user());

        return back();
    }

    private function redisConnections(?CacheDriverConfiguration $configuration = null): array
    {
        $referenced = array_filter([$configuration?->infrastructure_connection_id]);

        return InfrastructureConnection::withTrashed()->where('type', InfrastructureConnectionType::Redis->value)->where(fn ($q) => $q->where(fn ($q) => $q->whereNull('deleted_at')->where('is_enabled', true))->when($referenced, fn ($q) => $q->orWhereIn('id', $referenced)))->orderBy('name')->get()->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'source' => $c->source->value, 'is_enabled' => $c->is_enabled, 'deleted_at' => $c->deleted_at?->toIso8601String()])->all();
    }
}
