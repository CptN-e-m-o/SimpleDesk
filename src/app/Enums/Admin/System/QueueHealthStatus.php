<?php

namespace App\Enums\Admin\System;

enum QueueHealthStatus: string
{
    case Healthy = 'healthy';
    case Degraded = 'degraded';
    case Unhealthy = 'unhealthy';
    case Unavailable = 'unavailable';
}
