<?php

namespace App\Services\Admin\AgentStatuses;

use App\Enums\Admin\AgentRoutingEligibility;
use App\Enums\Admin\AgentStatusAvailability;
use App\Models\Admin\AgentStatus;
use App\Models\Admin\AgentStatusPeriod;

final readonly class ResolvedAgentStatus
{
    public function __construct(public AgentStatus $status, public AgentStatusAvailability $availability, public AgentRoutingEligibility $routingEligibility, public ?AgentStatusPeriod $globalPeriod, public ?AgentStatusPeriod $channelPeriod) {}
}
