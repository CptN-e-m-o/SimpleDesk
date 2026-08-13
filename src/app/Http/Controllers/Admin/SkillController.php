<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Admin\Skills\SkillMatchType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Skills\SkillRequest;
use App\Models\Admin\Skill;
use App\Services\Admin\Skills\SkillCatalogService;
use App\Services\Admin\Skills\SkillRuleFieldRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SkillController extends Controller
{
    public function __construct(
        private SkillCatalogService $service,
        private SkillRuleFieldRegistry $registry
    ) {}

    public function index(Request $request): Response
    {
        $state = $request->string('state', 'active')->toString();
        $matchType = $request->string('match_type')->toString();
        $query = Skill::query()
            ->with('rules')
            ->withCount('rules')
            ->search($request->string('search')->toString())
            ->when($matchType !== '', fn ($query) => $query->where('match_type', $matchType));

        if ($state === 'archived') {
            $query->onlyTrashed();
        } elseif ($state === 'inactive') {
            $query->where('is_active', false);
        } else {
            $query->where('is_active', true);
        }

        return Inertia::render('Admin/Skills/Index', [
            'skills' => $query->orderBy('sort_order')->orderBy('name')->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'match_type', 'state']),
            'permissions' => $request->user()->permissionKeys(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Skills/Form', $this->formProps());
    }

    public function store(SkillRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return redirect()->route('admin.skills.index')->with('success', 'Skill created.');
    }

    public function edit(Skill $skill): Response
    {
        return Inertia::render('Admin/Skills/Form', [
            ...$this->formProps(),
            'skill' => $this->skillPayload($skill),
        ]);
    }

    public function update(SkillRequest $request, Skill $skill): RedirectResponse
    {
        $this->service->update($skill, $request->validated(), $request->user());

        return redirect()->route('admin.skills.index')->with('success', 'Skill updated.');
    }

    public function duplicate(Request $request, Skill $skill): RedirectResponse
    {
        $this->service->duplicate($skill, $request->user());

        return redirect()->route('admin.skills.index')->with('success', 'Skill duplicated.');
    }

    public function toggle(Request $request, Skill $skill): RedirectResponse
    {
        $this->service->setActive($skill, ! $skill->is_active, $request->user());

        return redirect()->route('admin.skills.index')->with('success', 'Skill state changed.');
    }

    public function destroy(Request $request, Skill $skill): RedirectResponse
    {
        $this->service->archive($skill, $request->user());

        return redirect()->route('admin.skills.index', ['state' => 'archived'])->with('success', 'Skill archived.');
    }

    public function restore(Request $request, int $skill): RedirectResponse
    {
        $this->service->restore($skill, $request->user());

        return redirect()->route('admin.skills.index')->with('success', 'Skill restored.');
    }

    public function forceDelete(int $skill): RedirectResponse
    {
        $this->service->forceDelete($skill);

        return redirect()->route('admin.skills.index', ['state' => 'archived'])->with('success', 'Skill permanently deleted.');
    }

    private function formProps(): array
    {
        return [
            'matchTypeOptions' => array_column(SkillMatchType::cases(), 'value'),
            'ruleSchema' => $this->registry->schema(),
        ];
    }

    private function skillPayload(Skill $skill): array
    {
        $payload = $skill->load('rules')->toArray();
        $payload['rules'] = collect($payload['rules'])->map(function (array $rule): array {
            if (
                is_array($rule['value'])
                && ! in_array($rule['operator'], ['in', 'not_in', 'between'], true)
            ) {
                $rule['value'] = $rule['value'][0] ?? null;
            }

            return $rule;
        })->all();

        return $payload;
    }
}
