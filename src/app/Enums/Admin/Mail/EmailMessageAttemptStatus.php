<?php

namespace App\Enums\Admin\Mail;

enum EmailMessageAttemptStatus: string
{
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
}
