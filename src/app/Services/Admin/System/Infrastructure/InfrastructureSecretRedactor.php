<?php

namespace App\Services\Admin\System\Infrastructure;

class InfrastructureSecretRedactor
{
    public function redact(mixed $value, array $secrets): mixed
    {
        $secretValues = array_values(array_filter(array_map(fn (mixed $secret) => is_scalar($secret) ? (string) $secret : '', $secrets), fn (string $secret) => $secret !== ''));
        if (is_string($value)) {
            return str_replace($secretValues, '[REDACTED]', $value);
        }
        if (is_array($value)) {
            return array_map(fn (mixed $item) => $this->redact($item, $secretValues), $value);
        }

        return $value;
    }
}
