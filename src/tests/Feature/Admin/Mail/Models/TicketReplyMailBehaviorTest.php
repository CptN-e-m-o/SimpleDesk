<?php

namespace Tests\Feature\Admin\Mail\Models;

use App\Models\Admin\Mail\EmailMessage;
use App\Models\TicketReply;
use Tests\TestCase;

class TicketReplyMailBehaviorTest extends TestCase
{
    public function test_internal_note_cannot_be_sent_by_email(): void
    {
        $reply = new TicketReply();

        $reply->forceFill([
            'is_internal' => true,
        ]);

        $reply->setRelation(
            'incomingEmailMessage',
            null
        );

        $this->assertFalse(
            $reply->canBeSentByEmail()
        );
    }

    public function test_reply_created_from_incoming_email_cannot_be_sent_back(): void
    {
        $reply = new TicketReply();

        $reply->forceFill([
            'is_internal' => false,
        ]);

        $reply->setRelation(
            'incomingEmailMessage',
            new EmailMessage()
        );

        $this->assertTrue(
            $reply->cameFromIncomingEmail()
        );

        $this->assertFalse(
            $reply->canBeSentByEmail()
        );
    }

    public function test_public_agent_reply_can_be_sent_by_email(): void
    {
        $reply = new TicketReply();

        $reply->forceFill([
            'is_internal' => false,
        ]);

        $reply->setRelation(
            'incomingEmailMessage',
            null
        );

        $this->assertFalse(
            $reply->cameFromIncomingEmail()
        );

        $this->assertTrue(
            $reply->canBeSentByEmail()
        );
    }
}
