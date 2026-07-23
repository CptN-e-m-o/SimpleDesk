<?php

namespace App\Enums\Mail;

enum MailboxDriver: string
{
    case Imap = 'imap';
    case Smtp = 'smtp';

    case GmailApi = 'gmail_api';
    case MicrosoftGraph = 'microsoft_graph';

    case AmazonSes = 'amazon_ses';
    case Mailgun = 'mailgun';
    case Postmark = 'postmark';
    case Resend = 'resend';

    case Webhook = 'webhook';

    public function supportsIncoming(): bool
    {
        return match ($this) {
            self::Imap,
            self::GmailApi,
            self::MicrosoftGraph,
            self::AmazonSes,
            self::Mailgun,
            self::Postmark,
            self::Resend,
            self::Webhook => true,

            self::Smtp => false,
        };
    }

    public function supportsOutgoing(): bool
    {
        return match ($this) {
            self::Smtp,
            self::GmailApi,
            self::MicrosoftGraph,
            self::AmazonSes,
            self::Mailgun,
            self::Postmark,
            self::Resend => true,

            self::Imap,
            self::Webhook => false,
        };
    }
}
