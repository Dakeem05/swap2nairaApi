<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminWithdrawRequest extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $amount;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($name, $amount)
    {
        $this->name = $name;
        $this->amount = $amount;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.AdminWithdrawRequest');
    }
}
