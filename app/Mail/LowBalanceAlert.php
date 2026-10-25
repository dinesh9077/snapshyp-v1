<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LowBalanceAlert extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
	
	public $user;
    public $messageContent;
	
    public function __construct($user, $messageContent)
    {
        $this->user = $user;
        $this->messageContent = $messageContent;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Low Wallet Balance Alert')
                    ->view('emails.low_balance_alert')
                    ->with([
                        'user' => $this->user,
                        'messageContent' => $this->messageContent
                    ]);
    }
}
