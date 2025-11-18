<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Agent\AgentIndexRequest;
use App\Http\Requests\Admin\Agent\DeactivateAgentsRequest;
use App\Http\Requests\Admin\Agent\StoreAgentRequest;
use App\Http\Requests\Admin\Agent\UpdateAgentRequest;
use App\Models\User;
use App\Services\Admin\Agent\AgentService;
use App\Services\TimezoneService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AgentsController extends Controller
{
    public function index(AgentIndexRequest $request, AgentService $service): View
    {
        $agents = $service->list(
            $request->filters(),
            $request->sortBy(),
            $request->direction(),
            $request->perPage()
        )->appends($request->query());

        $options = [10, 20, 50];

        $view = $request->ajax()
            ? 'admin.agents-list.partials.agent_table'
            : 'admin.agents-list.index';

        return view($view, [
            'agents' => $agents,
            'perPage' => $request->perPage(),
            'sortBy' => $request->sortBy(),
            'direction' => $request->direction(),
            'options' => $options,
        ]);
    }

    public function deactivateBulk(DeactivateAgentsRequest $request, AgentService $service): RedirectResponse
    {
        try {
            $service->deactivateBulk(
                $request->agentIds(),
                $request->requestedTicketsAction(),
                $request->newRequesterId()
            );
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Произошла ошибка в ходе деактивации.');
        }

        return redirect()->back()->with('success', 'Выбранные агенты были успешно деактивированы.');
    }

    public function create(TimezoneService $timezoneService): View
    {
        return view('admin.agents-list.create', [
            'timezones' => $timezoneService->getUniqueFormattedList(),
        ]);
    }

    public function store(StoreAgentRequest $request, AgentService $service): RedirectResponse
    {
        $service->createAgent($request->validatedData());

        return redirect()->route('admin.agents.index');
    }

    public function edit(TimezoneService $timezoneService, User $agent): View
    {
        return view('admin.agents-list.edit', [
            'timezones' => $timezoneService->getUniqueFormattedList(),
            'agent' => $agent,
        ]);
    }

    public function update(UpdateAgentRequest $request, User $agent, AgentService $service): RedirectResponse
    {
        $service->updateAgent($agent, $request->validatedData());

        return redirect()->route('admin.agents.index');
    }
}
