<?php

namespace App\Enums\Admin\System;

enum InfrastructureConnectionSource: string
{
    case Managed = 'managed';
    case Deployment = 'deployment';
}
