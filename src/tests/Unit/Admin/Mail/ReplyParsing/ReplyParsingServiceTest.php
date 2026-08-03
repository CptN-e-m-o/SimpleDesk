<?php

namespace Tests\Unit\Admin\Mail\ReplyParsing;

use App\Enums\Admin\Mail\ReplyParsingContentType;
use App\Enums\Admin\Mail\ReplyParsingPatternType;
use App\Models\Admin\Mail\ReplyParsingRule;
use App\Services\Admin\Mail\ReplyParsing\ReplyParsingPatternCompiler;
use App\Services\Admin\Mail\ReplyParsing\ReplyParsingRuleQuery;
use App\Services\Admin\Mail\ReplyParsing\ReplyParsingService;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReplyParsingServiceTest extends TestCase
{
    private ReplyParsingService $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new ReplyParsingService(
            $this->createStub(ReplyParsingRuleQuery::class),
            new ReplyParsingPatternCompiler(),
        );
    }

    public function test_literal_rule_preserves_regex_special_characters(): void
    {
        $result = $this->parse(
            'Useful reply'."\n".'[quoted.*]',
            [$this->rule('[quoted.*]')],
        );

        $this->assertTrue($result->matched);
        $this->assertSame('Useful reply'."\n", $result->parsedContent);
        $this->assertSame('[quoted.*]', $result->removedContent);
    }

    public function test_regex_rule_and_unicode_content_are_supported(): void
    {
        $result = $this->parse(
            'Полезный ответ'."\n\n".'On Monday, Иван wrote:',
            [$this->rule('On .+ wrote:', ReplyParsingPatternType::Regex)],
        );

        $this->assertTrue($result->matched);
        $this->assertSame('Полезный ответ'."\n\n", $result->parsedContent);
    }

    #[DataProvider('contentTypeProvider')]
    public function test_content_type_filtering(
        ReplyParsingContentType $ruleType,
        ReplyParsingContentType $inputType,
        bool $matched,
    ): void {
        $result = $this->parse(
            'Reply -- quote',
            [$this->rule('-- quote', contentType: $ruleType)],
            $inputType,
        );

        $this->assertSame($matched, $result->matched);
    }

    public static function contentTypeProvider(): array
    {
        return [
            'plain text' => [ReplyParsingContentType::PlainText, ReplyParsingContentType::PlainText, true],
            'html' => [ReplyParsingContentType::Html, ReplyParsingContentType::Html, true],
            'both for plain text' => [ReplyParsingContentType::Both, ReplyParsingContentType::PlainText, true],
            'both for html' => [ReplyParsingContentType::Both, ReplyParsingContentType::Html, true],
            'plain does not parse html' => [ReplyParsingContentType::PlainText, ReplyParsingContentType::Html, false],
        ];
    }

    public function test_disabled_and_soft_deleted_rules_are_ignored(): void
    {
        $disabled = $this->rule('-- disabled');
        $disabled->is_active = false;
        $deleted = $this->rule('-- deleted');
        $deleted->deleted_at = '2026-08-03 00:00:00';

        $content = 'Reply -- disabled -- deleted';
        $result = $this->parse($content, [$disabled, $deleted]);

        $this->assertFalse($result->matched);
        $this->assertSame($content, $result->parsedContent);
        $this->assertSame('', $result->removedContent);
    }

    public function test_earliest_match_wins_regardless_of_rule_order(): void
    {
        $later = $this->rule('-----Original Message-----', displayOrder: 1, id: 1);
        $earlier = $this->rule('On Monday wrote:', displayOrder: 100, id: 2);
        $content = 'Reply'."\n".'On Monday wrote:'."\n".'-----Original Message-----';

        $result = $this->parse($content, [$later, $earlier]);

        $this->assertSame(6, $result->matchOffset);
        $this->assertSame(2, $result->matchedRuleId);
    }

    public function test_same_offset_uses_display_order_then_id(): void
    {
        $highOrder = $this->rule('Quote', displayOrder: 20, id: 1, name: 'High order');
        $higherId = $this->rule('Quote', displayOrder: 10, id: 8, name: 'Higher id');
        $winner = $this->rule('Quote', displayOrder: 10, id: 3, name: 'Winner');

        $result = $this->parse('Reply Quote', [$highOrder, $higherId, $winner]);

        $this->assertSame(3, $result->matchedRuleId);
        $this->assertSame('Winner', $result->matchedRuleName);
    }

    public function test_no_match_and_empty_content_remain_unchanged(): void
    {
        $noMatch = $this->parse('Useful reply', [$this->rule('Quote')]);
        $empty = $this->parse('', [$this->rule('Quote')]);

        $this->assertFalse($noMatch->matched);
        $this->assertSame('Useful reply', $noMatch->originalContent);
        $this->assertSame('Useful reply', $noMatch->parsedContent);
        $this->assertFalse($empty->matched);
        $this->assertSame('', $empty->parsedContent);
    }

    public function test_invalid_regex_is_reported_as_validation_error(): void
    {
        $this->expectException(ValidationException::class);

        $this->parse('content', [
            $this->rule('([invalid', ReplyParsingPatternType::Regex),
        ]);
    }

    private function parse(
        string $content,
        array $rules,
        ReplyParsingContentType $contentType = ReplyParsingContentType::PlainText,
    ): \App\Data\Admin\Mail\ReplyParsingResultData {
        return $this->parser->parse($content, $contentType, $rules);
    }

    private function rule(
        string $pattern,
        ReplyParsingPatternType $patternType = ReplyParsingPatternType::Literal,
        ReplyParsingContentType $contentType = ReplyParsingContentType::Both,
        int $displayOrder = 100,
        int $id = 1,
        string $name = 'Rule',
    ): ReplyParsingRule {
        $rule = new ReplyParsingRule([
            'name' => $name,
            'pattern' => $pattern,
            'pattern_type' => $patternType,
            'content_type' => $contentType,
            'display_order' => $displayOrder,
            'is_active' => true,
        ]);
        $rule->setAttribute('id', $id);
        $rule->exists = true;

        return $rule;
    }
}
