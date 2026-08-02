<?php

namespace App\Enums\Admin\Mail;

enum ImapEncryption: string
{
    case None = 'none';
    case StartTls = 'starttls';
    case Tls = 'tls';

    public function defaultPort(): int
    {
        return match ($this) {
            self::None,
            self::StartTls => 143,

            self::Tls => 993,
        };
    }

    public function webklexValue(): string|false
    {
        return match ($this) {
            self::None => false,
            self::StartTls => 'tls',
            self::Tls => 'ssl',
        };
    }
}
