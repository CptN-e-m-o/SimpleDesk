<?php

namespace App\Http\Controllers\Admin\System\Search;

use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Enums\Admin\System\SearchConfigurationMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\System\Search\SearchDriverConfigurationRequest;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Admin\System\SearchDriverConfiguration;
use App\Models\Admin\System\SearchDriverSettings;
use App\Services\Admin\System\Search\SearchActivationService;
use App\Services\Admin\System\Search\SearchDeploymentTargetService;
use App\Services\Admin\System\Search\SearchDriverCatalogService;
use App\Services\Admin\System\Search\SearchDriverHealthService;
use App\Services\Admin\System\Search\SearchDriverRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchDriverController extends Controller
{
    public function __construct(private readonly SearchDriverRegistry $registry, private readonly SearchDriverCatalogService $catalog, private readonly SearchDeploymentTargetService $deployment, private readonly SearchDriverHealthService $health, private readonly SearchActivationService $activation) {}

    public function index(Request $request): Response
    {
        $query = SearchDriverConfiguration::query()->with(['latestHealthCheck', 'infrastructureConnection']);
        if ($request->string('archived')->value() === 'archived') {
            $query->onlyTrashed();
        } elseif ($request->string('archived')->value() === 'all') {
            $query->withTrashed();
        }
        $settings = SearchDriverSettings::query()->with('activeConfiguration')->find(1);

        $active = $settings?->activeConfiguration;

        return Inertia::render('Admin/System/Search/Index', ['ownership' => ['mode' => $settings?->getRawOriginal('mode') ?? SearchConfigurationMode::Deployment->value, 'owned' => $settings !== null], 'effective_driver' => config('scout.driver'), 'deployment_target' => $this->deployment->safeTarget(), 'active_configuration' => $active instanceof SearchDriverConfiguration ? $this->catalog->safe($active) : null, 'configurations' => $query->latest()->paginate(25)->withQueryString()->through(fn (SearchDriverConfiguration $item) => [...$this->catalog->safe($item), 'latest_health' => $item->latestHealthCheck?->only(['status', 'latency_ms', 'message', 'created_at'])]), 'definitions' => array_map(fn ($definition) => $definition->toArray(), $this->registry->definitions())]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/System/Search/Form', ['configuration' => null, 'definitions' => array_map(fn ($definition) => $definition->toArray(), $this->registry->definitions()), 'connections' => $this->connections()]);
    }

    public function store(SearchDriverConfigurationRequest $request): RedirectResponse
    {
        $this->catalog->create($request->validated(), $request->user());

        return to_route('admin.system.search.index')->with('success', 'Search configuration created.');
    }

    public function edit(SearchDriverConfiguration $configuration): Response
    {
        return Inertia::render('Admin/System/Search/Form', ['configuration' => $this->catalog->safe($configuration), 'definitions' => array_map(fn ($definition) => $definition->toArray(), $this->registry->definitions()), 'connections' => $this->connections($configuration)]);
    }

    public function update(SearchDriverConfigurationRequest $request, SearchDriverConfiguration $configuration): RedirectResponse
    {
        $this->catalog->update($configuration, $request->validated(), $request->user());

        return to_route('admin.system.search.index')->with('success', 'Search configuration updated.');
    }

    public function enabled(Request $request, SearchDriverConfiguration $configuration): RedirectResponse
    {
        $request->validate(['is_enabled' => ['required', 'boolean']]);
        $this->catalog->setEnabled($configuration, $request->boolean('is_enabled'), $request->user());

        return back();
    }

    public function destroy(Request $request, SearchDriverConfiguration $configuration): RedirectResponse
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

    public function test(Request $request, SearchDriverConfiguration $configuration): JsonResponse
    {
        return response()->json($this->health->test($configuration, $request->user())->toArray());
    }

    public function activate(Request $request, SearchDriverConfiguration $configuration): RedirectResponse
    {
        return $this->activationResponse($this->activation->activate($configuration, $request->user()));
    }

    public function forceActivate(Request $request, SearchDriverConfiguration $configuration): RedirectResponse
    {
        return $this->activationResponse($this->activation->activate($configuration, $request->user(), true));
    }

    public function activateDeployment(Request $request): RedirectResponse
    {
        return $this->activationResponse($this->activation->activateDeployment($request->user()));
    }

    public function forceActivateDeployment(Request $request): RedirectResponse
    {
        return $this->activationResponse($this->activation->activateDeployment($request->user(), true));
    }

    private function activationResponse($result): RedirectResponse
    {
        return back()->with($result->restartSignaled ? 'success' : 'error', $result->restartSignaled ? 'Search ownership updated; queue worker restart signaled.' : 'Search ownership was updated, but queue worker restart signaling failed.');
    }

    private function connections(?SearchDriverConfiguration $configuration = null): array
    {
        $types = [InfrastructureConnectionType::Meilisearch->value, InfrastructureConnectionType::Typesense->value, InfrastructureConnectionType::Algolia->value];
        $referenced = array_filter([$configuration?->infrastructure_connection_id]);

        return InfrastructureConnection::withTrashed()->whereIn('type', $types)->where(fn ($query) => $query->where(fn ($query) => $query->whereNull('deleted_at')->where('is_enabled', true))->when($referenced, fn ($query) => $query->orWhereIn('id', $referenced)))->orderBy('name')->get()->map(function (InfrastructureConnection $connection) {
            $deletedAt = $connection->getAttribute('deleted_at');

            return ['id' => $connection->id, 'name' => $connection->name, 'type' => $connection->getRawOriginal('type'), 'is_enabled' => $connection->is_enabled, 'deleted_at' => $deletedAt instanceof \DateTimeInterface ? $deletedAt->format(DATE_ATOM) : null];
        })->all();
    }
}
