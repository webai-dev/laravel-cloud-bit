<?php

namespace App\Billing\Managers\Stripe;

use App\Billing\Managers\PlanManager;
use Stripe\Plan;
use Stripe\Product;

class StripePlanManager extends BaseBillingManager implements PlanManager {

    public function index(){
        return Plan::all(['limit' => 100])->data;
    }

    public function storage($code){
        $plan = Plan::retrieve(['id' => $code]);
        $product = Product::retrieve(['id' => $plan->product]);
        return intval($product->metadata->storage);
    }

}