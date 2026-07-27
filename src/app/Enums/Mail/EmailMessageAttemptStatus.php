<?php

namespace App\Enums\Mail;

enum EmailMessageAttemptStatus: string
{
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
}
