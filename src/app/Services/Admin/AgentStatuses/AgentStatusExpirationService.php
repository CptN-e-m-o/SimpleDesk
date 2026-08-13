<?php

namespace App\Services\Admin\AgentStatuses;

use App\Enums\Admin\AgentStatusEndReason;
use App\Enums\Admin\AgentStatusSource;
use App\Models\Admin\AgentStatus;
use App\Models\Admin\AgentStatusPeriod;
use Illuminate\Support\Facades\DB;
use Throwable;

class AgentStatusExpirationService
{
    public function expireDueStatuses(): array
    {
        $result = ['found' => 0, 'expired' => 0, 'skipped' => 0, 'errors' => 0];
        AgentStatusPeriod::expired()->orderBy('id')->chunkById(100, function ($periods) use (&$result) {
            foreach ($periods as $period) {
                $result['found']++;
                try {
                    $this->expirePeriod($period) ? $result['expired']++ : $result['skipped']++;
                } catch (Throwable $e) {
                    report($e);
                    $result['errors']++;
                }
            }
        });

        return $result;
    }

    public function expirePeriod(AgentStatusPeriod $period): bool
    {
        return DB::transaction(function () use ($period) {
            $period = AgentStatusPeriod::lockForUpdate()->find($period->id);
            if (! $period || $period->ended_at || ! $period->expires_at || $period->expires_at->isFuture()) {
                return false;
            }
            $period->update(['ended_at' => now(), 'end_reason' => AgentStatusEndReason::Expired, 'ended_by' => null]);
            $revert = AgentStatus::active()->find($period->revert_to_status_id) ?? AgentStatus::active()->where('is_default', true)->firstOrFail();
            $open = AgentStatusPeriod::forAgent($period->user_id)->open()->where('scope', $period->scope->value)->when($period->channel, fn ($q) => $q->where('channel', $period->channel->value), fn ($q) => $q->whereNull('channel'))->lockForUpdate()->exists();
            if (! $open) {
                AgentStatusPeriod::create(['user_id' => $period->user_id, 'agent_status_id' => $revert->id, 'scope' => $period->scope, 'channel' => $period->channel, 'started_at' => now(), 'source' => AgentStatusSource::System]);
            }

            return true;
        });
    }
}
