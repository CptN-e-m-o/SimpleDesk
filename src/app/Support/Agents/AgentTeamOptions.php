<?php

namespace App\Support\Agents;

use App\Models\Admin\Team;

class AgentTeamOptions
{
    public function options(): array
    {
        return Team::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Team $team) => [
                'id' => $team->id,
                'name' => $team->name,
            ])
            ->values()
            ->all();
    }
}
