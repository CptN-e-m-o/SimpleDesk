<?php

namespace App\Enums;

enum UserRole: int
{
    case Client = 1;
    case Agent = 2;
    case Admin = 3;
}
