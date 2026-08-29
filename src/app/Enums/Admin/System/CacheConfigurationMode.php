<?php

namespace App\Enums\Admin\System;

enum CacheConfigurationMode: string
{
    case Deployment = 'deployment';
    case Managed = 'managed';
}
