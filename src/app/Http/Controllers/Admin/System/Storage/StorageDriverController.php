<?php

namespace App\Http\Controllers\Admin\System\Storage;

use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Enums\Admin\System\StorageConfigurationMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\System\Storage\StorageDriverConfigurationRequest;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Admin\System\StorageDriverConfiguration;
use App\Models\Admin\System\StorageDriverSettings;
use App\Services\Admin\System\Storage\StorageActivationService;
use App\Services\Admin\System\Storage\StorageDeploymentTargetService;
use App\Services\Admin\System\Storage\StorageDriverCatalogService;
use App\Services\Admin\System\Storage\StorageDriverHealthService;
use App\Services\Admin\System\Storage\StorageDriverRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StorageDriverController extends Controller
{
    public function __construct(private readonly StorageDriverRegistry $registry, private readonly StorageDriverCatalogService $catalog, private readonly StorageDeploymentTargetService $deployment, private readonly StorageDriverHealthService $health, private readonly StorageActivationService $activation) {}

    public function index(Request $request): Response
    {
        $query = StorageDriverConfiguration::query()->with(['latestHealthCheck', 'infrastructureConnection']);
        if ($request->string('archived')->value() === 'archived') {
            $query->onlyTrashed();
        } elseif ($request->string('archived')->value() === 'all') {
            $query->withTrashed();
        }
        $settings = StorageDriverSettings::query()->with('activeConfiguration.infrastructureConnection')->find(1);

        return Inertia::render('Admin/System/Storage/Index', [
            'ownership' => ['mode' => $settings?->getRawOriginal('mode') ?? StorageConfigurationMode::Deployment->value, 'owned' => $settings !== null],
            'effective_disk' => config('filesystems.default'),
            'deployment_target' => $this->deployment->safeTarget(),
            'active_configuration' => $settings?->activeConfiguration instanceof StorageDriverConfiguration ? $this->payload($settings->activeConfiguration) : null,
            'configurations' => $query->latest()->paginate(25)->withQueryString()->through(fn (StorageDriverConfiguration $item) => [...$this->payload($item), 'latest_health' => $item->latestHealthCheck?->only(['status', 'latency_ms', 'message', 'created_at'])]),
            'definitions' => array_map(fn ($definition) => $definition->toArray(), $this->registry->definitions()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/System/Storage/Form', ['configuration' => null, 'definitions' => array_map(fn ($definition) => $definition->toArray(), $this->registry->definitions()), 'connections' => $this->connections()]);
    }

    public function store(StorageDriverConfigurationRequest $request): RedirectResponse
    {
        $this->catalog->create($request->validated(), $request->user());

        return to_route('admin.system.storage.index')->with('success', 'Storage configuration created.');
    }

    public function edit(StorageDriverConfiguration $configuration): Response
    {
        return Inertia::render('Admin/System/Storage/Form', ['configuration' => $this->catalog->safe($configuration), 'definitions' => array_map(fn ($definition) => $definition->toArray(), $this->registry->definitions()), 'connections' => $this->connections($configuration)]);
    }

    public function update(StorageDriverConfigurationRequest $request, StorageDriverConfiguration $configuration): RedirectResponse
    {
        $this->catalog->update($configuration, $request->validated(), $request->user());

        return to_route('admin.system.storage.index')->with('success', 'Storage configuration updated.');
    }

    public function enabled(Request $request, StorageDriverConfiguration $configuration): RedirectResponse
    {
        $request->validate(['is_enabled' => ['required', 'boolean']]);
        $this->catalog->setEnabled($configuration, $request->boolean('is_enabled'), $request->user());

        return back();
    }

    public function destroy(Request $request, StorageDriverConfiguration $configuration): RedirectResponse
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

    public function test(Request $request, StorageDriverConfiguration $configuration): JsonResponse
    {
        return response()->json($this->health->test($configuration, $request->user())->toArray());
    }

    public function activate(Request $request, StorageDriverConfiguration $configuration): RedirectResponse
    {
        return $this->activationResponse($this->activation->activate($configuration, $request->user()));
    }

    public function forceActivate(Request $request, StorageDriverConfiguration $configuration): RedirectResponse
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
        return back()->with($result->restartSignaled ? 'success' : 'error', $result->restartSignaled ? 'Storage ownership updated; queue worker restart signaled.' : $result->warning);
    }

    private function payload(StorageDriverConfiguration $configuration): array
    {
        $connection = $configuration->infrastructureConnection;

        if (! $connection instanceof InfrastructureConnection) {
            return [...$this->catalog->safe($configuration), 'infrastructure_connection' => null];
        }
        $deletedAt = $connection->getAttribute('deleted_at');

        return [...$this->catalog->safe($configuration), 'infrastructure_connection' => ['id' => $connection->id, 'name' => $connection->name, 'type' => $connection->getRawOriginal('type'), 'is_enabled' => $connection->is_enabled, 'archived_at' => $deletedAt instanceof \DateTimeInterface ? $deletedAt->format(DATE_ATOM) : null]];
    }

    private function connections(?StorageDriverConfiguration $configuration = null): array
    {
        $referenced = array_filter([$configuration?->infrastructure_connection_id]);

        return InfrastructureConnection::withTrashed()->whereIn('type', [InfrastructureConnectionType::Aws->value, InfrastructureConnectionType::S3Compatible->value])->where(fn ($query) => $query->where(fn ($query) => $query->whereNull('deleted_at')->where('is_enabled', true))->when($referenced, fn ($query) => $query->orWhereIn('id', $referenced)))->orderBy('name')->get()->map(function (InfrastructureConnection $connection) {
            $deletedAt = $connection->getAttribute('deleted_at');

            return ['id' => $connection->id, 'name' => $connection->name, 'type' => $connection->getRawOriginal('type'), 'is_enabled' => $connection->is_enabled, 'deleted_at' => $deletedAt instanceof \DateTimeInterface ? $deletedAt->format(DATE_ATOM) : null];
        })->all();
    }
}
