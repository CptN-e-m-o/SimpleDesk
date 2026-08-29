<?php

namespace App\Enums\Admin\System;

enum DriverCategory: string
{
    case Queue = 'queue';
    case Cache = 'cache';
    case Broadcasting = 'broadcasting';
    case Search = 'search';
    case Storage = 'storage';
}
