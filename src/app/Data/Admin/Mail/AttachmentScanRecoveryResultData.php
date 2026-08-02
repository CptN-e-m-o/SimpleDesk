<?php

namespace App\Data\Admin\Mail;

final readonly class AttachmentScanRecoveryResultData
{
    public function __construct(
        public int $stuckScansReset,
        public int $pendingScansDispatched,
    ) {}

    public function totalActions(): int
    {
        return $this->stuckScansReset
            + $this->pendingScansDispatched;
    }
}
