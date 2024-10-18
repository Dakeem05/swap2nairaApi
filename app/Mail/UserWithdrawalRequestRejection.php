<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserWithdrawalRequestRejection extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $amount;      
    public $balance;
    public $reason;      
    public $image;      

    public function __construct($name, $amount, $balance, $reason, $image)
    {
        $this->name = $name;
        $this->amount = $amount;
        $this->balance = $balance;
        $this->reason = $reason;
        $this->image = $image;
    }

    public function build()
    {
        return $this->view('emails.UserWithdrawalRequestRejection');
    }
}
