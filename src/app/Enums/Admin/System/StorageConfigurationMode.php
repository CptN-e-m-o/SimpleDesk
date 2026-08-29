<?php

namespace App\Enums\Admin\System;

enum StorageConfigurationMode: string
{
    case Deployment = 'deployment';
    case Managed = 'managed';
}
