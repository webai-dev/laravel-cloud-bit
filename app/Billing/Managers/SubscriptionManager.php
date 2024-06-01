<?php

namespace App\Billing\Managers;


use App\Models\Teams\Subscription;
use App\Models\Teams\Team;

interface SubscriptionManager {

    public function plan(Subscription $subscription);

    public function create(Team $team, $plan_code);

    public function update(Subscription $subscription, $plan_code);

    public function cancel(Subscription $subscription);

}