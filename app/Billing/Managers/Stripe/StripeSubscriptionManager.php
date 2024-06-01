<?php

namespace App\Billing\Managers\Stripe;

use App\Billing\Managers\SubscriptionManager;
use App\Models\Teams\Subscription as SubscriptionModel;
use App\Models\Teams\Team;
use Stripe\Plan;
use Stripe\Product;
use Stripe\Subscription;

class StripeSubscriptionManager extends BaseBillingManager implements SubscriptionManager {

    public function plan(SubscriptionModel $subscription) {
        /** @var Subscription $response */
        $response = Subscription::retrieve($subscription->code);

        /** @var Plan $plan */
        $plan = $response->items->data[0]->plan;

        return [
            'id'   => $plan->id,
            'data' => $plan->metadata
        ];
    }

    public function create(Team $team, $plan_code) {
        /** @var Subscription $subscription */
        $subscription = Subscription::create([
            'customer'  => $team->customer_code,
            'items'     => [
                ['plan' => $plan_code]
            ]
        ]);

        $product = Product::retrieve($subscription->plan->product);

        $sub = new SubscriptionModel();
        $sub->type = $product->metadata->type;
        $sub->code = $subscription->id;
        $sub->plan_code = $subscription->plan->id;
        $sub->storage = $product->metadata->storage;
        $sub->team_id = $team->id;
        $sub->status = 'active';
        $sub->save();

        return $sub;
    }

    public function update(SubscriptionModel $sub, $plan_code) {
        /** @var Subscription $subscription */
        $subscription = Subscription::retrieve($sub->code);

        /** @var Subscription $updated_subscription */
        $updated_subscription = Subscription::update($subscription->id, [
            'cancel_at_period_end' => false,
            'items'                => [
                [
                    'id'   => $subscription->items->data[0]->id,
                    'plan' => $plan_code,
                ],
            ]
        ]);

        $product = Product::retrieve($updated_subscription->plan->product);

        $sub->plan_code = $plan_code;
        $sub->storage = $product->metadata->storage;
        $sub->save();

        return $sub;
    }

    public function cancel(SubscriptionModel $sub) {
        /** @var Subscription $subscription */
        $subscription = Subscription::retrieve($sub->code);
        $subscription->cancel();

        return $sub;
    }
}