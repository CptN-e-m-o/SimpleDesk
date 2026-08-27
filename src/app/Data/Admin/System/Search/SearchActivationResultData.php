<?php

namespace App\Data\Admin\System\Search;

use App\Models\Admin\System\SearchDriverSettings;

final readonly class SearchActivationResultData
{
    public function __construct(public SearchDriverSettings $settings, public bool $forced, public bool $healthOverrideUsed, public bool $restartSignaled) {}
}
