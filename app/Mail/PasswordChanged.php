<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class PasswordChanged extends Mailable
{
    use Queueable, SerializesModels;

    private $user;
    
    public function setUser($user)
    {      
        $this->user = $user;       
    }
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('email.change-password-alert', [ 'user' => $this->user ])
                    ->subject('Your password has been changed');
    }
}
