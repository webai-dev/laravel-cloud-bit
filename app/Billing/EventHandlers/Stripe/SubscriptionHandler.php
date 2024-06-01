<?php

namespace App\Billing\EventHandlers\Stripe;

use App\Models\Teams\Subscription;
use App\Events\Subscriptions\SubscriptionCanceled;

class SubscriptionHandler extends StripeEventHandler {
    public function handle(){
        $sub_object = $this->data->object;

        $subscription = Subscription::where('code', $sub_object->id)->firstOrFail();

        if($this->type == 'customer.subscription.deleted'){
            $subscription ->active = false;
            event(new SubscriptionCanceled($subscription));
        }

        $subscription->status = $sub_object->status;
        $subscription->save();
    }
}