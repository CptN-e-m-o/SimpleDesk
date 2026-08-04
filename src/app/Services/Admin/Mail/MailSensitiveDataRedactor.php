<?php

namespace App\Services\Admin\Mail;

use Stringable;

class MailSensitiveDataRedactor
{
    public function sanitizeArray(array $values): array
    {
        $sanitized = [];

        foreach ($values as $key => $value) {
            if (
                is_string($key)
                && $this->isSensitiveKey($key)
            ) {
                continue;
            }

            $sanitized[$key] = $this->sanitizeValue(
                $value
            );
        }

        return $sanitized;
    }

    public function redactString(string $value): string
    {
        $value = preg_replace(
            '#([a-z][a-z0-9+.-]*://)([^/\s:@]+):([^@\s/]+)@#i',
            '$1***:***@',
            $value
        ) ?? $value;

        $value = preg_replace(
            '#\b(Bearer|Basic)\s+[A-Za-z0-9._~+/=-]+#i',
            '$1 [REDACTED]',
            $value
        ) ?? $value;

        $value = preg_replace(
            '#\b(password|passwd|secret|token|api[_-]?key|authorization)\s*[:=]\s*([^\s,;]+)#i',
            '$1=[REDACTED]',
            $value
        ) ?? $value;

        return mb_substr(
            $value,
            0,
            10000
        );
    }

    private function sanitizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return $this->sanitizeArray(
                $value
            );
        }

        if (is_string($value)) {
            return $this->redactString(
                $value
            );
        }

        if (
            is_int($value)
            || is_float($value)
            || is_bool($value)
            || $value === null
        ) {
            return $value;
        }

        if ($value instanceof Stringable) {
            return $this->redactString(
                (string) $value
            );
        }

        return $value::class;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower(
            str_replace(
                '-',
                '_',
                $key
            )
        );

        if (in_array($normalized, ['code', 'authorization_code', 'code_verifier', 'pkce_verifier', 'state'], true)) {
            return true;
        }

        foreach (
            [
                'password',
                'passwd',
                'secret',
                'token',
                'authorization',
                'credential',
                'cookie',
                'private_key',
                'api_key',
                'dsn',
            ] as $sensitivePart
        ) {
            if (
                str_contains(
                    $normalized,
                    $sensitivePart
                )
            ) {
                return true;
            }
        }

        return false;
    }
}
