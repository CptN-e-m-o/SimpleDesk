<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Resources\Admin\AgentResource;
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

    public function show(string $id)
    {
        //
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
