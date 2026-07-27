<?php

namespace App\Exceptions\Admin\Mail;

use RuntimeException;
use Throwable;

class MailStorageException extends RuntimeException
{
    public function __construct(
        string $message,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            previous: $previous,
        );
    }
}
