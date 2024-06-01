<?php

namespace App\Billing\Managers;

use App\Models\Teams\Team;

interface CustomerManager {

    public function create(Team $team, $token);

    public function update(Team $team, $token);

    public function cards(Team $team);
}