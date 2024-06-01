<?php

namespace App\Policies;

use App\Models\Share;
use App\Models\User;

class SharePolicy extends BasePolicy {

    public function update(User $user,Share $share){
        return $share->shareable->hasPermissionFor('share',$user->id);
    }

    public function delete(User $user,Share $share){
        return $share->shareable->hasPermissionFor('share',$user->id)
            || $share->user_id == $user->id;
    }

    public function create_shortcut(User $user,Share $share){
        return $user->id == $share->user_id;
    }

}