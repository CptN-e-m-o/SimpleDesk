<?php

namespace App\Data\Admin\Mail;

use App\Enums\Admin\Mail\InboundEmailClassification;

final readonly class InboundEmailDecisionData
{
    public function __construct(
        public bool $shouldProcess,
        public InboundEmailClassification $classification,
        public string $reason,
    ) {
    }
}
