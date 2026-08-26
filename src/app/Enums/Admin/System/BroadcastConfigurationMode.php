<?php

namespace App\Enums\Admin\System;

enum BroadcastConfigurationMode: string
{
    case Deployment = 'deployment';
    case Managed = 'managed';
}
