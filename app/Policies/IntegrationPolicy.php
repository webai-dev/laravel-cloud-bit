<?php

namespace App\Policies;

use App\Models\User;

class IntegrationPolicy extends BasePolicy {

    public function view(User $user){
        $team = $this->request->getTeam();
        return $user->id == $team->user_id || $user->hasRoleInTeam('admin',$team->id);
    }

    public function create(User $user){
        $team = $this->request->getTeam();
        return $user->id == $team->user_id || $user->hasRoleInTeam('admin',$team->id);
    }

    public function update(User $user){
        $team = $this->request->getTeam();
        return $user->id == $team->user_id || $user->hasRoleInTeam('admin',$team->id);
    }

    public function delete(User $user){
        $team = $this->request->getTeam();
        return $user->id == $team->user_id || $user->hasRoleInTeam('admin',$team->id);
    }
}