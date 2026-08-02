<?php

namespace App\Data\Admin\Mail;

final readonly class MailConnectionTestResultData
{
    public function __construct(
        public bool $successful,
        public string $message,
        public ?int $latencyMilliseconds = null,
        public array $details = [],
    ) {}

    public static function success(
        string $message,
        ?int $latencyMilliseconds = null,
        array $details = [],
    ): self {
        return new self(
            successful: true,
            message: $message,
            latencyMilliseconds: $latencyMilliseconds,
            details: $details,
        );
    }

    public static function failure(
        string $message,
        ?int $latencyMilliseconds = null,
        array $details = [],
    ): self {
        return new self(
            successful: false,
            message: $message,
            latencyMilliseconds: $latencyMilliseconds,
            details: $details,
        );
    }
}
