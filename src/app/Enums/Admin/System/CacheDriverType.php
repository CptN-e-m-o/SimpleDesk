<?php

namespace App\Enums\Admin\System;

enum CacheDriverType: string
{
    case Database = 'database';
    case File = 'file';
    case Redis = 'redis';
}
