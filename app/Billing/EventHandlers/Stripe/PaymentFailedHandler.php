<?php

namespace App\Billing\EventHandlers\Stripe;

class PaymentFailedHandler extends StripeEventHandler {

    public function handle(){
        $sub_code = $this->data->object->subscription;

        return 'OK';
    }
}