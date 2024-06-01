<?php

namespace App\Http\Controllers\Internal\Shop;

use App\Billing\Managers\ProductManager;
use App\Http\Controllers\Controller;
use App\Models\Teams\Team;

class ProductController extends Controller {

    public function index(Team $team, ProductManager $manager) {
        $all_products = $manager->index();

        $products= [];
        foreach ($all_products as $p){
            if($p->metadata->team_id) $p->metadata->team_id = intval($p->metadata->team_id);
            if($p->metadata->custom) $p->metadata->custom = $p->metadata->custom == 'true';
            if($p->metadata->storage) $p->metadata->storage = intval($p->metadata->storage);

            if($p->metadata->custom && $p->metadata->team_id != $team->id) continue;
            else $products[] = $p;
        }

        return $products;
    }
}
