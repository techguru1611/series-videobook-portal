<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    private $resetPasswordLink;

    public function setResetPasswordLink($resetPasswordLink)
    {
        $this->resetPasswordLink = $resetPasswordLink;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('email.password-reset', [ 'resetPasswordLink' => $this->resetPasswordLink ])
                    ->subject('Password Reset');
    }
}
