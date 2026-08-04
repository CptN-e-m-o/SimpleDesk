<?php

namespace App\Http\Requests\Admin\Mail\ReplyParsing;

use App\Enums\Admin\Mail\ReplyParsingContentType;
use App\Enums\Admin\Mail\ReplyParsingPatternType;
use App\Services\Admin\Mail\ReplyParsing\ReplyParsingPatternCompiler;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

abstract class ReplyParsingRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'pattern' => [
                'required',
                'string',
                'max:10000',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || trim($value) === '') {
                        $fail('The pattern must not be empty.');

                        return;
                    }

                    $patternType = ReplyParsingPatternType::tryFrom(
                        (string) $this->input('pattern_type')
                    );

                    if ($patternType !== ReplyParsingPatternType::Regex) {
                        return;
                    }

                    try {
                        app(ReplyParsingPatternCompiler::class)->compile(
                            $value,
                            $patternType,
                        );
                    } catch (ValidationException $exception) {
                        $fail($exception->errors()['pattern'][0] ?? 'The regular expression is invalid.');
                    }
                },
            ],
            'pattern_type' => ['required', Rule::enum(ReplyParsingPatternType::class)],
            'content_type' => ['required', Rule::enum(ReplyParsingContentType::class)],
            'display_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
