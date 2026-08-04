<?php

namespace Tests\Feature\Admin\Mail\ReplyParsing;

use App\Enums\Admin\Mail\ReplyParsingContentType;
use App\Enums\Admin\Mail\ReplyParsingPatternType;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\ReplyParsingRule;
use App\Services\Admin\Mail\ReplyParsing\InboundEmailReplyParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InboundEmailReplyParserDatabaseRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'simpledesk-mail-reply-parsing.enabled',
            true
        );

        config()->set(
            'simpledesk-mail-reply-parsing.strip_quoted_text',
            true
        );

        config()->set(
            'simpledesk-mail-reply-parsing.strip_signatures',
            false
        );

        config()->set(
            'simpledesk-mail-reply-parsing.prefer_plain_text',
            true
        );

        config()->set(
            'simpledesk-mail-reply-parsing.fallback_to_full_body',
            false
        );

        config()->set(
            'simpledesk-mail-reply-parsing.custom_separators',
            []
        );
    }

    public function test_plain_text_database_rule_removes_quoted_history(): void
    {
        $this->createRule(
            name: 'Custom plain-text separator',
            pattern: '=== QUOTED HISTORY ===',
            patternType: ReplyParsingPatternType::Literal,
            contentType: ReplyParsingContentType::PlainText,
        );

        $originalBody = implode(
            "\n",
            [
                'Здравствуйте.',
                '',
                'Проблема решена.',
                '',
                '=== QUOTED HISTORY ===',
                'Предыдущая переписка.',
            ]
        );

        $message = $this->message(
            textBody: $originalBody,
            htmlBody: null,
        );

        $result = $this->parser()->parse(
            $message
        );

        $this->assertSame(
            "Здравствуйте.\n\nПроблема решена.",
            $result->body
        );

        $this->assertSame(
            'text',
            $result->source
        );

        $this->assertTrue(
            $result->quotedTextRemoved
        );

        $this->assertSame(
            $originalBody,
            $message->text_body
        );
    }

    public function test_html_database_rule_is_applied_to_raw_html(): void
    {
        $this->createRule(
            name: 'Custom HTML quote block',
            pattern: '<div class="quoted-history">',
            patternType: ReplyParsingPatternType::Literal,
            contentType: ReplyParsingContentType::Html,
        );

        $originalHtml =
            '<p>Thank you, the problem is resolved.</p>'
            .'<div class="quoted-history">'
            .'<p>Previous message.</p>'
            .'</div>';

        $message = $this->message(
            textBody: null,
            htmlBody: $originalHtml,
        );

        $result = $this->parser()->parse(
            $message
        );

        $this->assertSame(
            'Thank you, the problem is resolved.',
            $result->body
        );

        $this->assertSame(
            'html',
            $result->source
        );

        $this->assertTrue(
            $result->quotedTextRemoved
        );

        $this->assertSame(
            $originalHtml,
            $message->html_body
        );
    }

    public function test_both_rule_is_applied_to_plain_text_content(): void
    {
        $this->createRule(
            name: 'Shared separator',
            pattern: '=== OLD CONTENT ===',
            patternType: ReplyParsingPatternType::Literal,
            contentType: ReplyParsingContentType::Both,
        );

        $message = $this->message(
            textBody: "New response.\n\n"
            ."=== OLD CONTENT ===\n"
            .'Previous response.',
            htmlBody: null,
        );

        $result = $this->parser()->parse(
            $message
        );

        $this->assertSame(
            'New response.',
            $result->body
        );

        $this->assertTrue(
            $result->quotedTextRemoved
        );
    }

    public function test_legacy_quote_detection_still_runs_without_database_match(): void
    {
        $message = $this->message(
            textBody: implode(
                "\n",
                [
                    'Thank you.',
                    '',
                    'On Monday, John Smith wrote:',
                    'Previous message.',
                ]
            ),
            htmlBody: null,
        );

        $result = $this->parser()->parse(
            $message
        );

        $this->assertSame(
            'Thank you.',
            $result->body
        );

        $this->assertTrue(
            $result->quotedTextRemoved
        );
    }

    public function test_invalid_stored_regex_does_not_break_incoming_processing(): void
    {
        $this->createRule(
            name: 'Broken regular expression',
            pattern: '([a-z',
            patternType: ReplyParsingPatternType::Regex,
            contentType: ReplyParsingContentType::PlainText,
        );

        $message = $this->message(
            textBody: 'Useful incoming response.',
            htmlBody: null,
        );

        $result = $this->parser()->parse(
            $message
        );

        $this->assertSame(
            'Useful incoming response.',
            $result->body
        );

        $this->assertFalse(
            $result->quotedTextRemoved
        );
    }

    private function parser(): InboundEmailReplyParser
    {
        return $this->app->make(
            InboundEmailReplyParser::class
        );
    }

    private function message(
        ?string $textBody,
        ?string $htmlBody,
    ): EmailMessage {
        $message = new EmailMessage;

        $message->forceFill([
            'text_body' => $textBody,
            'html_body' => $htmlBody,
        ]);

        return $message;
    }

    private function createRule(
        string $name,
        string $pattern,
        ReplyParsingPatternType $patternType,
        ReplyParsingContentType $contentType,
    ): ReplyParsingRule {
        return ReplyParsingRule::query()->create([
            'name' => $name,
            'pattern' => $pattern,

            'pattern_type' => $patternType->value,

            'content_type' => $contentType->value,

            'display_order' => 10,
            'is_active' => true,
            'description' => null,
        ]);
    }
}
