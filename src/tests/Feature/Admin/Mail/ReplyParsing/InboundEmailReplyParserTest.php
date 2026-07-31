<?php

namespace Tests\Feature\Admin\Mail\ReplyParsing;

use App\Models\Admin\Mail\EmailMessage;
use App\Services\Admin\Mail\ReplyParsing\InboundEmailReplyParser;
use Tests\TestCase;

class InboundEmailReplyParserTest extends TestCase
{
    private InboundEmailReplyParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set([
            'simpledesk-mail-reply-parsing.enabled' => true,

            'simpledesk-mail-reply-parsing.prefer_plain_text' => true,

            'simpledesk-mail-reply-parsing.strip_quoted_text' => true,

            'simpledesk-mail-reply-parsing.strip_signatures' => true,

            'simpledesk-mail-reply-parsing.fallback_to_full_body' => false,

            'simpledesk-mail-reply-parsing.empty_body_fallback' => 'Ответ не содержит нового текстового содержимого.',

            'simpledesk-mail-reply-parsing.max_body_characters' => 200000,

            'simpledesk-mail-reply-parsing.custom_separators' => [],
        ]);

        $this->parser = app(
            InboundEmailReplyParser::class
        );
    }

    public function test_it_removes_gmail_style_quoted_text(): void
    {
        $message = $this->message(
            textBody: implode("\n", [
                'Проблема всё ещё воспроизводится.',
                '',
                'On Mon, 27 Jul 2026 at 20:24 SimpleDesk Support wrote:',
                '> Здравствуйте!',
                '> Попробуйте повторить операцию.',
            ]),
        );

        $result = $this->parser->parse(
            $message
        );

        $this->assertSame(
            'Проблема всё ещё воспроизводится.',
            $result->body
        );

        $this->assertSame(
            'text',
            $result->source
        );

        $this->assertTrue(
            $result->quotedTextRemoved
        );

        $this->assertFalse(
            $result->signatureRemoved
        );

        $this->assertLessThan(
            $result->originalLength,
            $result->parsedLength
        );
    }

    public function test_it_removes_outlook_header_block(): void
    {
        $message = $this->message(
            textBody: implode("\n", [
                'Да, проблема сохраняется.',
                '',
                'From: SimpleDesk Support <support@simpledesk.test>',
                'Sent: Monday, July 27, 2026 8:24 PM',
                'To: Customer <customer@simpledesk.test>',
                'Subject: Re: Incoming SimpleDesk test',
                '',
                'Предыдущее сообщение.',
            ]),
        );

        $result = $this->parser->parse(
            $message
        );

        $this->assertSame(
            'Да, проблема сохраняется.',
            $result->body
        );

        $this->assertTrue(
            $result->quotedTextRemoved
        );
    }

    public function test_it_removes_mobile_signature(): void
    {
        $message = $this->message(
            textBody: implode("\n", [
                'Спасибо, теперь всё работает.',
                '',
                'Отправлено с моего iPhone',
            ]),
        );

        $result = $this->parser->parse(
            $message
        );

        $this->assertSame(
            'Спасибо, теперь всё работает.',
            $result->body
        );

        $this->assertFalse(
            $result->quotedTextRemoved
        );

        $this->assertTrue(
            $result->signatureRemoved
        );
    }

    public function test_it_removes_html_quote_blocks(): void
    {
        $message = $this->message(
            htmlBody: <<<'HTML'
                <div>Новый ответ клиента.</div>

                <div class="gmail_quote">
                    <div>
                        On Mon, 27 Jul 2026 SimpleDesk Support wrote:
                    </div>

                    <blockquote>
                        Предыдущее сообщение.
                    </blockquote>
                </div>
                HTML,
        );

        $result = $this->parser->parse(
            $message
        );

        $this->assertSame(
            'Новый ответ клиента.',
            $result->body
        );

        $this->assertSame(
            'html',
            $result->source
        );

        $this->assertStringNotContainsString(
            'Предыдущее сообщение',
            $result->body
        );
    }

    public function test_literal_backslash_n_is_not_treated_as_a_line_break(): void
    {
        $textBody =
            'Новый ответ.\n\n'
            .'On Mon, 27 Jul 2026 SimpleDesk Support wrote:\n'
            .'> Предыдущее сообщение.';

        $message = $this->message(
            textBody: $textBody,
        );

        $result = $this->parser->parse(
            $message
        );

        $this->assertSame(
            $textBody,
            $result->body
        );

        $this->assertFalse(
            $result->quotedTextRemoved
        );

        $this->assertStringContainsString(
            '\n',
            $result->body
        );
    }

    public function test_it_uses_fallback_for_an_empty_message(): void
    {
        $message = $this->message();

        $result = $this->parser->parse(
            $message
        );

        $this->assertSame(
            'Ответ не содержит нового текстового содержимого.',
            $result->body
        );

        $this->assertSame(
            'empty',
            $result->source
        );

        $this->assertSame(
            0,
            $result->originalLength
        );
    }

    private function message(
        ?string $textBody = null,
        ?string $htmlBody = null,
    ): EmailMessage {
        $message = new EmailMessage;

        $message->forceFill([
            'text_body' => $textBody,
            'html_body' => $htmlBody,
        ]);

        return $message;
    }
}
