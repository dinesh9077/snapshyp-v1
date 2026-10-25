<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $username;

    public function __construct($username, $otp)
    {
        $this->username = $username;
        $this->otp = $otp;
    }

    public function build()
    {
        return $this->subject('Your OTP for Account Verification')
                    ->view('emails.otp-email')
                    ->with([
                        'otp' => $this->otp,
                        'username' => $this->username
                    ]);
    }
}
