<?php

namespace App\Exceptions\Admin\Mail;

use RuntimeException;
use Throwable;

class AttachmentScanException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $errorCode,
        private readonly bool $retryable = true,
        private readonly array $context = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            previous: $previous,
        );
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function retryable(): bool
    {
        return $this->retryable;
    }

    public function context(): array
    {
        return $this->context;
    }
}
