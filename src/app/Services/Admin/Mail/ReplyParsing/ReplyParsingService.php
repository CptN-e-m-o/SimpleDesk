<?php

namespace App\Services\Admin\Mail\ReplyParsing;

use App\Data\Admin\Mail\ReplyParsingResultData;
use App\Enums\Admin\Mail\ReplyParsingContentType;
use App\Models\Admin\Mail\ReplyParsingRule;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Traversable;

class ReplyParsingService
{
    public function __construct(
        private readonly ReplyParsingRuleQuery $rules,
        private readonly ReplyParsingPatternCompiler $patterns,
    ) {}

    public function parse(
        string $content,
        ReplyParsingContentType $contentType,
        iterable|null $rules = null,
    ): ReplyParsingResultData {
        if ($contentType === ReplyParsingContentType::Both) {
            throw ValidationException::withMessages([
                'test_content_type' => ['Select plain text or HTML for the content being parsed.'],
            ]);
        }

        $candidates = $rules === null
            ? $this->rules->activeFor($contentType)
            : $this->collect($rules);

        $bestRule = null;
        $bestOffset = null;

        foreach ($candidates as $rule) {
            if (! $this->isApplicable($rule, $contentType)) {
                continue;
            }

            $pattern = $this->patterns->compile(
                $rule->pattern,
                $rule->pattern_type,
            );

            $matches = [];
            $result = preg_match(
                $pattern,
                $content,
                $matches,
                PREG_OFFSET_CAPTURE,
            );

            if ($result === false || preg_last_error() !== PREG_NO_ERROR) {
                throw ValidationException::withMessages([
                    'pattern' => ['The regular expression could not be evaluated: '.preg_last_error_msg()],
                ]);
            }

            if ($result !== 1) {
                continue;
            }

            $offset = (int) $matches[0][1];

            if ($bestOffset === null
                || $offset < $bestOffset
                || ($offset === $bestOffset && $this->hasHigherPriority($rule, $bestRule))) {
                $bestRule = $rule;
                $bestOffset = $offset;
            }
        }

        if ($bestRule === null || $bestOffset === null) {
            return new ReplyParsingResultData(
                originalContent: $content,
                parsedContent: $content,
                removedContent: '',
                matched: false,
                matchedRuleId: null,
                matchedRuleName: null,
                matchOffset: null,
            );
        }

        return new ReplyParsingResultData(
            originalContent: $content,
            parsedContent: substr($content, 0, $bestOffset),
            removedContent: substr($content, $bestOffset),
            matched: true,
            matchedRuleId: $bestRule->exists ? $bestRule->id : null,
            matchedRuleName: $bestRule->name,
            matchOffset: $bestOffset,
        );
    }

    private function collect(iterable $rules): Collection
    {
        if ($rules instanceof Traversable) {
            return collect(iterator_to_array($rules));
        }

        return collect($rules);
    }

    private function isApplicable(
        ReplyParsingRule $rule,
        ReplyParsingContentType $contentType,
    ): bool {
        return ! $rule->trashed()
            && $rule->is_active
            && in_array($rule->content_type, [
                $contentType,
                ReplyParsingContentType::Both,
            ], true);
    }

    private function hasHigherPriority(
        ReplyParsingRule $candidate,
        ?ReplyParsingRule $current,
    ): bool {
        if ($current === null) {
            return true;
        }

        if ($candidate->display_order !== $current->display_order) {
            return $candidate->display_order < $current->display_order;
        }

        return ($candidate->id ?? PHP_INT_MAX) < ($current->id ?? PHP_INT_MAX);
    }
}
