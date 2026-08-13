<?php

namespace App\Enums\Admin\System;

enum QueueDriverType: string
{
    case Database = 'database';
    case Redis = 'redis';
    case Sqs = 'sqs';
    case Beanstalkd = 'beanstalkd';
    case Sync = 'sync';
}
