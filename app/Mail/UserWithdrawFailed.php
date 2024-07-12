<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserWithdrawFailed extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $amount;
    public $balance;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($name, $amount, $balance)
    {
        $this->name = $name;
        $this->amount = $amount;
        $this->balance = $balance;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.UserWithdrawFailed');
    }
}
