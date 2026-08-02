<?php

namespace App\Services\Admin\Mail\Drivers\Imap;

use App\Exceptions\Admin\Mail\MailDriverException;
use Throwable;

class ImapExceptionMapper
{
    public function map(
        Throwable $exception,
        string $operation,
    ): MailDriverException {
        if ($exception instanceof MailDriverException) {
            return $exception;
        }

        $class = strtolower($exception::class);
        $message = strtolower($exception->getMessage());

        if (
            str_contains($class, 'authfailed')
            || str_contains($message, 'authentication failed')
            || str_contains($message, 'invalid credentials')
            || str_contains($message, 'login failed')
        ) {
            return $this->exception(
                exception: $exception,
                operation: $operation,
                code: 'imap_authentication_failed',
                retryable: false,
                failoverAllowed: true,
                affectsHealth: true,
            );
        }

        if (
            str_contains($class, 'connectionfailed')
            || str_contains($message, 'connection refused')
            || str_contains($message, 'connection timed out')
            || str_contains($message, 'could not connect')
            || str_contains($message, 'broken pipe')
            || str_contains($message, 'network is unreachable')
        ) {
            return $this->exception(
                exception: $exception,
                operation: $operation,
                code: 'imap_connection_failed',
                retryable: true,
                failoverAllowed: true,
                affectsHealth: true,
            );
        }

        if ($this->isFolderNotFound($exception)) {
            return $this->exception(
                exception: $exception,
                operation: $operation,
                code: 'imap_folder_not_found',
                retryable: false,
                failoverAllowed: true,
                affectsHealth: true,
            );
        }

        if ($this->isMessageNotFound($exception)) {
            return $this->exception(
                exception: $exception,
                operation: $operation,
                code: 'imap_message_not_found',
                retryable: false,
                failoverAllowed: false,
                affectsHealth: false,
            );
        }

        if (
            str_contains($class, 'decoder')
            || str_contains($class, 'messageheader')
            || str_contains($class, 'messagecontent')
            || str_contains($message, 'could not parse')
            || str_contains($message, 'failed to decode')
        ) {
            return $this->exception(
                exception: $exception,
                operation: $operation,
                code: 'imap_message_parsing_failed',
                retryable: false,
                failoverAllowed: false,
                affectsHealth: false,
            );
        }

        return $this->exception(
            exception: $exception,
            operation: $operation,
            code: 'imap_operation_failed',
            retryable: true,
            failoverAllowed: true,
            affectsHealth: true,
        );
    }

    public function isMessageNotFound(
        Throwable $exception
    ): bool {
        $class = strtolower($exception::class);
        $message = strtolower($exception->getMessage());

        return str_contains($class, 'messagenotfound')
            || str_contains($message, 'message not found')
            || str_contains($message, 'no message found');
    }

    public function isFolderNotFound(
        Throwable $exception
    ): bool {
        $class = strtolower($exception::class);
        $message = strtolower($exception->getMessage());

        return str_contains($class, 'foldernotfound')
            || str_contains($message, 'folder not found')
            || str_contains($message, 'mailbox does not exist')
            || str_contains($message, 'no such mailbox');
    }

    private function exception(
        Throwable $exception,
        string $operation,
        string $code,
        bool $retryable,
        bool $failoverAllowed,
        bool $affectsHealth,
    ): MailDriverException {
        return new MailDriverException(
            message: "IMAP {$operation} failed: "
            .$exception->getMessage(),
            driverErrorCode: $code,
            retryable: $retryable,
            failoverAllowed: $failoverAllowed,
            affectsChannelHealth: $affectsHealth,
            context: [
                'exception' => $exception::class,
                'operation' => $operation,
            ],
            previous: $exception,
        );
    }
}
