<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\PaymentLink;

class PaymentLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public PaymentLink $link;

    public function __construct(PaymentLink $link)
    {
        $this->link = $link;
    }

    public function build()
    {
        $url = url('/payment/' . ($this->link->token ?? ''));
        $subject = 'Payment link';
        return $this->subject($subject)
                    ->view('emails.payment_link')
                    ->with([
                        'link' => $this->link,
                        'url' => $url,
                    ]);
    }
}