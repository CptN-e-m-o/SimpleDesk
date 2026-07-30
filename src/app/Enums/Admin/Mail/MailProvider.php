<?php

namespace App\Enums\Admin\Mail;

enum MailProvider: string
{
    case Generic = 'generic';
    case Google = 'google';
    case Microsoft = 'microsoft';
    case AmazonSes = 'amazon_ses';
    case Mailgun = 'mailgun';
    case Postmark = 'postmark';
    case Resend = 'resend';
}
