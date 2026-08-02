<?php

namespace App\Exceptions\Admin\Mail;

use RuntimeException;
use Throwable;

class EmailQuarantineException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $errorCode,
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
}
