<?php

namespace App\Policies;

use App\Models\Shortcut;
use App\Models\User;

class ShortcutPolicy extends BasePolicy {

    public function move(User $user,Shortcut $shortcut){
        return $user->id == $shortcut->user_id;
    }

    public function delete(User $user,Shortcut $shortcut){
        return $user->id == $shortcut->user_id;
    }

}