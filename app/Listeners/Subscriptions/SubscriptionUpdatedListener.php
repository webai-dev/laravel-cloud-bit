<?php

namespace App\Listeners\Subscriptions;

use App\Billing\Managers\SubscriptionManager;
use App\Events\Subscriptions\SubscriptionUpdated;

class SubscriptionUpdatedListener {
    protected $subscriptions;

    public function __construct(SubscriptionManager $subscriptions) {
        $this->subscriptions = $subscriptions;
    }

    /**
     * Handle the event.
     *
     * @param  SubscriptionUpdated $event
     * @return void
     */
    public function handle(SubscriptionUpdated $event) {
        $subscription = $event->getSubscription();
        $team = $subscription->team;

        //When upgrading to a main subscription that includes sufficient storage, cancel any existing storage subscriptions
        if ($subscription->type == 'main') {
            $team->storage_limit = $subscription->storage;
            $team->save();
        }
    }
}
