<?php

namespace App\Services\Admin\System\Search\Clients;

use Typesense\Client;

class TypesenseClientFactory
{
    public function make(array $configuration): object
    {
        return new Client($configuration);
    }
}
