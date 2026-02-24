<?php

namespace App\Services;

use Payplus\Pay\PayPlus;
use App\Models\Payment;

class PayPlusService
{
    /**
     * Initier un paiement mobile money SANS redirection (MTN / Moov).
     * Le payeur reçoit une notification USSD/push sur son téléphone pour confirmer.
     *
     * @param  Payment  
     * @param  string   $customerPhone  Numéro au format 22967XXXXXXX
     * @param  array    $options  payer_first_name, payer_last_name, payer_email
     * @return array    ['token' => string, 'response_code' => string, 'message' => string]
     * @throws \Exception si PayPlus rejette la requête
     */
    public function launchPayment(Payment $payment, string $customerPhone, array $options = []): array
    {
        $co = (new PayPlus())->init();

        // Décrire l'article
        $co->addItem(
            'Paiement scolarité',
            1,
            (float) $payment->amount,
            (float) $payment->amount,
            "Référence : {$payment->reference}"
        );

        $co->setTotalAmount((float) $payment->amount);
        $co->setDescription('Paiement frais de scolarité');
        $co->setDevise('xof'); 

        $co->setCustomerNumber($customerPhone);

        // Informations optionnelles du payeur
        if (!empty($options['payer_first_name'])) {
            $co->setCustomerFirstName($options['payer_first_name']);
        }
        if (!empty($options['payer_last_name'])) {
            $co->setCustomerLastName($options['payer_last_name']);
        }
        if (!empty($options['payer_email'])) {
            $co->setCustomerEmail($options['payer_email']);
        }

        $co->setOtp('');

        // Métada personnalisées pour retrouver le paiement dans le webhook
        $co->addCustomData('payment_id', $payment->id);
        $co->addCustomData('reference', $payment->reference);

        // Logger le payload qui sera envoyé à PayPlus
        \Illuminate\Support\Facades\Log::info('PayPlus payload envoyé', [
            'url'             => $co->getPaiementUrl(),
            'phone'           => $customerPhone,
            'amount'          => $payment->amount,
            'reference'       => $payment->reference,
            'devise'          => 'xof',
        ]);

        // Lancer le paiement sans redirection
        $result = $co->launchPaiement();

        // Logger la réponse brute pour diagnostic
        \Illuminate\Support\Facades\Log::info('PayPlus launchPaiement raw response', [
            'result' => is_object($result) ? (array) $result : $result,
            'payment_reference' => $payment->reference,
            'phone' => $customerPhone,
            'amount' => $payment->amount,
        ]);

        // L'objet peut être un array en cas d'exception 
        if (is_array($result)) {
            throw new \Exception('Erreur réseau PayPlus : ' . ($result['message'] ?? json_encode($result)));
        }

        $responseCode = $result->response_code ?? '';

        if ($responseCode !== '00') {
            throw new \Exception(
                'PayPlus : ' . ($result->response_text ?? $result->description ?? json_encode($result))
            );
        }

        $payment->update([
            'payplus_transaction_id' => $result->token,
            'metadata' => [
                'payplus_token'   => $result->token,
                'response_code'   => $result->response_code,
                'response_text'   => $result->response_text,
                'customer_phone'  => $customerPhone,
            ],
        ]);

        return [
            'token'         => $result->token,
            'response_code' => $result->response_code,
            'message'       => $result->response_text ?? 'Paiement initié avec succès.',
        ];
    }

    /**
     *
     * @param  string  $token  Token PayPlus de la transaction
     * @return object  Réponse PayPlus (status: pending|completed|notcompleted)
     */
    public function verify(string $token): object
    {
        $co = (new PayPlus())->init();
        return $co->confirm($token);
    }
}
