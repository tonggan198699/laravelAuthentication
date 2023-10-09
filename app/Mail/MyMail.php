<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MyMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $token;
    public $expiryTimeInMinutes;

    /**
     * Create a new message instance.
     */
    public function __construct(
        User $user, $token, $expiryTimeInMinutes
    ) {
        $this->user = $user;
        $this->token = $token;
        $this->expiryTimeInMinutes = $expiryTimeInMinutes;
    }
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('admin@example.com')
            ->subject('email verification')
            ->view('email');
    }
}
