<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Teams\StoreTeamRequest;
use App\Http\Requests\Admin\Teams\UpdateTeamRequest;
use App\Http\Resources\Admin\TeamFormResource;
use App\Http\Resources\Admin\TeamResource;
use App\Models\Admin\Team;
use App\Services\Admin\TeamService;
use App\Support\Teams\TeamDepartmentOptions;
use App\Support\Teams\TeamEligibleUsers;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected TeamService $teamService,
        protected TeamEligibleUsers $teamEligibleUsers,
        protected TeamDepartmentOptions $teamDepartmentOptions,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Team::class);

        $teams = Team::query()
            ->withTrashed()
            ->with(['members:id,name'])
            ->withCount('members')
            ->latest()
            ->get();

        return Inertia::render('Admin/Teams/Index', [
            'teams' => TeamResource::collection($teams)->resolve(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Team::class);

        return Inertia::render('Admin/Teams/Create', [
            'departments' => $this->teamDepartmentOptions->options(),
            'users' => $this->teamEligibleUsers->forSelect(),
        ]);
    }

    public function store(StoreTeamRequest $request): RedirectResponse
    {
        $this->authorize('create', Team::class);
        $this->teamService->create($request->validated());

        return redirect()
            ->route('admin.teams.index')
            ->with('success', 'Team created successfully.');
    }

    public function show(Team $team): Response
    {
        $this->authorize('view', $team);

        $team->load([
            'members' => function ($query) {
                $query->select('users.id', 'name', 'email');
            },
        ]);

        return Inertia::render('Admin/Teams/Show', [
            'team' => TeamResource::make($team)->resolve(),
        ]);
    }

    public function edit(Team $team): Response
    {
        $this->authorize('update', $team);

        $team->load([
            'departments:id,name,slug',
            'members:id,name,email',
        ]);

        return Inertia::render('Admin/Teams/Edit', [
            'team' => TeamFormResource::make($team)->resolve(),
            'departments' => $this->teamDepartmentOptions->options(),
            'users' => $this->teamEligibleUsers->forSelect(),
        ]);
    }

    public function update(UpdateTeamRequest $request, Team $team): RedirectResponse
    {
        $this->authorize('update', $team);

        $this->teamService->update($team, $request->validated());

        return redirect()
            ->route('admin.teams.index')
            ->with('success', 'Team updated successfully.');
    }

    public function destroy(Team $team): RedirectResponse
    {
        $this->authorize('delete', $team);

        $team->delete();

        return redirect()
            ->route('admin.teams.index')
            ->with('success', 'Team deleted successfully.');
    }

    public function restore(Team $team): RedirectResponse
    {
        $this->authorize('restore', $team);

        $team->restore();

        return redirect()
            ->route('admin.teams.index')
            ->with('success', 'Team restored successfully.');
    }

    public function forceDelete(Team $team): RedirectResponse
    {
        $this->authorize('forceDelete', $team);
        $team->forceDelete();

        return redirect()
            ->route('admin.teams.index')
            ->with('success', 'Team permanently deleted successfully.');
    }
}
