<?php

namespace App\Policies;

use App\Models\Teams\Team;
use App\Models\User;

class TeamPolicy extends BasePolicy {

    public function view(User $user,Team $team){
        return $user->isInTeam($team->id) && !$user->isGuestUser($team);
    }

    public function update(User $user,Team $team){
        return $user->id == $team->user_id || $user->hasRoleInTeam('admin',$team->id);
    }

    public function close(User $user,Team $team){
        return $user->id == $team->user_id;
    }

    public function update_billing(User $user,Team $team){
        return $user->id == $team->user_id || $user->hasRoleInTeam('admin',$team->id);
    }

    public function suspend(User $user, Team $team){
        //Only allow superusers to suspend a team (handled in base policy)
        return false;
    }
}