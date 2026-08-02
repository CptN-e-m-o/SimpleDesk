<?php

namespace App\Enums\Admin\Mail;

enum SmtpEncryption: string
{
    case None = 'none';
    case StartTls = 'starttls';
    case Tls = 'tls';

    public function defaultPort(): int
    {
        return match ($this) {
            self::None => 25,
            self::StartTls => 587,
            self::Tls => 465,
        };
    }

    public function usesTls(): bool
    {
        return $this !== self::None;
    }

    public function usesImplicitTls(): bool
    {
        return $this === self::Tls;
    }
}
