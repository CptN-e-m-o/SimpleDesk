<?php

namespace App\Enums;

enum UserRole: int
{
    case Client = 1;
    case Agent = 2;
    case Admin = 3;

    public function toString(): string
    {
        return match ($this) {
            self::Client => __('lang.user_role_client'),
            self::Agent => __('lang.user_role_agent'),
            self::Admin => __('lang.user_role_admin'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Client => 'primary',
            self::Agent => 'warning',
            self::Admin => 'danger',
        };
    }
}
