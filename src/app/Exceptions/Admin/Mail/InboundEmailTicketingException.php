<?php

namespace App\Exceptions\Admin\Mail;

use RuntimeException;
use Throwable;

class InboundEmailTicketingException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $errorCode,
        private readonly bool $retryable = false,
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
}
