<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminWithdrawalRequestRejection extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $amount;

    public function __construct($name, $amount)
    {
        $this->name = $name;
        $this->amount = $amount;
    }

    public function build()
    {
        return $this->view('emails.AdminWithdrawalRequestRejection');
    }
}
