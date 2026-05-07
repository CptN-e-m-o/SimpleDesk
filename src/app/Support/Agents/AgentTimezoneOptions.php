<?php

namespace App\Support\Agents;

use DateTimeZone;

class AgentTimezoneOptions
{
    public function options(): array
    {
        return collect(DateTimeZone::listIdentifiers())
            ->map(fn (string $timezone) => [
                'id' => $timezone,
                'name' => $timezone,
            ])
            ->values()
            ->all();
    }
}
