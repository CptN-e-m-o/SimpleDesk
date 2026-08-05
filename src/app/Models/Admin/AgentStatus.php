<?php

namespace App\Models\Admin;

use App\Enums\Admin\AgentRoutingEligibility;
use App\Enums\Admin\AgentStatusAvailability;
use App\Models\User\User;
use Database\Factories\Admin\AgentStatusFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property AgentStatusAvailability $availability
 * @property AgentRoutingEligibility $routing_eligibility
 */
class AgentStatus extends Model
{
    /** @use HasFactory<AgentStatusFactory> */
    use HasFactory, SoftDeletes;

    public const ICONS = [
        'activity',
        'alarm-clock',
        'badge-check',
        'ban',
        'bell',
        'book-open',
        'brain',
        'briefcase-business',
        'calendar-clock',
        'calendar-days',
        'check-circle',
        'circle-check',
        'circle-dot',
        'circle-pause',
        'circle-slash',
        'clock',
        'coffee',
        'contact',
        'focus',
        'graduation-cap',
        'headphones',
        'heart',
        'hourglass',
        'info',
        'laptop',
        'lightbulb',
        'lock',
        'message-circle',
        'messages-square',
        'moon',
        'phone',
        'presentation',
        'refresh-cw',
        'route',
        'shield',
        'shield-check',
        'sparkles',
        'star',
        'sun',
        'timer',
        'triangle-alert',
        'user-check',
        'user-clock',
        'user-round-check',
        'users',
        'utensils',
        'workflow',
        'wrench',
        'zap',
    ];
    protected $fillable = ['name', 'slug', 'description', 'availability', 'routing_eligibility', 'icon', 'color', 'default_duration_minutes', 'is_system', 'is_default', 'is_active', 'is_selectable', 'sort_order', 'created_by', 'updated_by'];
    protected function casts(): array { return ['availability' => AgentStatusAvailability::class, 'routing_eligibility' => AgentRoutingEligibility::class, 'is_system' => 'boolean', 'is_default' => 'boolean', 'is_active' => 'boolean', 'is_selectable' => 'boolean', 'default_duration_minutes' => 'integer']; }
    public function periods(): HasMany { return $this->hasMany(AgentStatusPeriod::class); }
    public function revertPeriods(): HasMany { return $this->hasMany(AgentStatusPeriod::class, 'revert_to_status_id'); }
    public function currentlyUsedPeriods(): HasMany { return $this->periods()->whereNull('ended_at'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
    public function scopeActive($q) { return $q->whereNull('deleted_at')->where('is_active', true); }
    public function scopeSelectable($q) { return $q->active()->where('is_selectable', true); }
    public function scopeSystem($q) { return $q->where('is_system', true); }
    public function scopeCustom($q) { return $q->where('is_system', false); }
    public function scopeAvailability($q, AgentStatusAvailability|string $value) { return $q->where('availability', $value instanceof AgentStatusAvailability ? $value->value : $value); }
    public function scopeRoutingEligibility($q, AgentRoutingEligibility|string $value) { return $q->where('routing_eligibility', $value instanceof AgentRoutingEligibility ? $value->value : $value); }
    public function scopeSearch($q, ?string $search) { return $q->when($search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%$search%")->orWhere('description', 'like', "%$search%"))); }
    public function scopeArchived($q) { return $q->onlyTrashed(); }
}
