<?php

namespace App\Exceptions\Admin\Mail;

use App\Enums\Admin\Mail\MailboxChannelDirection;
use App\Enums\Admin\Mail\MailboxDriver;
use RuntimeException;

class MailDriverNotRegisteredException extends RuntimeException
{
    public function __construct(
        MailboxDriver $driver,
        MailboxChannelDirection $direction,
    ) {
        parent::__construct(
            "Mail driver [{$driver->value}] is not registered "
            . "for direction [{$direction->value}]."
        );
    }
}
