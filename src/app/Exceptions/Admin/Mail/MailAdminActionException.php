<?php

namespace App\Exceptions\Admin\Mail;

use RuntimeException;
use Throwable;

class MailAdminActionException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $errorCode,
        private readonly string $field = 'action',
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

    public function field(): string
    {
        return $this->field;
    }
}
