<?php

namespace Tests\Fakes\Admin\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class FakeScanEmailAttachmentJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $emailAttachmentId,
    ) {
    }

    public function handle(): void
    {
    }
}
