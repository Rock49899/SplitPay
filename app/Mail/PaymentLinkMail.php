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
    public string $messageType;

    public function __construct(PaymentLink $link, string $messageType = 'initial')
    {
        $this->link = $link;
        $this->messageType = $messageType;
    }

    public function build()
    {
        $url = url('/payment/' . ($this->link->token ?? ''));
        
        // Adapter le sujet selon le type de message
        $subject = match($this->messageType) {
            'reminder' => '🔔 Rappel : Paiement en attente',
            'urgent' => '⚠️ URGENT : Échéance de paiement proche',
            'final' => '⏰ DERNIER RAPPEL : Paiement requis',
            default => '💳 Lien de paiement'
        };
        
        return $this->subject($subject)
                    ->view('emails.payment_link')
                    ->with([
                        'link' => $this->link,
                        'url' => $url,
                        'messageType' => $this->messageType,
                    ]);
    }
}