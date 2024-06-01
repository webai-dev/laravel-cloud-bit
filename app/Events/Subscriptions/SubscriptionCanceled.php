<?php

namespace App\Events\Subscriptions;

use App\Models\Teams\Subscription;

class SubscriptionCanceled {

    protected $subscription;

    public function __construct(Subscription $subscription) {
        $this->subscription = $subscription;
    }

    public function getSubscription() {
        return $this->subscription;
    }
}
