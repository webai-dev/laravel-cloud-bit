<?php

namespace App\Listeners;

use App\Events\PermissionRevoked;
use App\Indexing\Jobs\IndexUnsharedItem;
use App\Jobs\ItemUnshared;

class PermissionRevokedListener {

    public function handle(PermissionRevoked $event) {
        $share = $event->share;

        $shareable = $share->shareable;

        dispatch(new ItemUnshared($shareable, $share->recipient));

        dispatch(new IndexUnsharedItem($share->id));
    }

}
