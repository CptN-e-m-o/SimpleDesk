<?php

namespace App\Enums\Admin\System;

enum InfrastructureHealthStatus: string
{
    case Unknown = 'unknown';
    case Healthy = 'healthy';
    case Degraded = 'degraded';
    case Unhealthy = 'unhealthy';
    case Unavailable = 'unavailable';
}
