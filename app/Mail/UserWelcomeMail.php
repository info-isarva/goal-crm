<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $plainPassword;
    public $verificationUrl;

    public function __construct($user, $plainPassword, $verificationUrl)
    {
        $this->user = $user;
        $this->plainPassword = $plainPassword;
        $this->verificationUrl = $verificationUrl;
    }

    public function build()
    {
        return $this->subject('Verify Your Email & Login Credentials')
            ->view('emails.user_welcome');
    }
}
