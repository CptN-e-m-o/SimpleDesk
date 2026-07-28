<?php

namespace App\Data\Admin\Mail;

use InvalidArgumentException;

final readonly class FailedInboundMessageData
{
    public function __construct(
        public NormalizedInboundMessageData $acknowledgementMessage,
        public string $errorCode,
        public string $errorMessage,
        public string $exceptionClass,
        public bool $retryable,
        public array $metadata = [],
    ) {
        if (trim($errorCode) === '') {
            throw new InvalidArgumentException(
                'Inbound normalization error code cannot be empty.'
            );
        }

        if (trim($errorMessage) === '') {
            throw new InvalidArgumentException(
                'Inbound normalization error message cannot be empty.'
            );
        }
    }
}
