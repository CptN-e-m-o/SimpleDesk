<?php

namespace App\Services\Admin\AgentStatuses;

use App\Enums\Admin\AgentStatusEndReason;
use App\Enums\Admin\AgentStatusScope;
use App\Enums\Admin\AgentStatusSource;
use App\Enums\Admin\AgentWorkChannel;
use App\Models\Admin\AgentStatus;
use App\Models\Admin\AgentStatusPeriod;
use App\Models\User\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AgentStatusService
{
    public const MAX_DURATION_MINUTES = 43200;
    public function setGlobalStatus(User $agent, AgentStatus $status, ?User $actor = null, AgentStatusSource $source = AgentStatusSource::Admin, ?int $durationMinutes = null, ?CarbonInterface $expiresAt = null, ?string $note = null): AgentStatusPeriod { return $this->setStatus($agent, $status, AgentStatusScope::Global, null, $actor, $source, $durationMinutes, $expiresAt, $note); }
    public function setChannelStatus(User $agent, AgentStatus $status, AgentWorkChannel $channel, ?User $actor = null, AgentStatusSource $source = AgentStatusSource::Admin, ?int $durationMinutes = null, ?CarbonInterface $expiresAt = null, ?string $note = null): AgentStatusPeriod { return $this->setStatus($agent, $status, AgentStatusScope::Channel, $channel, $actor, $source, $durationMinutes, $expiresAt, $note); }
    public function setStatus(User $agent, AgentStatus $status, AgentStatusScope $scope, ?AgentWorkChannel $channel, ?User $actor, AgentStatusSource $source, ?int $durationMinutes = null, ?CarbonInterface $expiresAt = null, ?string $note = null): AgentStatusPeriod
    {
        return DB::transaction(function () use ($agent, $status, $scope, $channel, $actor, $source, $durationMinutes, $expiresAt, $note) {
            if (! $agent->roles()->where('type', 'agent')->exists()) throw ValidationException::withMessages(['agent' => 'Only agents can receive an agent status.']);
            $status = AgentStatus::query()->find($status->id);
            if (! $status || ! $status->is_active) throw ValidationException::withMessages(['status' => 'Status must be active and not archived.']);
            if ($source === AgentStatusSource::Self && ! $status->is_selectable) throw ValidationException::withMessages(['status' => 'This status is not selectable by agents.']);
            if ($scope === AgentStatusScope::Channel && ! $channel) throw ValidationException::withMessages(['channel' => 'Channel is required.']);
            $startedAt = now();
            if ($expiresAt && $durationMinutes) throw ValidationException::withMessages(['expires_at' => 'Choose duration or an expiration time, not both.']);
            $minutes = $durationMinutes ?? ($expiresAt ? null : $status->default_duration_minutes);
            if ($minutes !== null && ($minutes < 1 || $minutes > self::MAX_DURATION_MINUTES)) throw ValidationException::withMessages(['duration_minutes' => 'Duration must be between 1 and '.self::MAX_DURATION_MINUTES.' minutes.']);
            $expiration = $expiresAt?->toImmutable() ?? ($minutes ? $startedAt->copy()->addMinutes($minutes) : null);
            if ($expiration && ($expiration->lte($startedAt) || $expiration->gt($startedAt->copy()->addMinutes(self::MAX_DURATION_MINUTES)))) throw ValidationException::withMessages(['expires_at' => 'Expiration must be in the future and within 30 days.']);
            $query = AgentStatusPeriod::forAgent($agent)->open()->where('scope', $scope->value)->when($scope === AgentStatusScope::Global, fn ($q) => $q->whereNull('channel'), fn ($q) => $q->where('channel', $channel?->value));
            $current = $query->lockForUpdate()->latest('started_at')->first();
            if ($current && $current->agent_status_id === $status->id && optional($current->expires_at)?->equalTo($expiration) !== false && (string) $current->note === (string) $note) return $current;
            $revertId = $current ? $current->agent_status_id : AgentStatus::active()->where('is_default', true)->value('id');
            if ($current) $current->update(['ended_at' => $startedAt, 'ended_by' => $actor?->id, 'end_reason' => AgentStatusEndReason::Replaced]);
            return AgentStatusPeriod::create(['user_id' => $agent->id, 'agent_status_id' => $status->id, 'scope' => $scope, 'channel' => $channel, 'started_at' => $startedAt, 'expires_at' => $expiration, 'revert_to_status_id' => $expiration ? $revertId : null, 'note' => $note, 'source' => $source, 'set_by' => $actor?->id]);
        });
    }
    public function clearStatus(User $agent, AgentStatusScope $scope = AgentStatusScope::Global, ?AgentWorkChannel $channel = null, ?User $actor = null, AgentStatusEndReason $reason = AgentStatusEndReason::Cleared): void { DB::transaction(fn () => AgentStatusPeriod::forAgent($agent)->open()->where('scope', $scope->value)->when($scope === AgentStatusScope::Channel, fn ($q) => $q->where('channel', $channel?->value), fn ($q) => $q->whereNull('channel'))->lockForUpdate()->update(['ended_at' => now(), 'ended_by' => $actor?->id, 'end_reason' => $reason->value])); }
    public function returnToDefault(User $agent, ?User $actor = null, AgentStatusSource $source = AgentStatusSource::Admin): AgentStatusPeriod { $default = AgentStatus::active()->where('is_default', true)->firstOrFail(); return $this->setGlobalStatus($agent, $default, $actor, $source, null, null); }
}
