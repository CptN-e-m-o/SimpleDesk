<?php

namespace App\Enums\Admin\Mail;

enum ReplyParsingContentType: string
{
    case PlainText = 'plain_text';
    case Html = 'html';
    case Both = 'both';
}
