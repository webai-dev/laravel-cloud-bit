<?php

namespace App\Billing\EventHandlers\Stripe;

use App\Models\Teams\Team;

class PaymentSucceededHandler extends StripeEventHandler {

    public function handle(){
        $sub_code = $this->data->object->subscription;

        return 'OK';
    }
}