<?php

namespace App\Enums\Admin\System;

enum SearchConfigurationMode: string
{
    case Deployment = 'deployment';
    case Managed = 'managed';
}
