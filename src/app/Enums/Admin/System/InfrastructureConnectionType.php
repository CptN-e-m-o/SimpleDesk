<?php

namespace App\Enums\Admin\System;

enum InfrastructureConnectionType: string
{
    case Redis = 'redis';
    case Aws = 'aws';
    case Memcached = 'memcached';
    case Beanstalkd = 'beanstalkd';
    case Meilisearch = 'meilisearch';
    case Typesense = 'typesense';
    case Algolia = 'algolia';
    case Pusher = 'pusher';
    case Reverb = 'reverb';
    case Ably = 'ably';
    case S3Compatible = 's3_compatible';
}
