<?php

namespace App\Services\Admin\Mail\Drivers\Smtp;

use App\Exceptions\Admin\Mail\MailDriverException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;

class SmtpExceptionMapper
{
    public function map(
        Throwable $exception,
        string $operation,
    ): MailDriverException {
        if ($exception instanceof MailDriverException) {
            return $exception;
        }

        if ($exception instanceof TransportExceptionInterface) {
            return $this->mapTransportException(
                exception: $exception,
                operation: $operation,
            );
        }

        return new MailDriverException(
            message: "SMTP {$operation} failed: "
            .$exception->getMessage(),
            driverErrorCode: 'smtp_unexpected_error',
            retryable: false,
            failoverAllowed: true,
            affectsChannelHealth: true,
            context: [
                'exception' => $exception::class,
                'operation' => $operation,
            ],
            previous: $exception,
        );
    }

    private function mapTransportException(
        TransportExceptionInterface $exception,
        string $operation,
    ): MailDriverException {
        $code = (int) $exception->getCode();

        [
            $driverCode,
            $retryable,
            $failoverAllowed,
            $affectsHealth,
        ] = match (true) {
            in_array(
                $code,
                [421, 450, 451, 452],
                true
            ) => [
                'smtp_temporary_failure',
                true,
                true,
                true,
            ],

            in_array(
                $code,
                [530, 534, 535, 538],
                true
            ) => [
                'smtp_authentication_failed',
                false,
                true,
                true,
            ],

            in_array(
                $code,
                [550, 551, 553],
                true
            ) => [
                'smtp_recipient_rejected',
                false,
                false,
                false,
            ],

            $code === 552 => [
                'smtp_message_rejected',
                false,
                false,
                false,
            ],

            $code === 554 => [
                'smtp_transaction_rejected',
                false,
                false,
                false,
            ],

            default => [
                $code > 0
                    ? 'smtp_transport_error'
                    : 'smtp_connection_failed',

                $code === 0
                || (
                    $code >= 400
                    && $code < 500
                ),

                true,
                true,
            ],
        };

        return new MailDriverException(
            message: "SMTP {$operation} failed: "
            .$exception->getMessage(),
            driverErrorCode: $driverCode,
            retryable: $retryable,
            failoverAllowed: $failoverAllowed,
            affectsChannelHealth: $affectsHealth,
            context: [
                'smtp_response_code' => $code > 0 ? $code : null,
                'operation' => $operation,
            ],
            previous: $exception,
        );
    }
}
