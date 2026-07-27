<?php

namespace App\Events\Admin\Mail;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InboundEmailStored
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int $emailMessageId,
    ) {
    }
}
