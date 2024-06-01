<?php

namespace App\Tracing\Observers;

use App\Models\Lock;
use App\Tracing\Facades\Activity;

class LockObserver {
    public function created(Lock $new) {
        $lockable = $new->lockable;
        $lockable->trace('lock', self::lockMetadata($new));
        Activity::minor($lockable);
        $lockable->cleanTraces();
    }

    public function deleting(Lock $new) {
        $lockable = $new->lockable;
        $lockable->trace('unlock', self::lockMetadata($new));
        Activity::minor($lockable);
        $lockable->cleanTraces();
    }

    private static function lockMetadata($new) {
        return [
            'lock_id' => $new->id,
            'team_id' => $new->team_id,
            'user_id' => $new->user_id,
        ];
    }
}