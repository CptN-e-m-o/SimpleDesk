<?php

namespace App\Http\Controllers\Agent;

use App\Enums\Admin\AgentStatusSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\SetOwnAgentStatusRequest;
use App\Models\Admin\AgentStatus;
use App\Services\Admin\AgentStatuses\AgentStatusResolver;
use App\Services\Admin\AgentStatuses\AgentStatusService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OwnAgentStatusController extends Controller
{
    public function __construct(private AgentStatusService $service, private AgentStatusResolver $resolver) {}

    public function store(SetOwnAgentStatusRequest $request)
    {
        $d = $request->validated();
        $this->service->setGlobalStatus($request->user(), AgentStatus::findOrFail($d['status_id']), $request->user(), AgentStatusSource::Self, $d['duration_minutes'] ?? null, null, $d['note'] ?? null);

        return redirect()->back()->with('success', 'Status changed.');
    }

    public function default(Request $request)
    {
        $this->service->returnToDefault($request->user(), $request->user(), AgentStatusSource::Self);

        return redirect()->back()->with('success', 'Returned to default status.');
    }

    public function history(Request $request)
    {
        return Inertia::render('Agent/StatusHistory', ['periods' => $this->resolver->history($request->user(), $request->only(['status_id', 'source', 'from', 'to']))]);
    }
}
