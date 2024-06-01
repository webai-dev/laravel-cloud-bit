<?php

namespace App\Billing\EventHandlers\Stripe;

class StripeEventHandler {
    function __construct($event_data, $event_type) {
        $this->data = $event_data;
        $this->type = $event_type;
    }

    public function handle(){
        return;
    }
}