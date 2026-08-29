<?php

namespace App\Http\Controllers\Admin\System\Broadcasting;

use App\Enums\Admin\System\BroadcastConfigurationMode;
use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\System\Broadcasting\BroadcastDriverConfigurationRequest;
use App\Models\Admin\System\BroadcastDriverConfiguration;
use App\Models\Admin\System\BroadcastDriverSettings;
use App\Models\Admin\System\InfrastructureConnection;
use App\Services\Admin\System\Broadcasting\BroadcastActivationService;
use App\Services\Admin\System\Broadcasting\BroadcastDeploymentTargetService;
use App\Services\Admin\System\Broadcasting\BroadcastDriverCatalogService;
use App\Services\Admin\System\Broadcasting\BroadcastDriverHealthService;
use App\Services\Admin\System\Broadcasting\BroadcastDriverRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BroadcastDriverController extends Controller
{
    public function __construct(private readonly BroadcastDriverRegistry $registry, private readonly BroadcastDriverCatalogService $catalog, private readonly BroadcastDeploymentTargetService $deployment, private readonly BroadcastDriverHealthService $health, private readonly BroadcastActivationService $activation) {}

    public function index(Request $request): Response
    {
        $query = BroadcastDriverConfiguration::query()->with(['latestHealthCheck', 'infrastructureConnection']);
        if ($request->string('archived')->value() === 'archived') {
            $query->onlyTrashed();
        } elseif ($request->string('archived')->value() === 'all') {
            $query->withTrashed();
        }
        $settings = BroadcastDriverSettings::query()->with('activeConfiguration')->find(1);

        return Inertia::render('Admin/System/Broadcasting/Index', ['ownership' => ['mode' => $settings?->mode->value ?? BroadcastConfigurationMode::Deployment->value, 'owned' => $settings !== null], 'effective_connection' => config('broadcasting.default'), 'deployment_target' => $this->deployment->safeTarget(), 'active_configuration' => $settings?->activeConfiguration ? $this->catalog->safe($settings->activeConfiguration) : null, 'configurations' => $query->latest()->paginate(25)->withQueryString()->through(fn ($item) => [...$this->catalog->safe($item), 'latest_health' => $item->latestHealthCheck?->only(['status', 'latency_ms', 'message', 'created_at'])]), 'definitions' => array_map(fn ($definition) => $definition->toArray(), $this->registry->definitions())]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/System/Broadcasting/Form', ['configuration' => null, 'definitions' => array_map(fn ($definition) => $definition->toArray(), $this->registry->definitions()), 'connections' => $this->connections()]);
    }

    public function store(BroadcastDriverConfigurationRequest $request): RedirectResponse
    {
        $this->catalog->create($request->validated(), $request->user());

        return to_route('admin.system.broadcasting.index')->with('success', 'Real-time configuration created.');
    }

    public function edit(BroadcastDriverConfiguration $configuration): Response
    {
        return Inertia::render('Admin/System/Broadcasting/Form', ['configuration' => $this->catalog->safe($configuration), 'definitions' => array_map(fn ($definition) => $definition->toArray(), $this->registry->definitions()), 'connections' => $this->connections($configuration)]);
    }

    public function update(BroadcastDriverConfigurationRequest $request, BroadcastDriverConfiguration $configuration): RedirectResponse
    {
        $this->catalog->update($configuration, $request->validated(), $request->user());

        return to_route('admin.system.broadcasting.index')
            ->with('success', 'Real-time configuration updated.');
    }

    public function enabled(Request $request, BroadcastDriverConfiguration $configuration): RedirectResponse
    {
        $request->validate(['is_enabled' => ['required', 'boolean']]);
        $this->catalog->setEnabled($configuration, $request->boolean('is_enabled'), $request->user());

        return back();
    }

    public function destroy(Request $request, BroadcastDriverConfiguration $configuration): RedirectResponse
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

    public function test(Request $request, BroadcastDriverConfiguration $configuration): JsonResponse
    {
        return response()->json($this->health->test($configuration, $request->user())->toArray());
    }

    public function activate(Request $request, BroadcastDriverConfiguration $configuration): RedirectResponse
    {
        return $this->activationResponse($this->activation->activate($configuration, $request->user()));
    }

    public function forceActivate(Request $request, BroadcastDriverConfiguration $configuration): RedirectResponse
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
        return back()->with($result->restartSignaled ? 'success' : 'error', $result->restartSignaled ? 'Real-time ownership updated; queue worker restart signaled.' : 'Real-time ownership was updated, but queue worker restart signaling failed.');
    }

    private function connections(?BroadcastDriverConfiguration $configuration = null): array
    {
        $types = [InfrastructureConnectionType::Reverb->value, InfrastructureConnectionType::Pusher->value];
        $referenced = array_filter([$configuration?->infrastructure_connection_id]);

        return InfrastructureConnection::withTrashed()->whereIn('type', $types)->where(fn ($query) => $query->where(fn ($query) => $query->whereNull('deleted_at')->where('is_enabled', true))->when($referenced, fn ($query) => $query->orWhereIn('id', $referenced)))->orderBy('name')->get()->map(fn ($connection) => ['id' => $connection->id, 'name' => $connection->name, 'type' => $connection->type->value, 'is_enabled' => $connection->is_enabled, 'deleted_at' => $connection->deleted_at?->toIso8601String()])->all();
    }
}
