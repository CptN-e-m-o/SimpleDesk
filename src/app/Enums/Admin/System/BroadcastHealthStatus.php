<?php

namespace App\Enums\Admin\System;

enum BroadcastHealthStatus: string
{
    case Healthy = 'healthy';
    case Degraded = 'degraded';
    case Unhealthy = 'unhealthy';
    case Unavailable = 'unavailable';
}
