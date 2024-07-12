<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserSellRequestRejection extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $number;
    public $type;
    public $rate;
    public $sum;

    public function __construct($name, $number, $type, $rate, $sum)
    {
        $this->name = $name;
        $this->number = $number;
        $this->type = $type;
        $this->rate = $rate;
        $this->sum = $sum;
    }

    public function build()
    {
        return $this->view('emails.UserSellRequestRejection');
    }
}
