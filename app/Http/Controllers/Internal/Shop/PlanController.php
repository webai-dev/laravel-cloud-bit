<?php

namespace App\Http\Controllers\Internal\Shop;

use App\Billing\Managers\PlanManager;
use App\Http\Controllers\Controller;

class PlanController extends Controller {

    public function index(PlanManager $manager) {
        return $manager->index();
    }
}
