<?php

namespace App\Enums\Admin\System;

enum SearchDriverType: string
{
    case Database = 'database';
    case Meilisearch = 'meilisearch';
    case Typesense = 'typesense';
    case Algolia = 'algolia';
}
