<?php

namespace App\Enums\Admin;

enum WorkScheduleExceptionType: string
{
    case DayOff = 'day_off';
    case CustomHours = 'custom_hours';
    case ExtraShift = 'extra_shift';
}
