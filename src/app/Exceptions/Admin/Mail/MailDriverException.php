<?php

namespace App\Exceptions\Admin\Mail;

use RuntimeException;
use Throwable;

class MailDriverException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly ?string $driverErrorCode = null,
        private readonly bool $retryable = true,
        private readonly bool $failoverAllowed = true,
        private readonly bool $affectsChannelHealth = true,
        private readonly array $context = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            previous: $previous,
        );
    }

    public function driverErrorCode(): ?string
    {
        return $this->driverErrorCode;
    }

    public function retryable(): bool
    {
        return $this->retryable;
    }

    public function failoverAllowed(): bool
    {
        return $this->failoverAllowed;
    }

    public function affectsChannelHealth(): bool
    {
        return $this->affectsChannelHealth;
    }

    public function context(): array
    {
        return $this->context;
    }
}
