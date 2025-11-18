<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserGeneratedPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $password) {}

    public function build()
    {
        return $this->subject('Ваш доступ к системе SimpleDesk')
            ->view('emails.user-generated-password')
            ->with('password', $this->password);
    }
}
