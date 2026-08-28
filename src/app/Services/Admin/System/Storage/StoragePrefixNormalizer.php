<?php

namespace App\Services\Admin\System\Storage;

use Illuminate\Validation\ValidationException;

class StoragePrefixNormalizer
{
    public function normalize(mixed $value): string
    {
        $prefix = trim(str_replace('\\', '/', (string) ($value ?? '')), '/');
        $prefix = preg_replace('#/+#', '/', $prefix) ?? $prefix;
        if (strlen($prefix) > 255 || in_array('..', explode('/', $prefix), true)) {
            throw ValidationException::withMessages(['configuration.prefix' => 'The object prefix must be a safe relative namespace of at most 255 characters.']);
        }

        return $prefix;
    }
}
