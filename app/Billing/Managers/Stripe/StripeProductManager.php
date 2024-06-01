<?php

namespace App\Billing\Managers\Stripe;

use App\Billing\Managers\ProductManager;
use App\Models\Teams\Team;
use Stripe\Product;

class StripeProductManager extends BaseBillingManager implements ProductManager {

    public function index(){
        return Product::all(['limit' => 100])->data;;
    }

}