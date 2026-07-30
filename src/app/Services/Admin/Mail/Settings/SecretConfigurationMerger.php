<?php

namespace App\Services\Admin\Mail\Settings;

class SecretConfigurationMerger
{
    public function merge(
        ?array $existing,
        ?array $incoming,
        array $clearKeys = [],
    ): array {
        $result = is_array($existing)
            ? $existing
            : [];

        foreach ($clearKeys as $key) {
            if (!is_string($key)) {
                continue;
            }

            $key = trim($key);

            if ($key !== '') {
                unset($result[$key]);
            }
        }

        foreach ($incoming ?? [] as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            $key = trim($key);

            if ($key === '' || !$this->hasValue($value)) {
                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    private function hasValue(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        return true;
    }
}
