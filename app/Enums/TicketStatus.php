<?php

namespace App\Enums;

enum TicketStatus: int
{
    case Open = 1;
    case Closed = 2;
    case Overdue = 3;
    case Unanswered = 4;
    case Pending_Approval = 5;
    case Spam = 6;

    public function label(): string
    {
        return match ($this) {
            self::Open => __('lang.open_tickets'),
            self::Closed => __('lang.closed_tickets'),
            self::Overdue => __('lang.overdue_tickets'),
            self::Unanswered => __('lang.unanswered_tickets'),
            self::Pending_Approval => __('lang.pending_approvals'),
            self::Spam => __('lang.spam_tickets'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'primary',
            self::Closed => 'success',
            self::Overdue => 'danger',
            self::Unanswered => 'warning',
            self::Pending_Approval => 'info',
            self::Spam => 'secondary',
        };
    }

    public static function fromName(string $name): ?self
    {
        foreach (self::cases() as $status) {
            if (strtolower($status->name) === str_replace('-', '_', $name)) {
                return $status;
            }
        }

        return null;
    }
}
