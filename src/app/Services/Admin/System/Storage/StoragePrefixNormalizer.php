<?php

namespace App\Services\Admin\System\Storage;

use Illuminate\Validation\ValidationException;

class StoragePrefixNormalizer
{
    public function normalize(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (! is_string($value)) {
            throw ValidationException::withMessages([
                'configuration.prefix' => 'The object prefix must be a string.',
            ]);
        }

        $prefix = trim(
            str_replace('\\', '/', $value),
            '/',
        );

        $prefix = preg_replace('#/+#', '/', $prefix) ?? $prefix;

        if (strlen($prefix) > 255) {
            throw ValidationException::withMessages([
                'configuration.prefix' => 'The object prefix must not exceed 255 characters.',
            ]);
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $prefix) === 1) {
            throw ValidationException::withMessages([
                'configuration.prefix' => 'The object prefix contains invalid control characters.',
            ]);
        }

        $segments = $prefix === ''
            ? []
            : explode('/', $prefix);

        foreach ($segments as $segment) {
            if ($segment === '.' || $segment === '..') {
                throw ValidationException::withMessages([
                    'configuration.prefix' => 'The object prefix must be a safe relative namespace.',
                ]);
            }
        }

        return $prefix;
    }
}
