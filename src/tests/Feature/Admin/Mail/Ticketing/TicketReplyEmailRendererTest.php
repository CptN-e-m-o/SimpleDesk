<?php

namespace Tests\Feature\Admin\Mail\Ticketing;

use App\Models\Admin\Department;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User\User;
use App\Services\Admin\Mail\Ticketing\TicketReplyEmailRenderer;
use Tests\TestCase;

class TicketReplyEmailRendererTest extends TestCase
{
    private TicketReplyEmailRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set([
            'simpledesk-mail-ticketing.outgoing_replies.subject_prefix' => 'Re: ',

            'simpledesk-mail-ticketing.outgoing_replies.include_agent_signature' => true,

            'simpledesk-mail-ticketing.outgoing_replies.include_department_signature' => true,
        ]);

        $this->renderer = app(
            TicketReplyEmailRenderer::class
        );
    }

    public function test_it_renders_reply_with_signatures(): void
    {
        $reply = $this->reply(
            ticketSubject: 'Re: Re: Не работает авторизация',

            message: 'Здравствуйте! Попробуйте очистить кеш браузера.',

            agentSignature: '<p>Иван Иванов<br>Technical Support</p>',

            departmentSignature: '<p>SimpleDesk Support Team</p>',
        );

        $result = $this->renderer->render(
            $reply
        );

        $this->assertSame(
            'Re: Не работает авторизация',
            $result->subject
        );

        $this->assertStringContainsString(
            'Здравствуйте! Попробуйте очистить кеш браузера.',
            $result->textBody
        );

        $this->assertStringContainsString(
            'Иван Иванов',
            $result->textBody
        );

        $this->assertStringContainsString(
            'Technical Support',
            $result->textBody
        );

        $this->assertStringContainsString(
            'SimpleDesk Support Team',
            $result->textBody
        );

        $this->assertStringContainsString(
            '<div class="simpledesk-message">',
            $result->htmlBody
        );

        $this->assertStringContainsString(
            '<div class="simpledesk-signatures">',
            $result->htmlBody
        );
    }

    public function test_it_escapes_agent_message_in_html(): void
    {
        $reply = $this->reply(
            ticketSubject: 'Ошибка отображения',

            message: '<script>alert("test")</script>'
            ."\n"
            .'<b>Это должно быть текстом</b>',

            agentSignature: null,
            departmentSignature: null,
        );

        $result = $this->renderer->render(
            $reply
        );

        $this->assertStringNotContainsString(
            '<script>',
            $result->htmlBody
        );

        $this->assertStringNotContainsString(
            '<b>Это должно быть текстом</b>',
            $result->htmlBody
        );

        $this->assertStringContainsString(
            '&lt;script&gt;alert(&quot;test&quot;)&lt;/script&gt;',
            $result->htmlBody
        );

        $this->assertStringContainsString(
            '&lt;b&gt;Это должно быть текстом&lt;/b&gt;',
            $result->htmlBody
        );
    }

    public function test_it_does_not_duplicate_identical_signatures(): void
    {
        $signature =
            '<p>SimpleDesk Support Team</p>';

        $reply = $this->reply(
            ticketSubject: 'Тест подписей',

            message: 'Ответ специалиста.',

            agentSignature: $signature,

            departmentSignature: $signature,
        );

        $result = $this->renderer->render(
            $reply
        );

        $this->assertSame(
            1,
            substr_count(
                $result->textBody,
                'SimpleDesk Support Team'
            )
        );
    }

    private function reply(
        string $ticketSubject,
        string $message,
        ?string $agentSignature,
        ?string $departmentSignature,
    ): TicketReply {
        $department = new Department;

        $department->forceFill([
            'signature' => $departmentSignature,
        ]);

        $ticket = new Ticket;

        $ticket->forceFill([
            'subject' => $ticketSubject,
        ]);

        $ticket->setRelation(
            'department',
            $department
        );

        $agent = new User;

        $agent->forceFill([
            'email' => 'agent@simpledesk.test',

            'first_name' => 'Иван',

            'last_name' => 'Иванов',

            'signature' => $agentSignature,
        ]);

        $reply = new TicketReply;

        $reply->forceFill([
            'message' => $message,

            'is_internal' => false,
        ]);

        $reply->setRelation(
            'ticket',
            $ticket
        );

        $reply->setRelation(
            'user',
            $agent
        );

        return $reply;
    }
}
