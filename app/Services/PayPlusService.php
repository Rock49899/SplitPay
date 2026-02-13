<?php

namespace App\Services;

use App\Models\Payment;

class PayPlusService
{
    public function createCheckout(Payment $payment)
    {
        require_once base_path('pay-php-gateway/pay-php-gateway.php');

        $setup = new \Pay_Setup();
        $setup->setApi_key(config('payplus.api_key'));
        $setup->setMode(config('payplus.mode'));
        $setup->setToken(config('payplus.token'));

        $store = new \Pay_Checkout_Store();
        $store->setName(config('payplus.application_name'));
        $store->setWebsiteUrl(config('payplus.application_website_url'));
        $store->setCancelUrl(config('payplus.application_cancel_url'));
        $store->setCallbackUrl(config('payplus.application_callback_url'));
        $store->setReturnUrl(config('payplus.application_return_url'));

        $co = new \Pay_Checkout_Invoice($store, $setup);

        $co->addItem(
            "Paiement scolarité",
            1,
            $payment->amount,
            $payment->amount,
            "Paiement référence {$payment->reference}"
        );

        $co->setTotalAmount($payment->amount);
        $co->setDescription("Paiement scolarité étudiant");

        if ($co->create()) {

            // Enregistrer l'ID transaction PayPlus si dispo
            $payment->update([
                'payplus_transaction_id' => $co->getInvoiceToken() ?? null
            ]);

            return $co->getInvoiceUrl();

        } else {

            throw new \Exception($co->response_text);
        }
    }
}
