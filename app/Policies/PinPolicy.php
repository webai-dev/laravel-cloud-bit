<?php

namespace App\Policies;

use App\Models\File;
use App\Models\Pins\Pin;
use App\Models\User;
use Faker\Provider\Base;
use Illuminate\Http\Request;

class PinPolicy extends BasePolicy {

    public function create(User $user){
        return !$user->hasRoleInTeam('guest',$this->request->input('team_id'));
    }

    public function edit(User $user){
        return !$user->hasRoleInTeam('guest',$this->request->input('team_id'));
    }

    public function delete(User $user){
        return !$user->hasRoleInTeam('guest',$this->request->input('team_id'));
    }
}