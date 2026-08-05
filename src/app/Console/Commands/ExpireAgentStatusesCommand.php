<?php

namespace App\Console\Commands;

use App\Services\Admin\AgentStatuses\AgentStatusExpirationService;
use Illuminate\Console\Command;

class ExpireAgentStatusesCommand extends Command
{
    protected $signature = 'simpledesk:agent-statuses:expire';
    protected $description = 'Expire temporary agent statuses and restore their previous/default status';
    public function handle(AgentStatusExpirationService $service): int { $r = $service->expireDueStatuses(); $this->info("Found: {$r['found']}; expired: {$r['expired']}; skipped: {$r['skipped']}; errors: {$r['errors']}."); return $r['errors'] ? self::FAILURE : self::SUCCESS; }
}
