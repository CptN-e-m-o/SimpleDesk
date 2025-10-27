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
            self::Open => 'Открыта',
            self::In_Progress => 'В работе',
            self::Closed => 'Закрыта',
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
}
