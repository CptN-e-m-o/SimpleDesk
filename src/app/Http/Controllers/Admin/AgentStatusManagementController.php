<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Admin\AgentStatusScope;
use App\Enums\Admin\AgentStatusSource;
use App\Enums\Admin\AgentWorkChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AgentStatuses\SetAgentStatusRequest;
use App\Models\Admin\AgentStatus;
use App\Models\User\User;
use App\Services\Admin\AgentStatuses\AgentStatusResolver;
use App\Services\Admin\AgentStatuses\AgentStatusService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AgentStatusManagementController extends Controller
{
    public function __construct(private AgentStatusService $service, private AgentStatusResolver $resolver) {}
    public function show(User $agent) { $resolved=$this->resolver->currentStatus($agent); return Inertia::render('Admin/Agents/Status', ['agent'=>$agent->only(['id','first_name','last_name','email']),'current'=>$this->resolved($resolved),'statuses'=>AgentStatus::selectable()->orderBy('sort_order')->get(),'channels'=>array_column(AgentWorkChannel::cases(),'value')]); }
    public function store(SetAgentStatusRequest $request, User $agent) { $d=$request->validated(); $status=AgentStatus::findOrFail($d['status_id']); $scope=AgentStatusScope::from($d['scope']); $channel=isset($d['channel'])?AgentWorkChannel::from($d['channel']):null; $this->service->setStatus($agent,$status,$scope,$channel,$request->user(),AgentStatusSource::Admin,$d['duration_minutes']??null,isset($d['expires_at'])?CarbonImmutable::parse($d['expires_at']):null,$d['note']??null); return redirect()->route('admin.agents.status.show',$agent)->with('success','Agent status changed.'); }
    public function default(Request $request, User $agent) { $this->service->returnToDefault($agent,$request->user()); return redirect()->route('admin.agents.status.show',$agent)->with('success','Agent returned to default status.'); }
    public function history(Request $request, User $agent) { return Inertia::render('Admin/Agents/StatusHistory',['agent'=>$agent->only(['id','first_name','last_name','email']),'periods'=>$this->resolver->history($agent,$request->only(['status_id','source','scope','channel','from','to'])),'statuses'=>AgentStatus::withTrashed()->orderBy('name')->get(['id','name']),'filters'=>$request->all()]); }
    private function resolved($r): array { return ['status'=>$r->status,'availability'=>$r->availability->value,'routing_eligibility'=>$r->routingEligibility->value,'period'=>$r->globalPeriod]; }
}
