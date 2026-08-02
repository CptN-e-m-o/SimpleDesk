<?php

namespace App\Data\Admin\Mail;

final readonly class MailAdminActionResultData
{
    public function __construct(
        public string $action,
        public bool $dispatched,
        public string $message,
        public array $details = [],
    ) {}

    public function toArray(): array
    {
        return [
            'action' => $this->action,
            'accepted' => true,
            'dispatched' => $this->dispatched,
            'message' => $this->message,
            'details' => $this->details,
            'accepted_at' => now()->toIso8601String(),
        ];
    }
}
