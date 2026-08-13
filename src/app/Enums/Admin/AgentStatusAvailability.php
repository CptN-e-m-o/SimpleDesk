<?php

namespace App\Enums\Admin;

enum AgentStatusAvailability: string
{
    case Available = 'available';
    case Limited = 'limited';
    case Unavailable = 'unavailable';

    public function weight(): int
    {
        return match ($this) {
            self::Available => 0, self::Limited => 1, self::Unavailable => 2
        };
    }
}
