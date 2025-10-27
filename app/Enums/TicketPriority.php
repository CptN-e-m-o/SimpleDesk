<?php

namespace App\Enums;

enum TicketPriority: int
{
    case Low = 1;
    case Medium = 2;
    case High = 3;

    public function label(): string
    {
        return match ($this) {
            self::Low => __('lang.ticket_priority_low'),
            self::Medium => __('lang.ticket_priority_medium'),
            self::High => __('lang.ticket_priority_high'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => 'info',
            self::Medium => 'warning',
            self::High => 'danger',
        };
    }
}
