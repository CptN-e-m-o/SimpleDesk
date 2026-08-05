<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Admin\AgentRoutingEligibility;
use App\Enums\Admin\AgentStatusAvailability;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AgentStatuses\AgentStatusRequest;
use App\Models\Admin\AgentStatus;
use App\Services\Admin\AgentStatuses\AgentStatusCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AgentStatusController extends Controller
{
    public function __construct(
        private AgentStatusCatalogService $service
    ) {
    }

    public function index(Request $request): Response
    {
        $search = $request
            ->string('search')
            ->toString();

        $availability = $request
            ->string('availability')
            ->toString();

        $routing = $request
            ->string('routing')
            ->toString();

        $type = $request
            ->string('type')
            ->toString();

        $state = $request
            ->string('state', 'active')
            ->toString();

        $query = AgentStatus::query()
            ->withCount([
                'currentlyUsedPeriods',
                'periods',
                'revertPeriods',
            ])
            ->search($search)
            ->when(
                $availability !== '',
                fn ($query) => $query->where(
                    'availability',
                    $availability
                )
            )
            ->when(
                $routing !== '',
                fn ($query) => $query->where(
                    'routing_eligibility',
                    $routing
                )
            )
            ->when(
                $type === 'system',
                fn ($query) => $query->system()
            )
            ->when(
                $type === 'custom',
                fn ($query) => $query->custom()
            );

        if ($state === 'archived') {
            $query->onlyTrashed();
        } elseif ($state === 'inactive') {
            $query->where('is_active', false);
        } else {
            $query->where('is_active', true);
        }

        return Inertia::render(
            'Admin/AgentStatuses/Index',
            [
                'statuses' => $query
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->paginate(15)
                    ->withQueryString(),
                'filters' => $request->only([
                    'search',
                    'availability',
                    'routing',
                    'type',
                    'state',
                ]),
                'permissions' => $request
                    ->user()
                    ->permissionKeys(),
            ]
        );
    }

    public function create(): Response
    {
        return Inertia::render(
            'Admin/AgentStatuses/Form',
            $this->formProps()
        );
    }

    public function store(
        AgentStatusRequest $request
    ): RedirectResponse {
        $this->service->create(
            $request->validated(),
            $request->user()
        );

        return redirect()
            ->route('admin.agent-statuses.index')
            ->with(
                'success',
                'Agent status created.'
            );
    }

    public function edit(
        AgentStatus $agentStatus
    ): Response {
        return Inertia::render(
            'Admin/AgentStatuses/Form',
            [
                ...$this->formProps(),
                'status' => $agentStatus,
            ]
        );
    }

    public function update(
        AgentStatusRequest $request,
        AgentStatus $agentStatus
    ): RedirectResponse {
        $this->service->update(
            $agentStatus,
            $request->validated(),
            $request->user()
        );

        return redirect()
            ->route('admin.agent-statuses.index')
            ->with(
                'success',
                'Agent status updated.'
            );
    }

    public function duplicate(
        Request $request,
        AgentStatus $agentStatus
    ): RedirectResponse {
        $this->service->duplicate(
            $agentStatus,
            $request->user()
        );

        return redirect()
            ->route('admin.agent-statuses.index')
            ->with(
                'success',
                'Agent status duplicated.'
            );
    }

    public function toggle(
        Request $request,
        AgentStatus $agentStatus
    ): RedirectResponse {
        $this->service->setActive(
            $agentStatus,
            ! $agentStatus->is_active,
            $request->user()
        );

        return redirect()
            ->route('admin.agent-statuses.index')
            ->with(
                'success',
                'Agent status state changed.'
            );
    }

    public function destroy(
        Request $request,
        AgentStatus $agentStatus
    ): RedirectResponse {
        $this->service->archive(
            $agentStatus,
            $request->user()
        );

        return redirect()
            ->route(
                'admin.agent-statuses.index',
                [
                    'state' => 'archived',
                ]
            )
            ->with(
                'success',
                'Agent status archived.'
            );
    }

    public function restore(
        Request $request,
        int $agentStatus
    ): RedirectResponse {
        $this->service->restore(
            $agentStatus,
            $request->user()
        );

        return redirect()
            ->route('admin.agent-statuses.index')
            ->with(
                'success',
                'Agent status restored.'
            );
    }

    public function forceDelete(
        int $agentStatus
    ): RedirectResponse {
        $this->service->forceDelete(
            $agentStatus
        );

        return redirect()
            ->route(
                'admin.agent-statuses.index',
                [
                    'state' => 'archived',
                ]
            )
            ->with(
                'success',
                'Agent status permanently deleted.'
            );
    }

    public function makeDefault(
        Request $request,
        AgentStatus $agentStatus
    ): RedirectResponse {
        $this->service->makeDefault(
            $agentStatus,
            $request->user()
        );

        return redirect()
            ->route('admin.agent-statuses.index')
            ->with(
                'success',
                'Default status changed.'
            );
    }

    private function formProps(): array
    {
        return [
            'availabilityOptions' => array_column(
                AgentStatusAvailability::cases(),
                'value'
            ),
            'routingOptions' => array_column(
                AgentRoutingEligibility::cases(),
                'value'
            ),
            'icons' => AgentStatus::ICONS,
        ];
    }
}
