<?php

namespace App\Enums\Admin;

enum AgentRoutingEligibility: string
{
    case Eligible = 'eligible';
    case Fallback = 'fallback';
    case Blocked = 'blocked';

    public function weight(): int
    {
        return match ($this) {
            self::Eligible => 0, self::Fallback => 1, self::Blocked => 2
        };
    }
}
