<?php


namespace App\Billing\EventHandlers\Stripe;

use App\Models\Teams\Team;

class CustomerDeletedHandler extends StripeEventHandler {
    public function handle(){
        $code = $this->data->object->id;
        $team = Team::where('customer_code', $code)->firstOrFail();
        $team->customer_code = null;
        $team->save();
    }
}