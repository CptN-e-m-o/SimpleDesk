<?php

namespace App\Enums\Admin\System;

enum QueueConfigurationMode: string
{
    case Deployment = 'deployment';
    case Managed = 'managed';
}
