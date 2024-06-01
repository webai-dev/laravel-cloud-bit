<?php

namespace App\Listeners\Subscriptions;

use App\Enums\SubscriptionType;
use App\Events\Subscriptions\SubscriptionCanceled;

class SubscriptionCanceledListener {
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct() {
        //
    }

    /**
     * Handle the event.
     *
     * @param  SubscriptionCanceled $event
     * @return void
     */
    public function handle(SubscriptionCanceled $event) {
        $subscription = $event->getSubscription();
        $team = $subscription->team;

        if ($subscription->type == SubscriptionType::MAIN) {
            $team->storage_limit = config('filesystems.default_storage_limit');
            $team->save();
        }
    }
}
