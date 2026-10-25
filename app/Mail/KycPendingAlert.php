<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class KycPendingAlert extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $messageContent;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $messageContent)
    {
        $this->user = $user;
        $this->messageContent = $messageContent;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('KYC Pending Reminder')
                    ->view('emails.kyc-pending')
                    ->with([
                        'messageContent' => $this->messageContent,
                    ]);
    }
}
