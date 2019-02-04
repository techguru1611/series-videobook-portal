<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ForgotPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    private $resetOtp;

    public function resetOtp($resetOtp)
    {
        $this->resetOtp = $resetOtp;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('email.reset-password', [ 'resetOtp' => $this->resetOtp ])
                    ->subject('Password Reset');
    }
}
