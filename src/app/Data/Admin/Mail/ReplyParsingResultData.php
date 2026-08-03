<?php

namespace App\Data\Admin\Mail;

class ReplyParsingResultData
{
    public function __construct(
        public readonly string $originalContent,
        public readonly string $parsedContent,
        public readonly string $removedContent,
        public readonly bool $matched,
        public readonly ?int $matchedRuleId,
        public readonly ?string $matchedRuleName,
        public readonly ?int $matchOffset,
    ) {}

    public function toArray(): array
    {
        return [
            'original_content' => $this->originalContent,
            'parsed_content' => $this->parsedContent,
            'removed_content' => $this->removedContent,
            'matched' => $this->matched,
            'matched_rule_id' => $this->matchedRuleId,
            'matched_rule_name' => $this->matchedRuleName,
            'match_offset' => $this->matchOffset,
        ];
    }
}
