<?php

namespace App\Enums\Admin\System;

enum InfrastructureHealthTrigger: string
{
    case Manual = 'manual';
    case Scheduled = 'scheduled';
    case Activation = 'activation';
}
