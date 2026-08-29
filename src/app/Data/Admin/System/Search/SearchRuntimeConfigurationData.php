<?php

namespace App\Data\Admin\System\Search;

use App\Enums\Admin\System\SearchDriverType;

final readonly class SearchRuntimeConfigurationData
{
    public function __construct(public SearchDriverType $driver, public array $connectivity = []) {}
}
