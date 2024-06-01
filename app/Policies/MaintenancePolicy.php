<?php

namespace App\Policies;

use App\Models\Maintenance;
use App\Models\User;

class MaintenancePolicy extends BasePolicy
{
    public function view(User $user){
        return false;
    }

    public function create(User $user){
        return false;
    }

    public function update(User $user){
        return false;
    }

    public function delete(User $user){
        return false;
    }
}