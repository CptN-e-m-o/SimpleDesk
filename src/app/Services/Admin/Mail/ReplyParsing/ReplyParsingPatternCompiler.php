<?php

namespace App\Services\Admin\Mail\ReplyParsing;

use App\Enums\Admin\Mail\ReplyParsingPatternType;
use ErrorException;
use Illuminate\Validation\ValidationException;

class ReplyParsingPatternCompiler
{
    public function compile(
        string $pattern,
        ReplyParsingPatternType $patternType,
    ): string {
        if (trim($pattern) === '') {
            throw ValidationException::withMessages([
                'pattern' => ['The pattern must not be empty.'],
            ]);
        }

        $delimiter = $this->delimiter($pattern);
        $body = $patternType === ReplyParsingPatternType::Literal
            ? preg_quote($pattern, $delimiter)
            : $pattern;
        $compiled = $delimiter.$body.$delimiter.'u';

        $this->validateCompiledPattern($compiled);

        return $compiled;
    }

    private function delimiter(string $pattern): string
    {
        foreach (['~', '#', '%', '!', ';', '`', '@'] as $delimiter) {
            if (! str_contains($pattern, $delimiter)) {
                return $delimiter;
            }
        }

        throw ValidationException::withMessages([
            'pattern' => ['The pattern contains every supported PCRE delimiter.'],
        ]);
    }

    private function validateCompiledPattern(string $pattern): void
    {
        $warning = null;

        set_error_handler(
            static function (int $severity, string $message) use (&$warning): bool {
                $warning = new ErrorException($message, 0, $severity);

                return true;
            }
        );

        try {
            $result = preg_match($pattern, '');
        } finally {
            restore_error_handler();
        }

        if ($result !== false && $warning === null && preg_last_error() === PREG_NO_ERROR) {
            return;
        }

        $message = $warning?->getMessage() ?? preg_last_error_msg();

        throw ValidationException::withMessages([
            'pattern' => ['The regular expression is invalid: '.$message],
        ]);
    }
}
