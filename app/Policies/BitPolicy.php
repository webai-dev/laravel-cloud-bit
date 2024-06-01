<?php

namespace App\Policies;

use App\Models\User;
use App\Sharing\Shareable;

class BitPolicy extends ShareablePolicy {

    protected $type = 'bit';

    public function view(User $user,Shareable $shareable){
        $team = $shareable->team;
        return $shareable->hasPermissionFor('view',$user->id) && !$team->hasUnpaidSubscriptions();
    }


}