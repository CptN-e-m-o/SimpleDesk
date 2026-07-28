<?php

namespace App\Exceptions\Admin\Mail;

use RuntimeException;
use Throwable;

class InboundMessagePersistenceException extends RuntimeException
{
    public function __construct(
        private readonly int $emailMessageId,
        private readonly string $errorCode,
        string $message,
        private readonly bool $retryable = true,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            previous: $previous,
        );
    }

    public function emailMessageId(): int
    {
        return $this->emailMessageId;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function retryable(): bool
    {
        return $this->retryable;
    }
}
