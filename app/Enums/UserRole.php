<?php

namespace App\Enums;

enum UserRole: int
{
    case Client = 1;
    case Agent = 2;
    case Admin = 3;

    public function toString(): string
    {
        return match($this) {
            self::Client => 'Пользователь',
            self::Agent => 'Агент',
            self::Admin => 'Администратор',
        };
    }
}
