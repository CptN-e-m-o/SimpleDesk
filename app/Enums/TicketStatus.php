<?php

namespace App\Enums;

enum TicketStatus: int
{
    case Open = 1;
    case In_Progress = 2;
    case Closed = 3;

    public function label(): string
    {
        return match ($this) {
            self::Open => __('lang.ticket_status_open'),
            self::In_Progress => __('lang.ticket_status_in_progress'),
            self::Closed => __('lang.ticket_status_closed'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'primary',
            self::In_Progress => 'warning',
            self::Closed => 'success',
        };
    }

    public function isClosed(): bool
    {
        return $this === self::Closed;
    }
}
