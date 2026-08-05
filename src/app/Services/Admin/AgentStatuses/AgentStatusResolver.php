<?php

namespace App\Services\Admin\AgentStatuses;

use App\Enums\Admin\AgentRoutingEligibility;
use App\Enums\Admin\AgentStatusAvailability;
use App\Enums\Admin\AgentWorkChannel;
use App\Models\Admin\AgentStatus;
use App\Models\Admin\AgentStatusPeriod;
use App\Models\User\User;
use Carbon\CarbonInterface;

class AgentStatusResolver
{
    public function currentPeriod(User $agent, ?AgentWorkChannel $channel = null, ?CarbonInterface $at = null): ?AgentStatusPeriod
    {
        $at ??= now();
        $global = AgentStatusPeriod::with('status')->forAgent($agent)->global()->activeAt($at)->latest('started_at')->first();
        if (! $channel) return $global;
        return AgentStatusPeriod::with('status')->forAgent($agent)->forChannel($channel)->activeAt($at)->latest('started_at')->first() ?? $global;
    }
    public function currentStatus(User $agent, ?AgentWorkChannel $channel = null, ?CarbonInterface $at = null): ResolvedAgentStatus
    {
        $at ??= now();
        $global = AgentStatusPeriod::with('status')->forAgent($agent)->global()->activeAt($at)->latest('started_at')->first();
        $channelPeriod = $channel ? AgentStatusPeriod::with('status')->forAgent($agent)->forChannel($channel)->activeAt($at)->latest('started_at')->first() : null;
        $base = $global ? $global->status : AgentStatus::active()->where('is_default', true)->firstOrFail();
        $availability = $base->availability; $routing = $base->routing_eligibility; $display = $base;
        if ($channelPeriod) {
            if ($channelPeriod->status->availability->weight() > $availability->weight()) { $availability = $channelPeriod->status->availability; $display = $channelPeriod->status; }
            if ($channelPeriod->status->routing_eligibility->weight() > $routing->weight()) { $routing = $channelPeriod->status->routing_eligibility; $display = $channelPeriod->status; }
        }
        return new ResolvedAgentStatus($display, $availability, $routing, $global, $channelPeriod);
    }
    public function availabilityFor(User $agent, ?AgentWorkChannel $channel = null, ?CarbonInterface $at = null): AgentStatusAvailability { return $this->currentStatus($agent, $channel, $at)->availability; }
    public function routingEligibilityFor(User $agent, ?AgentWorkChannel $channel = null, ?CarbonInterface $at = null): AgentRoutingEligibility { return $this->currentStatus($agent, $channel, $at)->routingEligibility; }
    public function canReceiveNewWork(User $agent, ?AgentWorkChannel $channel = null, ?CarbonInterface $at = null): bool { $resolved = $this->currentStatus($agent, $channel, $at); return $resolved->availability === AgentStatusAvailability::Available && $resolved->routingEligibility === AgentRoutingEligibility::Eligible; }
    public function history(User $agent, array $filters = [], int $perPage = 25) { return AgentStatusPeriod::with(['status', 'setBy', 'endedBy'])->forAgent($agent)->when($filters['status_id'] ?? null, fn ($q, $v) => $q->where('agent_status_id', $v))->when($filters['source'] ?? null, fn ($q, $v) => $q->where('source', $v))->when($filters['scope'] ?? null, fn ($q, $v) => $q->where('scope', $v))->when($filters['channel'] ?? null, fn ($q, $v) => $q->where('channel', $v))->when($filters['from'] ?? null, fn ($q, $v) => $q->where('started_at', '>=', $v))->when($filters['to'] ?? null, fn ($q, $v) => $q->where('started_at', '<=', $v))->history()->paginate($perPage)->withQueryString(); }
}
