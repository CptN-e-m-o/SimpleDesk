<?php

namespace Database\Seeders;

use App\Models\Admin\AgentStatus;
use App\Models\Admin\AgentStatusPeriod;
use App\Models\User\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AgentStatusSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $definitions = [
                ['Available', 'available', 'available', 'eligible', 'circle-check', '#22C55E', null, true, true],
                ['Busy', 'busy', 'limited', 'fallback', 'circle-dot', '#F59E0B', null, true, false],
                ['Away', 'away', 'unavailable', 'blocked', 'clock', '#64748B', null, true, false],
                ['Do Not Disturb', 'do-not-disturb', 'unavailable', 'blocked', 'circle-slash', '#EF4444', null, true, false],
                ['Break', 'break', 'unavailable', 'blocked', 'coffee', '#F97316', 15, false, false],
                ['Lunch', 'lunch', 'unavailable', 'blocked', 'utensils', '#EAB308', 60, false, false],
                ['Meeting', 'meeting', 'unavailable', 'blocked', 'calendar-clock', '#8B5CF6', 30, false, false],
                ['Training', 'training', 'unavailable', 'blocked', 'graduation-cap', '#06B6D4', null, false, false],
                ['Focus', 'focus', 'limited', 'fallback', 'focus', '#3B82F6', 60, false, false],
            ];
            foreach ($definitions as [$name,$slug,$availability,$routing,$icon,$color,$duration,$system,$default]) {
                AgentStatus::updateOrCreate(['slug' => $slug], ['name' => $name, 'availability' => $availability, 'routing_eligibility' => $routing, 'icon' => $icon, 'color' => $color, 'default_duration_minutes' => $duration, 'is_system' => $system, 'is_default' => $default, 'is_active' => true, 'is_selectable' => true]);
            }
            $default = AgentStatus::where('slug', 'available')->firstOrFail();
            AgentStatus::where('is_default', true)->whereKeyNot($default->id)->update(['is_default' => false]);
            User::whereHas('roles', fn ($q) => $q->where('type', 'agent'))->eachById(function (User $agent) use ($default) {
                if (! AgentStatusPeriod::forAgent($agent)->global()->open()->exists()) {
                    AgentStatusPeriod::create(['user_id' => $agent->id, 'agent_status_id' => $default->id, 'scope' => 'global', 'started_at' => now(), 'source' => 'system']);
                }
            });
        });
    }
}
