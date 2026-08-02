<?php

namespace Tests\Feature\Admin\Mail\ReplyParsing;

use App\Enums\Admin\Mail\InboundEmailClassification;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\Mailbox;
use App\Services\Admin\Mail\ReplyParsing\InboundEmailClassifier;
use Tests\TestCase;

class InboundEmailClassifierTest extends TestCase
{
    private InboundEmailClassifier $classifier;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set([
            'simpledesk-mail-reply-parsing.ignore.same_mailbox_sender' => true,

            'simpledesk-mail-reply-parsing.ignore.simpledesk_origin' => true,

            'simpledesk-mail-reply-parsing.ignore.auto_replies' => true,

            'simpledesk-mail-reply-parsing.ignore.delivery_status' => true,

            'simpledesk-mail-reply-parsing.ignore.bulk' => true,
        ]);

        $this->classifier = app(
            InboundEmailClassifier::class
        );
    }

    public function test_it_accepts_a_regular_human_message(): void
    {
        $message = $this->message(
            senderAddress: 'customer@simpledesk.test',

            subject: 'Не работает приложение',
        );

        $decision = $this->classifier->classify(
            $message
        );

        $this->assertTrue(
            $decision->shouldProcess
        );

        $this->assertSame(
            InboundEmailClassification::Human,
            $decision->classification
        );

        $this->assertSame(
            'human_message',
            $decision->reason
        );
    }

    public function test_it_blocks_a_message_from_the_same_mailbox(): void
    {
        $message = $this->message(
            senderAddress: 'support@simpledesk.test',
        );

        $decision = $this->classifier->classify(
            $message
        );

        $this->assertFalse(
            $decision->shouldProcess
        );

        $this->assertSame(
            InboundEmailClassification::Loop,
            $decision->classification
        );

        $this->assertSame(
            'sender_matches_mailbox',
            $decision->reason
        );
    }

    public function test_it_blocks_a_simpledesk_origin_message(): void
    {
        $message = $this->message(
            headers: [
                'X-SimpleDesk-Origin' => [
                    'ticket-reply',
                ],
            ],
        );

        $decision = $this->classifier->classify(
            $message
        );

        $this->assertFalse(
            $decision->shouldProcess
        );

        $this->assertSame(
            InboundEmailClassification::Loop,
            $decision->classification
        );

        $this->assertSame(
            'simpledesk_origin_header',
            $decision->reason
        );
    }

    public function test_it_blocks_an_automatic_reply(): void
    {
        $message = $this->message(
            subject: 'Automatic reply: Annual leave',

            headers: [
                'Auto-Submitted' => [
                    'auto-replied',
                ],
            ],
        );

        $decision = $this->classifier->classify(
            $message
        );

        $this->assertFalse(
            $decision->shouldProcess
        );

        $this->assertSame(
            InboundEmailClassification::AutoReply,
            $decision->classification
        );

        $this->assertSame(
            'automatic_response',
            $decision->reason
        );
    }

    public function test_it_blocks_a_delivery_status_notification(): void
    {
        $message = $this->message(
            senderAddress: 'mailer-daemon@example.test',

            subject: 'Delivery Status Notification',

            headers: [
                'Content-Type' => [
                    'multipart/report; report-type=delivery-status',
                ],

                'Return-Path' => [
                    '<>',
                ],
            ],
        );

        $decision = $this->classifier->classify(
            $message
        );

        $this->assertFalse(
            $decision->shouldProcess
        );

        $this->assertSame(
            InboundEmailClassification::DeliveryStatus,
            $decision->classification
        );

        $this->assertSame(
            'delivery_status_notification',
            $decision->reason
        );
    }

    public function test_it_blocks_a_mailing_list_message(): void
    {
        $message = $this->message(
            headers: [
                'List-ID' => [
                    '<developers.example.test>',
                ],

                'Precedence' => [
                    'list',
                ],
            ],
        );

        $decision = $this->classifier->classify(
            $message
        );

        $this->assertFalse(
            $decision->shouldProcess
        );

        $this->assertSame(
            InboundEmailClassification::Bulk,
            $decision->classification
        );

        $this->assertSame(
            'bulk_or_mailing_list_message',
            $decision->reason
        );
    }

    public function test_filter_can_be_disabled_for_auto_replies(): void
    {
        config()->set(
            'simpledesk-mail-reply-parsing.ignore.auto_replies',
            false
        );

        $message = $this->message(
            headers: [
                'Auto-Submitted' => [
                    'auto-replied',
                ],
            ],
        );

        $decision = $this->classifier->classify(
            $message
        );

        $this->assertTrue(
            $decision->shouldProcess
        );

        $this->assertSame(
            InboundEmailClassification::AutoReply,
            $decision->classification
        );
    }

    private function message(
        string $senderAddress =
        'customer@simpledesk.test',

        string $subject =
        'Regular customer message',

        array $headers = [],
    ): EmailMessage {
        $mailbox = new Mailbox;

        $mailbox->forceFill([
            'email_address' => 'support@simpledesk.test',
        ]);

        $message = new EmailMessage;

        $message->forceFill([
            'sender_address' => $senderAddress,

            'subject' => $subject,

            'headers' => $headers,
        ]);

        $message->setRelation(
            'mailbox',
            $mailbox
        );

        return $message;
    }
}
