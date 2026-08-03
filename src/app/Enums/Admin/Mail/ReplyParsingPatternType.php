<?php

namespace App\Enums\Admin\Mail;

enum ReplyParsingPatternType: string
{
    case Literal = 'literal';
    case Regex = 'regex';
}
