<?php

namespace App\Models\Admin;

use App\Enums\Admin\AgentStatusEndReason;
use App\Enums\Admin\AgentStatusScope;
use App\Enums\Admin\AgentStatusSource;
use App\Enums\Admin\AgentWorkChannel;
use App\Models\User\User;
use Carbon\CarbonImmutable;
use Database\Factories\Admin\AgentStatusPeriodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property AgentStatusScope $scope
 * @property AgentWorkChannel|null $channel
 * @property CarbonImmutable|null $expires_at
 * @property AgentStatus $status
 */
class AgentStatusPeriod extends Model
{
    /** @use HasFactory<AgentStatusPeriodFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'agent_status_id', 'scope', 'channel', 'started_at', 'ended_at', 'expires_at', 'revert_to_status_id', 'note', 'source', 'set_by', 'ended_by', 'end_reason'];

    protected function casts(): array
    {
        return ['scope' => AgentStatusScope::class, 'channel' => AgentWorkChannel::class, 'source' => AgentStatusSource::class, 'end_reason' => AgentStatusEndReason::class, 'started_at' => 'immutable_datetime', 'ended_at' => 'immutable_datetime', 'expires_at' => 'immutable_datetime'];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(AgentStatus::class, 'agent_status_id')->withTrashed();
    }

    public function revertToStatus(): BelongsTo
    {
        return $this->belongsTo(AgentStatus::class, 'revert_to_status_id')->withTrashed();
    }

    public function setBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'set_by');
    }

    public function endedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ended_by');
    }

    public function scopeOpen($q)
    {
        return $q->whereNull('ended_at');
    }

    public function scopeExpired($q, $at = null)
    {
        return $q->open()->whereNotNull('expires_at')->where('expires_at', '<=', $at ?? now());
    }

    public function scopeGlobal($q)
    {
        return $q->where('scope', AgentStatusScope::Global->value);
    }

    public function scopeForChannel($q, AgentWorkChannel|string $channel)
    {
        return $q->where('scope', AgentStatusScope::Channel->value)->where('channel', $channel instanceof AgentWorkChannel ? $channel->value : $channel);
    }

    public function scopeForAgent($q, User|int $agent)
    {
        return $q->where('user_id', $agent instanceof User ? $agent->id : $agent);
    }

    public function scopeActiveAt($q, $at)
    {
        return $q->where('started_at', '<=', $at)->where(fn ($q) => $q->whereNull('ended_at')->orWhere('ended_at', '>', $at))->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', $at));
    }

    public function scopeHistory($q)
    {
        return $q->orderByDesc('started_at')->orderByDesc('id');
    }
}
