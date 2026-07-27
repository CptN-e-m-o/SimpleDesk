<?php

namespace App\Enums\Admin\Mail;

enum MailAuthenticationType: string
{
    case None = 'none';
    case Password = 'password';
    case OAuth2 = 'oauth2';
    case ApiKey = 'api_key';
    case AwsIam = 'aws_iam';
}
