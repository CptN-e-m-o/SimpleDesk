<?php

namespace App\Services\Admin\Mail\ReplyParsing;

use App\Enums\Admin\Mail\ReplyParsingContentType;
use App\Models\Admin\Mail\ReplyParsingRule;
use Illuminate\Database\Eloquent\Collection;

class ReplyParsingRuleQuery
{
    public function activeFor(
        ReplyParsingContentType $contentType,
    ): Collection {
        return ReplyParsingRule::query()
            ->where('is_active', true)
            ->whereIn('content_type', [
                $contentType->value,
                ReplyParsingContentType::Both->value,
            ])
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
    }
}
