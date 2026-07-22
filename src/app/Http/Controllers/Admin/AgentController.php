<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Agents\StoreAgentRequest;
use App\Http\Requests\Admin\Agents\UpdateAgentRequest;
use App\Http\Resources\Admin\AgentFormResource;
use App\Http\Resources\Admin\AgentResource;
use App\Http\Resources\Auth\UserLoginSessionResource;
use App\Models\User\User;
use App\Services\Admin\AgentService;
use App\Support\Agents\AgentDepartmentOptions;
use App\Support\Agents\AgentRoleOptions;
use App\Support\Agents\AgentTeamOptions;
use App\Support\Agents\AgentTimezoneOptions;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AgentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected AgentService $agentService,
        protected AgentDepartmentOptions $agentDepartmentOptions,
        protected AgentRoleOptions $agentRoleOptions,
        protected AgentTeamOptions $agentTeamOptions,
        protected AgentTimezoneOptions $agentTimezoneOptions,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', User::class);

        $agents = User::query()
            ->whereHas('roles', fn ($query) => $query->where('type', 'agent'))
            ->withTrashed()
            ->with('roles:id,label,name,type')
            ->latest()
            ->get();

        return Inertia::render('Admin/Agents/Index', [
            'agents' => AgentResource::collection($agents)->resolve(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('Admin/Agents/Create', $this->formOptions());
    }

    public function store(StoreAgentRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $this->agentService->create($request->validated());

        return redirect()
            ->route('admin.agents.index')
            ->with('success', 'Agent created successfully.');
    }

    public function show(User $agent): Response
    {
        return $this->showProfile($agent, 'agent');
    }

    public function showUser(User $agent): Response
    {
        return $this->showProfile($agent, 'user');
    }

    public function edit(User $agent): Response
    {
        $agent = $this->agent($agent);

        $this->authorize('update', $agent);

        $agent->load([
            'roles:id,name,label,type',
            'departments:id,name',
            'teams:id,name',
        ]);

        return Inertia::render('Admin/Agents/Edit', [
            'agent' => AgentFormResource::make($agent)->resolve(),
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateAgentRequest $request, User $agent): RedirectResponse
    {
        $agent = $this->agent($agent);

        $this->authorize('update', $agent);

        $this->agentService->update($agent, $request->validated());

        return redirect()
            ->route('admin.agents.index')
            ->with('success', 'Agent updated successfully.');
    }

    public function destroy(User $agent): RedirectResponse
    {
        $agent = $this->agent($agent);

        $this->authorize('delete', $agent);

        $agent->delete();

        return redirect()
            ->route('admin.agents.index')
            ->with('success', 'Agent deleted successfully.');
    }

    public function restore(User $agent): RedirectResponse
    {
        $agent = $this->agent($agent);

        abort_unless($agent->trashed(), 404);

        $this->authorize('restore', $agent);

        $agent->restore();

        return redirect()
            ->route('admin.agents.index')
            ->with('success', 'Agent restored successfully.');
    }

    public function forceDelete(User $agent): RedirectResponse
    {
        $agent = $this->agent($agent);

        abort_unless($agent->trashed(), 404);

        $this->authorize('forceDelete', $agent);

        $agent->forceDelete();

        return redirect()
            ->route('admin.agents.index')
            ->with('success', 'Agent permanently deleted successfully.');
    }

    private function showProfile(User $agent, string $viewAs): Response
    {
        $agent = $this->agent($agent);

        $this->authorize('view', $agent);

        $agent->load([
            'roles:id,name,label,type',
            'loginSessions' => fn ($query) => $query
                ->latest('logged_in_at')
                ->limit(10),
        ]);

        return Inertia::render('Admin/Agents/Show', [
            'agent' => AgentResource::make($agent)->resolve(),
            'loginSessions' => UserLoginSessionResource::collection($agent->loginSessions)->resolve(),
            'viewAs' => $viewAs,
        ]);
    }

    private function agent(User $user): User
    {
        abort_unless(
            $user->roles()
                ->where('type', 'agent')
                ->exists(),
            404
        );

        return $user;
    }

    private function formOptions(): array
    {
        return [
            'departments' => $this->agentDepartmentOptions->options(),
            'roles' => $this->agentRoleOptions->options(),
            'teams' => $this->agentTeamOptions->options(),
            'timezones' => $this->agentTimezoneOptions->options(),
        ];
    }
}
