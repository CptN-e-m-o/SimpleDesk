<?php

namespace App\Enums\Admin\Mail;

enum MailOAuthTenantMode: string
{
    case Common = 'common';
    case Organizations = 'organizations';
    case Specific = 'specific';
}
