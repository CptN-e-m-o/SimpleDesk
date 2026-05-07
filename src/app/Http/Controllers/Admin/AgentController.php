<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AgentResource;
use App\Models\User\User;
use App\Http\Resources\Auth\UserLoginSessionResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AgentController extends Controller
{
    use AuthorizesRequests;
    public function index(): Response
    {
        $this->authorize('viewAny', User::class);

        $agents = User::query()
            ->whereHas('roles', function ($query) {
                $query->where('type', 'agent');
            })
            ->withTrashed()
            ->with('roles:id,label,name,type')
            ->latest()
            ->get();

        return Inertia::render('Admin/Agents/Index', [
            'agents' => AgentResource::collection($agents)->resolve(),
        ]);
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show(int $agent): Response
    {
        return $this->showProfile($agent, 'agent');
    }

    public function showUser(int $agent): Response
    {
        return $this->showProfile($agent, 'user');
    }

    private function showProfile(int $agentId, string $viewAs): Response
    {
        $agent = User::withTrashed()
            ->whereKey($agentId)
            ->whereHas('roles', fn ($query) => $query->where('type', 'agent'))
            ->with([
                'roles:id,name,label,type',
                'loginSessions' => fn ($query) => $query
                    ->latest('logged_in_at')
                    ->limit(10),
            ])
            ->firstOrFail();

        $this->authorize('view', $agent);

        return Inertia::render('Admin/Agents/Show', [
            'agent' => AgentResource::make($agent)->resolve(),
            'loginSessions' => UserLoginSessionResource::collection($agent->loginSessions)->resolve(),
            'viewAs' => $viewAs,
        ]);
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }

    public function restore()
    {
       //
    }

    public function forceDelete()
    {
        //
    }
}
