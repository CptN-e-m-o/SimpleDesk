<?php

namespace App\Enums\Admin\Mail;

enum MailAdminAuditSubjectType: string
{
    case Mailbox = 'mailbox';
    case MailboxChannel = 'mailbox_channel';
    case ProviderConnection = 'provider_connection';
    case EmailMessage = 'email_message';
    case EmailAttachment = 'email_attachment';
    case EmailQuarantine = 'email_quarantine';
    case Antivirus = 'antivirus';
}
