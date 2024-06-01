<?php

namespace App\Http\Controllers\Internal\Shop;

use App\Http\Controllers\Controller;
use App\Models\Teams\Team;

class StorageController extends Controller {

    public function index(Team $team) {
        $this->authorize('update_billing', $team);

        $used = $team->getTotalUsedStorage();

        $total = $team->storage_limit;

        return response()->json(compact('used', 'total'));
    }

}
