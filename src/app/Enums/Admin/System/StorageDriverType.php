<?php

namespace App\Enums\Admin\System;

enum StorageDriverType: string
{
    case Local = 'local';
    case S3 = 's3';
    case S3Compatible = 's3_compatible';
}
